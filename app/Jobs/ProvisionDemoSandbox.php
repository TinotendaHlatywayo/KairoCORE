<?php

namespace App\Jobs;

use App\Models\School;
use App\Services\DemoSandboxService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class ProvisionDemoSandbox implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public int $uniqueFor = 600;

    public function __construct(public int $schoolId, public string $sessionId) {}

    public function uniqueId(): string
    {
        return 'demo-sandbox-provision-'.$this->schoolId;
    }

    public function handle(DemoSandboxService $sandboxes): void
    {
        $school = School::withoutGlobalScopes()->find($this->schoolId);

        if (! $school || $school->demo_session_id !== $this->sessionId) {
            return;
        }

        if ($school->pool_status === 'claimed' && $school->has_dummy_data) {
            return;
        }

        try {
            $sandboxes->seed($school);

            $school->forceFill([
                'pool_status' => 'claimed',
                'seed_status' => 'ready',
                'seed_error' => null,
                'seeded_at' => now(),
                'expires_at' => now()->addMinutes((int) config('demo.sandbox.idle_minutes', 120)),
                'hard_expires_at' => now()->addHours((int) config('demo.sandbox.hard_lifetime_hours', 8)),
                'demo_last_activity_at' => now(),
            ])->saveQuietly();
        } catch (Throwable $e) {
            $school->forceFill([
                'pool_status' => 'failed',
                'seed_status' => 'failed',
                'seed_error' => mb_substr($e->getMessage(), 0, 500),
            ])->saveQuietly();

            throw $e;
        }
    }
}
