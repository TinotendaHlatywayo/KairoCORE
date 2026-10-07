<?php

namespace Modules\Recovery\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Recovery\Models\PlatformRestoreLog;
use Modules\Recovery\Services\PlatformRestoreService;

/**
 * Restores a recovery archive on the queue worker.
 */
class RestorePlatformBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(public int $restoreLogId) {}

    public function handle(PlatformRestoreService $service): void
    {
        $service->executePlatformRestore($this->restoreLogId);
    }

    public function failed(\Throwable $e): void
    {
        PlatformRestoreLog::where('id', $this->restoreLogId)->update([
            'status' => 'failed',
            'error_details' => $e->getMessage(),
        ]);
    }
}