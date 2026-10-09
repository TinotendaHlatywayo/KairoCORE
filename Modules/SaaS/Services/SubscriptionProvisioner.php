<?php

namespace Modules\SaaS\Services;

use App\Models\School;
use Modules\SaaS\Models\SaaSPlan;
use Modules\SaaS\Models\SaaSSubscription;

/**
 * Makes sure every institution has exactly one subscription row.
 *
 * The SaaS billing tables use a unique index on school_id, so a school can only
 * ever have a single subscription. Historically that row was created lazily the
 * first time the school opened its billing page, which left the platform's
 * "School Subscriptions" screen missing every tenant that had never visited it.
 * This service is the single place that fills the gap.
 */
class SubscriptionProvisioner
{
    /**
     * Return the school's subscription, creating one on the default plan when
     * it does not exist yet. Returns null only when no plan is configured, so a
     * school can never be silently left without a billing record.
     */
    public function ensureForSchool(School $school): ?SaaSSubscription
    {
        $existing = SaaSSubscription::withTrashed()
            ->where('school_id', $school->id)
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            return $existing;
        }

        $plan = SaaSPlan::query()
            ->where('is_active', true)
            ->orderBy('price_monthly')
            ->first()
            ?? SaaSPlan::query()->orderBy('id')->first();

        if (! $plan) {
            return null;
        }

        $subscription = new SaaSSubscription([
            'school_id' => $school->id,
            'saas_plan_id' => $plan->id,
            'billing_period' => 'monthly',
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays((int) ($plan->trial_days ?: 14)),
        ]);

        $subscription->save();

        // The model computed billing_start_date from the tenant's grace period
        // while saving; now place the first due date on the next billing cycle
        // so a long-standing tenant is not flagged overdue the moment it is
        // provisioned.
        $subscription->forceFill([
            'next_payment_date' => $subscription->initialNextPaymentDate()->toDateString(),
        ])->save();

        return $subscription;
    }

    /**
     * Move the school onto the chosen plan (and optional billing period),
     * provisioning a subscription first when the school has none.
     */
    public function assignPlan(School $school, int $planId, ?string $billingPeriod = null): ?SaaSSubscription
    {
        if (! SaaSPlan::query()->whereKey($planId)->exists()) {
            return null;
        }

        $subscription = $this->ensureForSchool($school);

        if (! $subscription) {
            return null;
        }

        $subscription->update([
            'saas_plan_id' => $planId,
            'billing_period' => $billingPeriod ?: ($subscription->billing_period ?: 'monthly'),
        ]);

        return $subscription;
    }
}
