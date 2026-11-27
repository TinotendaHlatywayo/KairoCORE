<?php

namespace App\Console\Commands;

use App\Services\DemoSandboxService;
use Illuminate\Console\Command;

final class CleanupDemoSandboxes extends Command
{
    protected $signature = 'demo:cleanup-sandboxes';

    protected $description = 'Delete expired temporary demo sandbox tenants.';

    public function handle(DemoSandboxService $sandboxes): int
    {
        $deleted = $sandboxes->deleteExpired();

        if (config('demo.sandbox.pool_enabled', false)) {
            $sandboxes->ensurePool();
        }

        $this->info("Deleted {$deleted} expired demo sandbox(es).");

        return self::SUCCESS;
    }
}
