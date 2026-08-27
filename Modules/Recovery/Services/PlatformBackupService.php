<?php

namespace Modules\Recovery\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Recovery\Models\PlatformBackup;
use ZipArchive;

class PlatformBackupService
{
    public function executeFullBackup(): PlatformBackup
    {
        $timestamp = now()->format('Y-m-d_His');
        $fileName = "Kairo CORE_PLATFORM_SNAP_{$timestamp}.zip";
        $tempPath = storage_path('app/platform_temp_snapshots');

        if (! file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        $backupRecord = PlatformBackup::create([
            'filename' => $fileName,
            'disk' => 'local',
            'status' => 'pending',
        ]);

        try {
            $zip = new ZipArchive;
            $zipFile = "{$tempPath}/{$fileName}";

            if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('Unable to write temporary backup zip folder.');
            }

            // Generate full database snapshot
            $sqlContent = $this->compileFullSchemaAndData();
            $zip->addFromString('backup_payload.sql', $sqlContent);
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

    private function compileFullSchemaAndData(): string
    {
        $sql = "-- Kairo CORE Global Enterprise Database Snapshot\n";
        $sql .= '-- Timestamp: '.now()->toDateTimeString()."\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $tableKey = "Tables_in_{$dbName}";

        foreach ($tables as $tableRow) {
            $table = $tableRow->$tableKey;

            // Skip auditing tables to avoid circular bloat
            if (in_array($table, ['platform_backups', 'platform_restore_logs'])) {
                continue;
            }

            // Dump table schema structure
            $createTableStmt = DB::select("SHOW CREATE TABLE `{$table}`");
            if (! empty($createTableStmt)) {
                $createSqlKey = 'Create Table';
                $sql .= "-- TABLE STRUCTURE: {$table}\n";
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= $createTableStmt[0]->$createSqlKey.";\n\n";
            }

            // Dump table data rows
            $rows = DB::table($table)->get();
            if ($rows->isEmpty()) {
                continue;
            }

            $sql .= "-- TABLE DATA: {$table}\n";
            foreach ($rows as $row) {
                $arrayRow = (array) $row;
                $keys = array_map(fn ($k) => "`{$k}`", array_keys($arrayRow));
                $values = array_map(function ($v) {
                    if (is_null($v)) {
                        return 'NULL';
                    }

                    return DB::getPdo()->quote($v);
                }, array_values($arrayRow));

                $sql .= "INSERT INTO `{$table}` (".implode(', ', $keys).') VALUES ('.implode(', ', $values).");\n";
            }
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $sql;
    }
}
