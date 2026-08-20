<?php

namespace App\Jobs\SaaS;

use App\Models\School;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Modules\SaaS\Models\SaaSSubscription;

class ProcessAutoDeactivationCron implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = Carbon::today();

        // Select all subscriptions where the grace period has ended or next payment is overdue past the allowed threshold
        $overdueSubscriptions = SaaSSubscription::whereIn('status', ['active', 'grace_period', 'trialing'])
            ->get();

        foreach ($overdueSubscriptions as $sub) {
            $nextPayDate = Carbon::parse($sub->next_payment_date);
            $limitDays = $sub->auto_deactivate_after_days;
            $deactivationBoundary = $nextPayDate->addDays($limitDays);

            if ($today->greaterThanOrEqualTo($deactivationBoundary)) {
                DB::transaction(function () use ($sub) {
                    // Update subscription status to suspended
                    $sub->update([
                        'status' => 'suspended',
                    ]);

                    // Block school-level access dynamically
                    School::where('id', $sub->school_id)->update([
                        'status' => 'suspended',
                    ]);
                });
            }
        }
    }
}
