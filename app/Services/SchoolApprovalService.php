<?php

namespace App\Services;

use App\Jobs\SeedSchoolDemoDataJob;
use App\Models\School;
use App\Models\User;

/**
 * Single source of truth for approving a newly registered school.
 *
 * Issues a single-use activation token (emailed to the pending administrator),
 * stamps the approval metadata, starts the trial period and, when the applicant
 * opted in, kicks off background demo-data seeding. Shared by the schools table
 * action and the school edit page so the behaviour is identical everywhere.
 */
class SchoolApprovalService
{
    /**
     * @return array{admin_user: User|null, token: string|null, seed_dispatched: bool}
     */
    public function approve(School $school): array
    {
        $adminUser = $school->users()
            ->where('requested_role', 'administrator')
            ->where('account_status', User::STATUS_PENDING)
            ->first() ?? $school->users()->where('account_status', User::STATUS_PENDING)->first();

        $token = null;
        if ($adminUser) {
            $token = app(AccountActivationService::class)->issueAndSend($adminUser);

            $adminUser->forceFill([
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ])->save();
        }

        $school->update([
            'status' => 'pending', // Still pending — becomes 'active' when the contact completes activation
            'trial_ends_at' => now()->addMonths(3),
        ]);

        $seedDispatched = false;
        if ($school->has_dummy_data && $school->seed_status !== 'seeded') {
            SeedSchoolDemoDataJob::dispatch($school->id);
            $seedDispatched = true;
        }

        return [
            'admin_user' => $adminUser,
            'token' => $token,
            'seed_dispatched' => $seedDispatched,
        ];
    }
}