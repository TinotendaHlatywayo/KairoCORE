<?php

namespace Modules\Recovery\Services;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Modules\Recovery\Models\PlatformBackup;
use Modules\Recovery\Models\PlatformRestoreLog;
use ZipArchive;

class PlatformRestoreService
{
    public function executePlatformRestore(int $logId): void
    {
        @set_time_limit(0);

        $log = PlatformRestoreLog::with('backup')->find($logId);
        if (! $log) {
            throw new Exception('Platform restore log not found.');
        }

        $log->update(['status' => 'processing']);
        $backup = $log->backup;
        $log->update(['scope' => $backup->scope ?? 'system']);

        $filePath = "backups/{$backup->filename}";
        if (! Storage::disk($backup->disk)->exists($filePath)) {
            $log->update([
                'status' => 'failed',
                'error_details' => 'Recovery file missing from disk.',
            ]);

            return;
        }

        $tempPath = storage_path("app/restore_platform_temp_{$log->id}");
        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        $localZip = "{$tempPath}/archive.zip";

        try {
            // Stream the archive to disk instead of loading it all into memory.
            $in = Storage::disk($backup->disk)->readStream($filePath);
            if (! $in) {
                throw new Exception('Unable to read the recovery archive.');
            }
            $out = fopen($localZip, 'wb');
            stream_copy_to_stream($in, $out);
            fclose($out);
            if (is_resource($in)) {
                fclose($in);
            }

            $zip = new ZipArchive;
            if ($zip->open($localZip) !== true) {
                throw new Exception('Unable to open restoration file.');
            }

            $stream = $zip->getStream('backup_payload.sql');
            if (! $stream) {
                $zip->close();
                throw new Exception('Archive does not contain a backup payload.');
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::statement("SET SESSION sql_mode = '';");
            $this->runSqlStream($stream);
            fclose($stream);
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            DB::statement("SET SESSION sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';");

            // Rows are only half the restore: every uploaded photo, logo,
            // favicon and library file lives on disk, and an archive that
            // carries them must put them back or the school returns to an
            // empty media library. Archives from before this was added simply
            // have no `files/` entries and restore exactly as they used to.
            $fileStats = $this->restoreStorageFiles($zip);
            $zip->close();

            @unlink($localZip);
            @rmdir($tempPath);

            $this->repairStorageSymlink();
            $this->clearApplicationCaches();
            $this->fixOwnership([storage_path('app')]);

            $log->update([
                'status' => 'completed',
                'summary' => $this->buildSummary($log->summary, $fileStats),
            ]);

        } catch (Exception $e) {
            if (isset($stream) && is_resource($stream)) {
                @fclose($stream);
            }
            if (file_exists($tempPath)) {
                @array_map('unlink', glob("$tempPath/*"));
                @rmdir($tempPath);
            }
            $log->update([
                'status' => 'failed',
                'error_details' => mb_substr($e->getMessage(), 0, 5000),
            ]);
            throw $e;
        }
    }

    /**
     * Put every `files/public/**` entry from an archive straight into the web
     * root.
     *
     * This runs in the web request rather than on the worker: the worker
     * container has its own image-layer copy of public/, so a file written there
     * would never reach Apache. Storage is a shared volume, public/ is not.
     *
     * Files land before the database does (the worker owns the SQL phase and the
     * two cannot be ordered across containers), so a failed restore can leave
     * assets ahead of the rows — the operator is told what was replaced.
     *
     * @return array{files: int, bytes: int, skipped: int}
     */
    public function restorePublicAssets(PlatformBackup $backup): array
    {
        // A large web root takes real time; finish even if the tab navigates off.
        @set_time_limit(0);
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }

        $archivePath = Storage::disk($backup->disk)->path("backups/{$backup->filename}");
        if (! is_file($archivePath)) {
            throw new Exception('Recovery file missing from disk.');
        }

        $zip = new ZipArchive;
        if ($zip->open($archivePath, ZipArchive::RDONLY) !== true) {
            throw new Exception('Unable to open restoration file.');
        }

        try {
            return $this->extractEntries($zip, 'files/public/', public_path());
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array{files: int, bytes: int, skipped: int}
     */
    public function restoreStorageFiles(ZipArchive $zip): array
    {
        $stats = $this->extractEntries($zip, 'files/storage/', storage_path());

        return $stats;
    }

    /**
     * @return array{files: int, bytes: int, skipped: int}
     */
    public function extractEntries(ZipArchive $zip, string $prefix, string $root): array
    {
        $root = rtrim(str_replace('\\', '/', $root), '/');
        $files = 0;
        $bytes = 0;
        $skipped = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if (! is_string($name) || ! str_starts_with($name, $prefix) || str_ends_with($name, '/')) {
                continue;
            }

            $relative = substr($name, strlen($prefix));
            $target = $this->resolveTarget($root, $relative);

            if ($target === null) {
                $skipped++;

                continue;
            }

            $stream = $zip->getStream($name);

            if ($stream === false) {
                $skipped++;

                continue;
            }

            File::ensureDirectoryExists(dirname($target), 0755, true);

            // A root-owned file left by an older run is not writable by the
            // worker — replace it instead of failing the whole restore.
            $handle = @fopen($target, 'wb');

            if ($handle === false && file_exists($target)) {
                @unlink($target);
                $handle = @fopen($target, 'wb');
            }

            if ($handle === false) {
                fclose($stream);
                $skipped++;

                continue;
            }

            stream_copy_to_stream($stream, $handle);
            fclose($handle);
            fclose($stream);

            $files++;
            $bytes += (int) $zip->statName($name)['size'];
        }

        return ['files' => $files, 'bytes' => $bytes, 'skipped' => $skipped];
    }

    /**
     * Map an archive-relative path onto a real file under $root, refusing
     * anything that would escape it. `..`, absolute paths and Windows drive
     * letters are all rejected before a single byte is written.
     */
    public function resolveTarget(string $root, string $relative): ?string
    {
        $relative = str_replace('\\', '/', $relative);

        if ($relative === '' || str_starts_with($relative, '/') || preg_match('#^[a-zA-Z]:#', $relative)) {
            return null;
        }

        foreach (explode('/', $relative) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }
        }

        $target = $root.'/'.str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $normalized = str_replace('\\', '/', $target);

        if (! str_starts_with($normalized, $root.'/')) {
            return null;
        }

        return $target;
    }

    /**
     * public/storage must point at storage/app/public or uploads are invisible
     * once the symlink is gone (a restore from a bare checkout can be the very
     * thing that removed it).
     */
    public function repairStorageSymlink(): void
    {
        $link = public_path('storage');

        if (is_link($link) || file_exists($link)) {
            return;
        }

        if (! function_exists('symlink')) {
            return;
        }

        @symlink(storage_path('app/public'), $link);
    }

    /**
     * Config, routes and views are cached from the shell at boot; restored rows
     * must not keep showing the pre-restore world. Sessions live in the database
     * here, so clearing the file/permission caches cannot log anyone out.
     */
    public function clearApplicationCaches(): void
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('permission:cache-reset');
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * The queue worker runs as root, so freshly extracted files would be
     * root-owned and unwritable by the web user. Only worth running when we
     * actually have the privilege.
     *
     * @param  array<int, string>  $roots
     */
    public function fixOwnership(array $roots): void
    {
        if (! function_exists('exec') || (int) @trim((string) @shell_exec('id -u 2>/dev/null')) !== 0) {
            return;
        }

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }

            @exec('chown -R www-data:www-data '.escapeshellarg($root).' 2>/dev/null');
        }
    }

    /**
     * @param  array{files: int, bytes: int, skipped: int}  $stats
     */
    public function buildSummary(?string $publicSummary, array $stats): string
    {
        $parts = [];

        if (blank($publicSummary) === false) {
            $parts[] = trim($publicSummary);
        }

        if ($stats['files'] > 0) {
            $parts[] = 'Media + uploads: '.$stats['files'].' files ('.$this->formatBytes($stats['bytes']).') restored';
        }

        $summary = 'Database restored'.($parts === [] ? '' : ' · '.implode(' · ', $parts));

        if ($stats['skipped'] > 0) {
            $summary .= ' · '.$stats['skipped'].' files skipped';
        }

        return mb_substr($summary, 0, 255);
    }

    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;
        $value = (float) $bytes;

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return round($value, 1).' '.$units[$index];
    }

    /**
     * Reads a .sql stream and executes it in batches without ever holding the
     * whole file in memory. Statement boundaries are detected outside quoted
     * string literals so values containing semicolons or newlines are safe.
     */
    private function runSqlStream($stream): void
    {
        $batch = [];
        $batchBytes = 0;
        $sql = '';
        $inString = false;
        $escapeNext = false;

        $flush = function () use (&$batch, &$batchBytes) {
            if ($batch === []) {
                return;
            }
            DB::unprepared(implode('', $batch));
            $batch = [];
            $batchBytes = 0;
        };

        while (! feof($stream)) {
            $chunk = fread($stream, 65536);
            $n = strlen($chunk);

            for ($i = 0; $i < $n; $i++) {
                $ch = $chunk[$i];

                if ($escapeNext) {
                    $sql .= $ch;
                    $escapeNext = false;

                    continue;
                }

                if ($inString) {
                    if ($ch === '\\') {
                        $sql .= $ch;
                        $escapeNext = true;

                        continue;
                    }
                    if ($ch === "'") {
                        $inString = false;
                    }
                    $sql .= $ch;

                    continue;
                }

                if ($ch === "'") {
                    $inString = true;
                    $sql .= $ch;

                    continue;
                }

                if ($ch === ';') {
                    $sql .= $ch;
                    $statement = trim($sql);
                    $sql = '';

                    if ($statement !== '') {
                        $statement = $this->sanitizeStructureStatement($statement);
                        $batch[] = $statement."\n";
                        $batchBytes += strlen($statement);

                        if ($batchBytes >= 262144) {
                            $flush();
                        }
                    }

                    continue;
                }

                $sql .= $ch;
            }
        }

        if (trim($sql) !== '') {
            $batch[] = $this->sanitizeStructureStatement(trim($sql));
        }

        $flush();
    }

    /**
     * MariaDB emits DEFAULT clauses on TEXT/BLOB/JSON/GEOMETRY columns in
     * SHOW CREATE TABLE output, but MySQL 8.x rejects them with error 1101
     * ("can't have a default value") regardless of sql_mode. Backups produced
     * on MariaDB therefore fail when restored into MySQL 8.4.
     *
     * Only CREATE TABLE statements are rewritten (never INSERTs), so data
     * values that happen to contain the word "DEFAULT" are never corrupted.
     */
    private function sanitizeStructureStatement(string $statement): string
    {
        if (! preg_match('/^\s*CREATE\s+TABLE/i', $statement)) {
            return $statement;
        }

        return preg_replace(
            '/\b(TINYBLOB|MEDIUMBLOB|LONGBLOB|BLOB|TINYTEXT|MEDIUMTEXT|LONGTEXT|TEXT'
            .'|JSON|GEOMETRYCOLLECTION|MULTIPOLYGON|MULTILINESTRING|MULTIPOINT'
            .'|POLYGON|LINESTRING|POINT|GEOMETRY)\b'
            .'((?:\s+(?:CHARACTER\s+SET|COLLATE)\s+[\w]+|\s+NOT\s+NULL|\s+NULL)*)'
            .'\s+DEFAULT\s+'
            .'(?:\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*"|\([^)]*\)|[^\s,)]+)'
            .'/i',
            '$1$2',
            $statement
        ) ?? $statement;
    }

    /**
     * Execute a full SQL string statement-by-statement, respecting quoted string
     * literals so semicolons inside values do not split statements prematurely.
     */
    private function runSqlString(string $sql): void
    {
        $statements = [];
        $current = '';
        $inString = false;
        $escapeNext = false;
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];

            if ($escapeNext) {
                $current .= $ch;
                $escapeNext = false;

                continue;
            }

            if ($inString) {
                if ($ch === '\\') {
                    $escapeNext = true;
                    $current .= $ch;

                    continue;
                }
                if ($ch === "'") {
                    $inString = false;
                }
                $current .= $ch;

                continue;
            }

            if ($ch === "'") {
                $inString = true;
                $current .= $ch;

                continue;
            }

            if ($ch === ';') {
                $statement = trim($current);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $current = '';

                continue;
            }

            $current .= $ch;
        }

        $statement = trim($current);
        if ($statement !== '') {
            $statements[] = $statement;
        }

        foreach ($statements as $statement) {
            DB::unprepared($statement);
        }
    }
}
