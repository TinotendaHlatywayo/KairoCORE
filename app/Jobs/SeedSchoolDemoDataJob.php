<?php

namespace App\Jobs;

use App\Models\School;
use App\Services\DummyDataSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Seeds a school's demonstration dataset in the background AFTER a super admin
 * has approved the institution. Running it at approval time (rather than during
 * registration) means the applicant is not blocked while signing up, and there
 * is typically a gap between approval and the applicant activating their
 * account — enough time for the (slow) demo dataset to finish generating.
 *
 * The school becomes usable immediately regardless; demo data simply "appears"
 * once seeding completes. Progress and outcome are recorded on the school row
 * (seed_status / seeded_at / seed_error) and surfaced in the admin panel.
 */
class SeedSchoolDemoDataJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $backoff = 10;

    public int $timeout = 900;

    public function __construct(public int $schoolId) {}

    public function handle(): void
    {
        $school = School::query()->withoutGlobalScopes()->find($this->schoolId);

        if (! $school || ! $school->has_dummy_data) {
            return;
        }

        if (in_array($school->seed_status, ['seeded', 'seeding'], true)) {
            Log::info('SeedSchoolDemoDataJob skipped (already seeded/seeding).', [
                'school_id' => $this->schoolId,
                'seed_status' => $school->seed_status,
            ]);

            return;
        }

        $school->forceFill([
            'seed_status' => 'seeding',
            'seed_error' => null,
        ])->save();

        // Provide a real tenant context so tenant-scoped models behave correctly
        // inside this queue worker (no HTTP request is in scope).
        app()->instance('current_tenant', $school);

        try {
            $result = app(DummyDataSeeder::class)->seed(
                $school->id,
                fn (string $message) => Log::info("SeedSchoolDemoDataJob: {$message}", [
                    'school_id' => $this->schoolId,
                ])
            );

            $school->forceFill([
                'seed_status' => 'seeded',
                'seeded_at' => now(),
                'seed_error' => null,
            ])->save();

            Log::info('SeedSchoolDemoDataJob completed for school.', [
                'school_id' => $this->schoolId,
                'students' => $result['students'] ?? 0,
                'sections' => $result['sections'] ?? 0,
                'reports' => $result['reports'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            report($e);

            $school->forceFill([
                'seed_status' => 'failed',
                'seed_error' => mb_substr($e->getMessage(), 0, 1000),
            ])->save();

            Log::error('SeedSchoolDemoDataJob failed.', [
                'school_id' => $this->schoolId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
