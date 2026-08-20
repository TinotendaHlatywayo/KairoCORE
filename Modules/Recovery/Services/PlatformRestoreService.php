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
        $log = PlatformRestoreLog::with('backup')->find($logId);
        if (! $log) {
            throw new Exception('Platform restore log not found.');
        }

        $log->update(['status' => 'processing']);
        $backup = $log->backup;

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

        try {
            $fileData = Storage::disk($backup->disk)->get($filePath);
            $localZip = "{$tempPath}/archive.zip";
            file_put_contents($localZip, $fileData);

            $zip = new ZipArchive;
            if ($zip->open($localZip) !== true) {
                throw new Exception('Unable to open restoration file.');
            }

            DB::beginTransaction();

            $sqlContent = $zip->getFromName('backup_payload.sql');
            if ($sqlContent) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                // Parse queries by looking for end-of-line semicolons
                $queries = array_filter(explode(";\n", $sqlContent));
                foreach ($queries as $query) {
                    $trimmed = trim($query);
                    if (! empty($trimmed)) {
                        DB::unprepared($trimmed);
                    }
                }

                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }

            DB::commit();
            $zip->close();

            @unlink($localZip);
            @rmdir($tempPath);

            $log->update(['status' => 'completed']);

        } catch (Exception $e) {
            DB::rollBack();
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
}
