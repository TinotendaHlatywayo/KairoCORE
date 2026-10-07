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
            $this->runSqlStream($stream);
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            fclose($stream);
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
                'error_details' => $e->getMessage(),
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
}