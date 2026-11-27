<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\DemoSandboxService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class DemoSandboxPoolStatus extends Command
{
    protected $signature = 'demo:sandbox:pool-status {--json}';

    protected $description = 'Report demo sandbox pool capacity and refill configuration.';

    public function handle(DemoSandboxService $sandboxes): int
    {
        $interval = max(1, (int) config('demo.sandbox.refill_interval_seconds', 60));
        $workers = max(1, (int) config('demo.sandbox.refill_workers', 2));
        $loginsPerMinute = max(1, (int) config('demo.sandbox.logins_per_minute', 10));
        $seedMs = DemoSandboxService::$lastSeedDurationMs ?? (int) config('demo.sandbox.last_seed_ms', 7000);

        if ($seedMs <= 0) {
            $seedMs = (int) DB::table('system_settings')
                ->where('group', 'demo')
                ->where('key', 'last_seed_duration_ms')
                ->value('value');
        }

        $seedMs = max(1000, (int) $seedMs);
        $capacityPerWorker = 60000 / $seedMs;
        $capacity = $capacityPerWorker * $workers;

        $counts = School::withoutGlobalScopes()
            ->where('is_demo_sandbox', true)
            ->select('pool_status', DB::raw('COUNT(*) as total'))
            ->groupBy('pool_status')
            ->pluck('total', 'pool_status')
            ->all();

        $payload = [
            'pool_enabled' => (bool) config('demo.sandbox.pool_enabled', false),
            'pool_size' => (int) config('demo.sandbox.pool_size', 5),
            'max_concurrent' => (int) config('demo.sandbox.max_concurrent', 50),
            'refill_queue' => (string) config('demo.sandbox.refill_queue', 'demo-pool'),
            'refill_interval_seconds' => $interval,
            'refill_jobs_per_minute' => round(60 / $interval, 2),
            'configured_workers' => $workers,
            'logins_per_minute' => $loginsPerMinute,
            'last_seed_ms' => $seedMs,
            'estimated_sandboxes_per_minute' => round($capacity, 2),
            'meets_login_rate' => $capacity >= $loginsPerMinute,
            'counts' => [
                'ready' => (int) ($counts['ready'] ?? 0),
                'seeding' => (int) ($counts['seeding'] ?? 0),
                'claimed' => (int) ($counts['claimed'] ?? 0),
            ],
            'active' => $sandboxes->activeCount(),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        foreach ($payload as $key => $value) {
            $this->line($key.': '.json_encode($value, JSON_THROW_ON_ERROR));
        }

        return self::SUCCESS;
    }
}
