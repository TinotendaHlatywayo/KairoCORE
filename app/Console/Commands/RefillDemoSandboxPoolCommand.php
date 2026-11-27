<?php

namespace App\Console\Commands;

use App\Jobs\RefillDemoSandboxPool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

final class RefillDemoSandboxPoolCommand extends Command
{
    protected $signature = 'demo:sandbox:refill-pool';

    protected $description = 'Queue a demo sandbox pool refill on the dedicated pool queue.';

    public function handle(): int
    {
        if (! config('demo.sandbox.pool_enabled', false)) {
            return self::SUCCESS;
        }

        $interval = max(1, (int) config('demo.sandbox.refill_interval_seconds', 60));
        $cacheKey = 'demo:sandbox:last-refill-dispatch';
        $last = Cache::get($cacheKey);
        $now = now()->timestamp;

        if ($last !== null && $now - (int) $last < $interval) {
            return self::SUCCESS;
        }

        Cache::put($cacheKey, $now, now()->addMinutes(10));

        RefillDemoSandboxPool::dispatch()
            ->onQueue((string) config('demo.sandbox.refill_queue', 'demo-pool'));

        return self::SUCCESS;
    }
}
