<?php

namespace Modules\SaaS\Services;

use Modules\SaaS\Models\SchoolSubscription;

class SaaSBillingEngine
{
    /**
     * Process cron sweep: identifies trials that have expired and suspends access.
     */
    public static function enforceTrialExpirations(): array
    {
        $expiredSubscriptions = SchoolSubscription::where('status', 'trial')
            ->where('trial_ends_at', '<', now())
            ->get();

        $processedCount = 0;

        foreach ($expiredSubscriptions as $sub) {
            $sub->status = 'expired';
            $sub->save();

            $school = $sub->school;
            if ($school) {
                $school->status = 'suspended';
                $school->save();
            }

            $processedCount++;
        }

        return [
            'status' => 'success',
            'processed_count' => $processedCount,
        ];
    }
}
