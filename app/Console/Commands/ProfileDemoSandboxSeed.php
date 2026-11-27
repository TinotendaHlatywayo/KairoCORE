<?php

namespace App\Console\Commands;

use App\Services\DemoSandboxService;
use App\Support\DemoSeedProfiler;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Queue\Events\JobQueueing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Admin\Models\SystemAuditLog;

class ProfileDemoSandboxSeed extends Command
{
    protected $signature = 'demo:sandbox:profile-seed {--json}';

    protected $description = 'Profile sandbox seeding for query count, timings and side effects';

    public function handle(): int
    {
        config()->set('demo.sandbox.profile_seed', true);
        config()->set('demo.sandbox.pool_enabled', false);
        DemoSeedProfiler::enable();

        $events = [];
        $sideEffects = [
            'mail' => 0,
            'notifications' => 0,
            'http' => 0,
            'jobs' => 0,
        ];

        Event::listen('eloquent.*', function (string $event) use (&$events): void {
            $key = Str::after($event, 'eloquent.');
            $events[$key] = ($events[$key] ?? 0) + 1;
        });

        Event::listen(MessageSending::class, function () use (&$sideEffects): void {
            $sideEffects['mail']++;
        });

        Event::listen(NotificationSending::class, function () use (&$sideEffects): void {
            $sideEffects['notifications']++;
        });

        Event::listen(RequestSending::class, function () use (&$sideEffects): void {
            $sideEffects['http']++;
        });

        Event::listen(JobQueueing::class, function () use (&$sideEffects): void {
            $sideEffects['jobs']++;
        });

        DB::flushQueryLog();
        DB::enableQueryLog();

        $startedAt = microtime(true);
        $claim = app(DemoSandboxService::class)->claim();
        $wallMs = (int) round((microtime(true) - $startedAt) * 1000);
        $schoolId = (int) $claim['school']->id;

        $queries = DB::getQueryLog();
        $queryTypes = [];

        foreach ($queries as $query) {
            $verb = Str::upper(Str::before(ltrim((string) $query['query']), ' '));
            $queryTypes[$verb] = ($queryTypes[$verb] ?? 0) + 1;
        }

        arsort($queryTypes);
        arsort($events);

        ksort($sideEffects);

        $payload = [
            'school_id' => $schoolId,
            'wall_ms' => $wallMs,
            'seed_ms' => DemoSandboxService::$lastSeedDurationMs,
            'query_count' => count($queries),
            'query_types' => $queryTypes,
            'eloquent_events' => $events,
            'side_effect_events' => $sideEffects,
            'audit_rows' => SystemAuditLog::withoutGlobalScopes()->where('school_id', $schoolId)->count(),
            'file_writes' => 0,
            'timings_ms' => DemoSeedProfiler::timings(),
        ];

        app(DemoSandboxService::class)->delete($schoolId);

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        foreach ($payload as $key => $value) {
            $this->line($key.': '.json_encode($value, JSON_THROW_ON_ERROR));
        }

        return self::SUCCESS;
    }
}
