<?php

namespace Modules\Recovery\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Recovery\Models\PlatformBackup;
use Modules\Recovery\Models\PlatformRestoreLog;
use Modules\Recovery\Services\PlatformBackupService;

/**
 * Builds a recovery archive on the queue worker.
 *
 * Dumping and zipping a large tenant (or the whole platform) can take minutes
 * and hundreds of megabytes — far beyond a single web request. Running it on
 * the worker is why the vault no longer 500s on big tenants.
 */
class GeneratePlatformBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    /**
     * @param  array<int,int>|null  $schoolIds  null = whole platform
     */
    public function __construct(public int $backupId, public ?array $schoolIds = null) {}

    public function handle(PlatformBackupService $service): void
    {
        $record = PlatformBackup::find($this->backupId);

        if (! $record) {
            return;
        }

        $service->generate($record, $this->schoolIds);
    }

    public function failed(\Throwable $e): void
    {
        PlatformBackup::where('id', $this->backupId)->update([
            'status' => 'failed',
            'error_log' => $e->getMessage(),
        ]);
    }
}