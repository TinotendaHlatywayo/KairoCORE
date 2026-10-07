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
     * @param  array<int,int>|null  $schoolIds
     */
    public function generate(PlatformBackup $backupRecord, ?array $schoolIds): PlatformBackup
    {
        // Streaming the dump keeps memory flat regardless of table size, but a
        // large platform can still take a while — never let PHP kill it midway.
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

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
            $zip->addFromString('backup_meta.json', json_encode([
                'scope' => $scope,
                'school_id' => $backupRecord->school_id,
                'school_ids' => $schoolIds,
                'notes' => $backupRecord->notes,
                'generated_at' => now()->toIso8601String(),
                'app_version' => config('app.version', 'unknown'),
            ], JSON_PRETTY_PRINT));
            $zip->close();

            $size = filesize($zipFile);
            $checksum = hash_file('sha256', $zipFile);

            // Persist into the same local disk the vault reads from. put() with
            // a stream copies from disk rather than buffering the archive in PHP.
            Storage::disk('local')->put("backups/{$fileName}", fopen($zipFile, 'r'));
            File::deleteDirectory($workDir);

            $backupRecord->update([
                'size_bytes' => $size,
                'checksum' => $checksum,
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
        return DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())
            ->where('column_name', 'school_id')
            ->pluck('table_name')
            ->all();
    }

    /**
     * @return array<int,string>
     */
    private function allTables(): array
    {
        return DB::table('information_schema.tables')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_type', 'BASE TABLE')
            ->pluck('table_name')
            ->all();
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