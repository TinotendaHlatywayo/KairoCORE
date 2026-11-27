<?php

namespace App\Jobs;

use App\Services\DemoSandboxService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class RefillDemoSandboxPool implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public ?int $tries = 3;

    public int $timeout = 600;

    public function handle(DemoSandboxService $sandboxes): void
    {
        $sandboxes->ensurePool();
    }

    public function retryUntil(): int
    {
        return now()->addMinutes(10)->timestamp;
    }
}
