<?php

namespace App\Jobs;

use App\Models\School;
use App\Models\User;
use App\Services\DummyDataSeeder;
use App\Services\SchoolRegistrationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Completes the heavy parts of a school registration in the background so the
 * public registration wizard responds immediately.
 *
 * The wizard only synchronously persists the core pending-school + admin rows
 * (fast, atomic, keeps the subdomain unique-index safe). Once submitted, this
 * job fans out async to:
 *   1. Optionally seed demo data (hundreds of records — the slow part).
 *   2. Notify every platform super administrator (in-app + email) about the
 *      new application, who then approves it and sets a real admin password.
 */
class ProcessSchoolRegistrationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public int $timeout = 600;

    public function __construct(
        public int $schoolId,
        public bool $hasDummyData,
    ) {}

    public function handle(): void
    {
        $school = School::query()->find($this->schoolId);

        if (! $school) {
            Log::warning('ProcessSchoolRegistrationJob: school not found.', [
                'school_id' => $this->schoolId,
            ]);

            return;
        }

        if ($this->hasDummyData) {
            try {
                app(DummyDataSeeder::class)->seed($school->id);
            } catch (\Throwable $e) {
                report($e);
                Log::error('ProcessSchoolRegistrationJob: demo data seeding failed.', [
                    'school_id' => $this->schoolId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $contact = User::query()
            ->where('school_id', $this->schoolId)
            ->orderBy('id')
            ->first();

        $mailRecipients = [];
        try {
            $mailRecipients = app(SchoolRegistrationService::class)
                ->notifySuperAdmin($school, $contact);
        } catch (\Throwable $e) {
            report($e);
            Log::error('ProcessSchoolRegistrationJob: super-admin notification failed.', [
                'school_id' => $this->schoolId,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('ProcessSchoolRegistrationJob completed.', [
            'school_id' => $this->schoolId,
            'has_dummy_data' => $this->hasDummyData,
            'mail_recipients' => count($mailRecipients),
        ]);
    }
}
