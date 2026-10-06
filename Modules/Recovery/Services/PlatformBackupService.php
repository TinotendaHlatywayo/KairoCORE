<?php

namespace Modules\Recovery\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Recovery\Models\PlatformBackup;
use ZipArchive;

class PlatformBackupService
{
    public function executeFullBackup(int|array|null $schools = null): PlatformBackup
    {
        $timestamp = now()->format('Y-m-d_His');
        $schoolIds = is_array($schools) ? $schools : ($schools ? [$schools] : null);
        $scope = empty($schoolIds) ? 'system' : (count($schoolIds) === 1 ? 'tenant' : 'selected_tenants');
        
        $label = match($scope) {
            'system' => 'PLATFORM',
            'tenant' => "TENANT_{$schoolIds[0]}",
            default => 'SELECTED_TENANTS',
        };

        $fileName = "Kairo CORE_{$label}_SNAP_{$timestamp}.zip";
        $tempPath = storage_path('app/platform_temp_snapshots');

        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        $backupRecord = PlatformBackup::create([
            'filename' => $fileName,
            'scope' => $scope === 'selected_tenants' ? 'tenant' : $scope,
            'school_id' => count($schoolIds ?? []) === 1 ? $schoolIds[0] : null,
            'status' => 'pending',
        ]);

        try {
            $zip = new ZipArchive;
            $zipFile = "{$tempPath}/{$fileName}";

            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Unable to write temporary backup zip folder.');
            }

            // Generate full database snapshot
            $sqlContent = $this->compileFullSchemaAndData($schoolIds);
            $zip->addFromString('backup_payload.sql', $sqlContent);
            $zip->addFromString('backup_meta.json', json_encode([
                'scope' => $backupRecord->scope,
                'school_id' => $backupRecord->school_id,
                'school_ids' => $schoolIds,
                'generated_at' => now()->toIso8601String(),
                'app_version' => config('app.version', 'unknown'),
            ], JSON_PRETTY_PRINT));
            $zip->close();

            $size = filesize($zipFile);
            $checksum = hash_file('sha256', $zipFile);

            // Copy file to local backups disk
            $stream = fopen($zipFile, 'r+');
            Storage::disk('local')->put("backups/{$fileName}", $stream);
            fclose($stream);

            @unlink($zipFile);
            @rmdir($tempPath);

            $backupRecord->update([
                'size_bytes' => $size,
                'checksum' => $checksum,
                'status' => 'completed',
                'is_verified' => true,
            ]);

            return $backupRecord;

        } catch (Exception $e) {
            if (file_exists($tempPath)) {
                @array_map('unlink', glob("$tempPath/*"));
                @rmdir($tempPath);
            }
            $backupRecord->update([
                'status' => 'failed',
                'error_log' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function compileFullSchemaAndData(array|int|null $schools = null): string
    {
        $database = DB::getDatabaseName();
        $skipTables = ['platform_backups', 'platform_restore_logs'];
        $schoolIds = is_array($schools) ? $schools : ($schools ? [$schools] : null);

        if (empty($schoolIds)) {
            return $this->compileSystemSnapshot($skipTables);
        }

        return $this->compileTenantSnapshot($database, $schoolIds, $skipTables);
    }

    private function compileSystemSnapshot(array $skipTables): string
    {
        $sql = "-- Full system snapshot\n";
        $sql .= '-- Generated: '.now()->toDateTimeString()."\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";

        foreach ($this->allTables() as $table) {
            if (in_array($table, $skipTables, true)) {
                continue;
            }

            $createTableStmt = DB::select("SHOW CREATE TABLE `{$table}`");
            if (! empty($createTableStmt)) {
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= $createTableStmt[0]->{'Create Table'}.";\n\n";
            }

            foreach (DB::table($table)->get() as $row) {
                $sql .= $this->insertRow($table, (array) $row);
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $sql;
    }

    private function compileTenantSnapshot(string $database, array $schoolIds, array $skipTables): string
    {
        $tenantTables = DB::table('information_schema.columns')
            ->where('table_schema', $database)
            ->where('column_name', 'school_id')
            ->pluck('table_name')
            ->all();

        $userIds = DB::table('users')->whereIn('school_id', $schoolIds)->pluck('id')->all();
        $userList = empty($userIds) ? '0' : implode(',', array_map('intval', $userIds));
        $schoolList = implode(',', array_map('intval', $schoolIds));

        $sql = "-- Tenants snapshot (school_ids: {$schoolList})\n";
        $sql .= '-- Generated: '.now()->toDateTimeString()."\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";

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

            // Scoped delete (never drop the table) then re-insert these tenants' rows only.
            $sql .= "DELETE FROM `{$table}` WHERE {$where};\n";
            foreach (DB::table($table)->whereRaw($where)->get() as $row) {
                $sql .= $this->insertRow($table, (array) $row);
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $sql;
    }

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
            return is_null($v) ? 'NULL' : DB::getPdo()->quote($v);
        }, array_values($row));

        return "INSERT INTO `{$table}` (".implode(', ', $keys).') VALUES ('.implode(', ', $values).");\n";
    }
}
