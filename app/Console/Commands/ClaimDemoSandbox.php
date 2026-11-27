<?php

namespace App\Console\Commands;

use App\Services\DemoSandboxService;
use Illuminate\Console\Command;

class ClaimDemoSandbox extends Command
{
    protected $signature = 'demo:sandbox:claim {--json}';

    protected $description = 'Claim an isolated demo sandbox for testing and benchmarking';

    public function handle(): int
    {
        $claim = app(DemoSandboxService::class)->claim();

        $payload = [
            'school_id' => (int) $claim['school']->id,
            'subdomain' => (string) $claim['school']->subdomain,
            'demo_session_id' => (string) $claim['school']->demo_session_id,
            'user_id' => (int) $claim['user']->id,
            'email' => (string) $claim['user']->email,
            'token' => (string) $claim['token'],
            'seed_duration_ms' => DemoSandboxService::$lastSeedDurationMs,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        foreach ($payload as $key => $value) {
            $this->line($key.': '.(string) $value);
        }

        return self::SUCCESS;
    }
}
