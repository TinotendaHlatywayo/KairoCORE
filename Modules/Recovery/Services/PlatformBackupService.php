<?php

namespace Modules\Recovery\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Recovery\Models\PlatformBackup;
use ZipArchive;

class PlatformBackupService
{
    /**
     * Build a recovery archive.
     *
     * @param  int|array<int,int>|null  $schools  null = whole platform, int = one tenant, array = selected tenants
     * @param  string|null  $notes  A free-text note shown in the recovery vault / restore picker
     */
    public function executeFullBackup(int|array|null $schools = null, ?string $notes = null): PlatformBackup
    {
        $schoolIds = $this->normalizeSchools($schools);
        $record = $this->createRecord($schoolIds, $notes);

        return $this->generate($record, $schoolIds);
    }

    /**
     * Create the vault row up front so the UI can show a queued/pending run
     * immediately, then dispatch the heavy generation to a worker.
     *
     * @param  array<int,int>|null  $schoolIds
     */
    public function createRecord(?array $schoolIds, ?string $notes): PlatformBackup
    {
        $timestamp = now()->format('Y-m-d_His');
        $scope = empty($schoolIds) ? 'system' : 'tenant';

        $label = match (true) {
            $scope === 'system' => 'PLATFORM',
            count($schoolIds) === 1 => "TENANT_{$schoolIds[0]}",
            default => 'SELECTED_TENANTS',
        };

        $fileName = "Kairo CORE_{$label}_SNAP_{$timestamp}.zip";

        return PlatformBackup::create([
            'filename' => $fileName,
            'scope' => $scope,
            'school_id' => count($schoolIds ?? []) === 1 ? $schoolIds[0] : null,
            'notes' => $notes,
            'status' => 'pending',
        ]);
    }

    /**
     * Record an archive that already sits in the vault directory as a vault row.
     *
     * Used by the chunked upload endpoint once the browser's pieces have been
     * reassembled on disk. Reads the archive's own backup_meta.json (when it
     * has one) so scope, comment and the include-files flag match what built
     * it. Safe to call twice for the same file: the existing row is returned.
     *
     * @throws \RuntimeException when the file is missing or not a readable ZIP
     */
    public function importArchive(string $absolutePath, string $fileName): PlatformBackup
    {
        $fileName = basename($fileName);

        $existing = PlatformBackup::where('filename', $fileName)->first();
        if ($existing) {
            return $existing;
        }

        if (! is_file($absolutePath)) {
            throw new \RuntimeException("The archive file is no longer on the server ({$fileName}).");
        }

        $scope = 'system';
        $schoolId = null;
        $notes = null;
        // Archives written before files were tracked have no flag at all, and
        // are honest about carrying only the dump.
        $includeFiles = false;

        $zip = new ZipArchive;
        if ($zip->open($absolutePath, ZipArchive::RDONLY) !== true) {
            throw new \RuntimeException("{$fileName} is not a readable ZIP archive.");
        }

        $meta = $zip->getFromName('backup_meta.json');
        if ($meta) {
            $decoded = json_decode($meta, true) ?: [];
            $scope = $decoded['scope'] ?? 'system';
            $schoolId = $decoded['school_id'] ?? null;
            $notes = $decoded['notes'] ?? null;
            $includeFiles = (bool) ($decoded['include_files'] ?? false);
        }
        $zip->close();

        return PlatformBackup::create([
            'filename' => $fileName,
            'scope' => $scope,
            'school_id' => $schoolId,
            'notes' => $notes,
            'include_files' => $includeFiles,
            'size_bytes' => filesize($absolutePath),
            'checksum' => hash_file('sha256', $absolutePath),
            'disk' => 'local',
            'is_verified' => true,
            'status' => 'completed',
        ]);
    }

    /**
     * @param  array<int,int>|null  $schoolIds
     * @param  bool|null  $includeFiles  null = whatever the vault row recorded
     */
    public function generate(PlatformBackup $backupRecord, ?array $schoolIds, ?bool $includeFiles = null): PlatformBackup
    {
        // Streaming the dump keeps memory flat regardless of table size, but a
        // large platform can still take a while — never let PHP kill it midway.
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $includeFiles ??= (bool) ($backupRecord->include_files ?? true);
        $fileName = $backupRecord->filename;
        $scope = $backupRecord->scope;

        // Per-run scratch directory so concurrent runs never collide.
        $workDir = storage_path('app/platform_temp_snapshots/'.uniqid('snap_', true));
        File::ensureDirectoryExists($workDir, 0777, true);

        try {
            $sqlFile = "{$workDir}/backup_payload.sql";
            $this->writeDatabaseDump($sqlFile, $schoolIds);

            $zipFile = "{$workDir}/{$fileName}";
            $zip = new ZipArchive;

            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Unable to create the backup archive.');
            }

            $zip->addFile($sqlFile, 'backup_payload.sql');

            // The SQL alone only brings the rows back. Everything the school
            // actually uploads (logos, favicons, student photos, library scans,
            // website media) lives on disk, so it has to travel in the archive
            // too or a restore hands back an empty media library.
            $fileStats = ['files' => 0, 'bytes' => 0, 'skipped' => 0];
            if ($includeFiles) {
                $fileStats = $this->addFilesToZip($zip);
            }

            $zip->addFromString('backup_meta.json', json_encode([
                'scope' => $scope,
                'school_id' => $backupRecord->school_id,
                'school_ids' => $schoolIds,
                'notes' => $backupRecord->notes,
                'include_files' => $includeFiles,
                'file_count' => $fileStats['files'],
                'file_bytes' => $fileStats['bytes'],
                'generated_at' => now()->toIso8601String(),
                'app_version' => config('app.version', 'unknown'),
            ], JSON_PRETTY_PRINT));

            // close() is where the archive actually gets written: a file that
            // vanished mid-run surfaces here, never as a silently short zip.
            if ($zip->close() === false) {
                throw new Exception('Unable to finish writing the backup archive: '.$zip->getStatusString());
            }

            $size = filesize($zipFile);
            $checksum = hash_file('sha256', $zipFile);

            // Persist into the same local disk the vault reads from. put() with
            // a stream copies from disk rather than buffering the archive in PHP.
            Storage::disk('local')->put("backups/{$fileName}", fopen($zipFile, 'r'));
            File::deleteDirectory($workDir);

            $backupRecord->update([
                'size_bytes' => $size,
                'checksum' => $checksum,
                'include_files' => $includeFiles,
                'status' => 'completed',
                'is_verified' => true,
                'error_log' => null,
            ]);

            return $backupRecord;
        } catch (\Throwable $e) {
            File::deleteDirectory($workDir);

            $backupRecord->update([
                'status' => 'failed',
                'error_log' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Every file the platform cannot rebuild from the database, as entries
     * prefixed with `files/` so a restore can tell them apart from the dump.
     *
     * Each entry's own path (relative to base_path()) doubles as its identity:
     * public/images/logo.png and storage/app/public/logo.png share a basename
     * but never a full path, so nothing can collide inside the archive.
     *
     * @return array{path: string, type: 'dir'|'file'}[]
     */
    public function archiveSources(): array
    {
        $sources = [
            ['path' => storage_path('app'), 'type' => 'dir'],
            ['path' => public_path('images'), 'type' => 'dir'],
            ['path' => public_path('img'), 'type' => 'dir'],
        ];

        // Top-level public files only: site icons, verification tokens, the
        // og:image cards. Directories are code or a symlink to storage, and
        // index.php / .htaccess are deploy artefacts, never restorable data.
        foreach (glob(public_path('*')) ?: [] as $item) {
            if (is_dir($item) || is_link($item)) {
                continue;
            }

            if (in_array(basename($item), ['index.php', '.htaccess', 'hot', 'mix-manifest.json'], true)) {
                continue;
            }

            $sources[] = ['path' => $item, 'type' => 'file'];
        }

        return $sources;
    }

    /**
     * @param  array{path: string, type: 'dir'|'file'}[]|null  $sources
     * @return array{files: int, bytes: int, skipped: int}
     */
    public function addFilesToZip(ZipArchive $zip, ?array $sources = null): array
    {
        $files = 0;
        $bytes = 0;
        $skipped = 0;

        foreach ($sources ?? $this->archiveSources() as $source) {
            $root = $this->relativeToBase($source['path']);

            if ($root === null) {
                $skipped++;

                continue;
            }

            if ($source['type'] === 'file') {
                if ($zip->addFile($source['path'], "files/{$root}")) {
                    $files++;
                    $bytes += (int) @filesize($source['path']);
                } else {
                    $skipped++;
                }

                continue;
            }

            if (! is_dir($source['path'])) {
                continue;
            }

            foreach ($this->walkFiles($source['path'], $root) as [$path, $relative]) {
                if ($zip->addFile($path, "files/{$relative}")) {
                    $files++;
                    $bytes += (int) @filesize($path);
                } else {
                    $skipped++;
                }
            }
        }

        return ['files' => $files, 'bytes' => $bytes, 'skipped' => $skipped];
    }

    /**
     * @return array{string, string}[] [absolute path, path relative to base_path()]
     */
    public function walkFiles(string $absolute, string $relative): \Generator
    {
        $excluded = $this->excludedDirectories();
        $excludedPrefixes = $this->excludedDirectoryPrefixes();

        foreach (new \FilesystemIterator($absolute, \FilesystemIterator::SKIP_DOTS) as $item) {
            $path = $item->getPathname();
            $child = $relative.'/'.$item->getFilename();

            // Symlinks would be followed straight out of the backup set (public/storage
            // points at storage/app/public, which is already archived on its own).
            if (is_link($path)) {
                continue;
            }

            if ($item->isDir()) {
                if (in_array($child, $excluded, true)) {
                    continue;
                }

                foreach ($excludedPrefixes as $prefix) {
                    if (str_starts_with($child, $prefix)) {
                        continue 2;
                    }
                }

                foreach ($this->walkFiles($path, $child) as $entry) {
                    yield $entry;
                }

                continue;
            }

            if (! $item->isFile()) {
                continue;
            }

            yield [$path, $child];
        }
    }

    /**
     * Directories that are vault archives, in-flight work or pure scratch —
     * archiving them would nest a backup inside a backup, or capture a restore
     * while it is still writing.
     *
     * @return array<int, string>
     */
    public function excludedDirectories(): array
    {
        return [
            'storage/app/private/backups',
            'storage/app/private/chunked-uploads',
            'storage/app/private/tmp',
            'storage/app/private/tmp-csv-tests',
            'storage/app/livewire-tmp',
            'storage/app/private/livewire-tmp',
            'storage/app/livewire-tmp-tests',
            'storage/app/platform_temp_snapshots',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function excludedDirectoryPrefixes(): array
    {
        return ['storage/app/restore_platform_temp_'];
    }

    /**
     * Path of an absolute file/dir relative to the app root, or null when it
     * lives outside the app (nothing outside the app belongs in an archive).
     */
    public function relativeToBase(string $absolute): ?string
    {
        $path = str_replace('\\', '/', $absolute);
        $base = rtrim(str_replace('\\', '/', base_path()), '/').'/';

        if (! str_starts_with($path, $base)) {
            return null;
        }

        return trim(substr($path, strlen($base)), '/');
    }

    /**
     * @return array<int,int>|null
     */
    public function normalizeSchools(int|array|null $schools): ?array
    {
        if ($schools === null) {
            return null;
        }

        $ids = is_array($schools) ? $schools : [$schools];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        return $ids === [] ? null : $ids;
    }

    /**
     * Stream the SQL dump straight to disk, one row at a time, so that neither
     * the whole table nor the whole script ever lives in PHP memory.
     *
     * @param  array<int,int>|null  $schoolIds
     */
    private function writeDatabaseDump(string $sqlFile, ?array $schoolIds): void
    {
        $handle = fopen($sqlFile, 'w');

        if ($handle === false) {
            throw new Exception('Unable to open the temporary SQL file for writing.');
        }

        $skipTables = ['platform_backups', 'platform_restore_logs', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'];

        fwrite($handle, "-- Kairo CORE recovery snapshot\n");
        fwrite($handle, '-- Generated: '.now()->toIso8601String()."\n");
        fwrite($handle, $schoolIds === null
            ? "-- Scope: whole platform\n\n"
            : '-- Scope: tenant(s) '.implode(',', $schoolIds)."\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");

        if ($schoolIds === null) {
            foreach ($this->allTables() as $table) {
                if (in_array($table, $skipTables, true)) {
                    continue;
                }
                $this->dumpStructure($handle, $table);
                $this->dumpRows($handle, $table, null);
            }
        } else {
            $tenantTables = $this->tenantTables();
            $userIds = DB::table('users')->whereIn('school_id', $schoolIds)->pluck('id')->all();
            $userList = empty($userIds) ? '0' : implode(',', array_map('intval', $userIds));
            $schoolList = implode(',', $schoolIds);

            foreach ($this->allTables() as $table) {
                if (in_array($table, $skipTables, true)) {
                    continue;
                }

                if ($table === 'schools') {
                    $where = "id IN ({$schoolList})";
                } elseif (in_array($table, $tenantTables, true)) {
                    $where = "school_id IN ({$schoolList})";
                } elseif (in_array($table, ['model_has_roles', 'model_has_permissions'], true)) {
                    $where = "model_id IN ({$userList})";
                } else {
                    continue;
                }

                // Scoped replace: never drop the table, only clear this tenant's rows.
                fwrite($handle, "DELETE FROM `{$table}` WHERE {$where};\n");
                $this->dumpRows($handle, $table, $where);
            }
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
    }

    private function dumpStructure($handle, string $table): void
    {
        $create = DB::select("SHOW CREATE TABLE `{$table}`");
        if (! empty($create)) {
            $row = (array) $create[0];
            $ddl = $row['Create Table'] ?? array_values($row)[1] ?? null;
            if ($ddl) {
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n{$ddl};\n");
            }
        }
    }

    /**
     * @param  string|null  $where  raw WHERE clause for tenant scoping, null = all rows
     */
    private function dumpRows($handle, string $table, ?string $where): void
    {
        $query = DB::table($table)->when($where !== null, fn ($q) => $q->whereRaw($where));

        if (Schema::hasColumn($table, 'id')) {
            // Chunked reads release each batch, so memory stays flat even when a
            // table holds hundreds of thousands of rows.
            foreach ($query->orderBy('id')->lazyById(1000, 'id') as $row) {
                fwrite($handle, $this->insertRow($table, (array) $row));
            }
        } else {
            foreach ($query->cursor() as $row) {
                fwrite($handle, $this->insertRow($table, (array) $row));
            }
        }
    }

    /**
     * @return array<int,string>
     */
    private function tenantTables(): array
    {
        // information_schema columns come back with UPPERCASE names (TABLE_NAME)
        // on MySQL 8+, so always alias explicitly.
        return array_column(DB::select(
            'SELECT TABLE_NAME AS table_name
             FROM information_schema.columns
             WHERE table_schema = ? AND column_name = ?',
            [DB::getDatabaseName(), 'school_id']
        ), 'table_name');
    }

    /**
     * @return array<int,string>
     */
    private function allTables(): array
    {
        return array_column(DB::select(
            'SELECT TABLE_NAME AS table_name
             FROM information_schema.tables
             WHERE table_schema = ? AND table_type = ?',
            [DB::getDatabaseName(), 'BASE TABLE']
        ), 'table_name');
    }

    private function insertRow(string $table, array $row): string
    {
        $keys = array_map(fn ($k) => "`{$k}`", array_keys($row));
        $values = array_map(function ($v) {
            return is_null($v) ? 'NULL' : DB::getPdo()->quote((string) $v);
        }, array_values($row));

        return "INSERT INTO `{$table}` (".implode(', ', $keys).') VALUES ('.implode(', ', $values).");\n";
    }
}
