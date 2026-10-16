<?php

namespace Modules\Recovery\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            DB::statement("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION';");

            // Read the full backup payload into memory (platform backups are small
            // enough) so we can strip MySQL-5.x DEFAULT clauses on TEXT/BLOB/JSON
            // columns, which MySQL 8.4 rejects with error 1101 and cannot be
            // bypassed by sql_mode alone.
            $sql = '';
            while (! feof($stream)) {
                $sql .= fread($stream, 65536);
            }
            fclose($stream);

            $sql = preg_replace(
                "/(TEXT|LONGTEXT|MEDIUMTEXT|TINYTEXT|BLOB|LONGBLOB|MEDIUMBLOB|TINYBLOB|JSON)\b[^,()]*?\s+DEFAULT\s+(?:'[^']*'|\"[^\"]*\"|[^,)]*)/i",
                '$1',
                $sql
            );

            $this->runSqlString($sql);

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            DB::statement("SET SESSION sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';");
            $zip->close();

            @unlink($localZip);
            @rmdir($tempPath);

            $log->update(['status' => 'completed']);

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
                'error_details' => mb_substr($e->getMessage(), 0, 255),
            ]);
            throw $e;
        }
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
            $batch[] = trim($sql);
        }

        $flush();
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