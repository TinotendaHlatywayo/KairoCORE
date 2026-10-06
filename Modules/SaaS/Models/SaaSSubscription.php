<?php

namespace Modules\SaaS\Models;

use App\Models\School;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SaaSSubscription extends Model
{
    use SoftDeletes;

    protected $table = 'saas_subscriptions';

    protected $fillable = [
        'uuid', 'school_id', 'saas_plan_id', 'billing_period', 'status',
        'trial_ends_at', 'starts_at', 'ends_at', 'grace_ends_at',
        'billing_start_date', 'billing_day_of_month',
        'custom_price_monthly', 'credit_balance', 'next_payment_date',
        'last_payment_date', 'auto_deactivate_after_days', 'dunning_days_before',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'grace_ends_at' => 'datetime',
        'next_payment_date' => 'date',
        'last_payment_date' => 'date',
        'billing_start_date' => 'date',
        'billing_day_of_month' => 'integer',
        'credit_balance' => 'decimal:2',
        'custom_price_monthly' => 'decimal:2',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($subscription) {
            $subscription->uuid = (string) Str::uuid();
            if (empty($subscription->next_payment_date)) {
                $subscription->next_payment_date = now()->addDays(14)->toDateString();
            }

            // Give every tenant its own billing cycle: free from registration
            // until billing_start_date, then billable monthly on that day.
            if (empty($subscription->billing_start_date)) {
                $freeDays = 30;
                try {
                    $freeDays = (int) PlatformBillingSetting::current()->default_free_days;
                } catch (\Throwable $e) {
                    // Settings table not migrated yet (e.g. during a fresh
                    // install) — fall back to the 30-day default.
                }

                $registeredAt = $subscription->school?->created_at ?? now();
                $start = Carbon::parse($registeredAt)->addDays(max(0, $freeDays));

                $subscription->billing_start_date = $start->toDateString();

                if (empty($subscription->billing_day_of_month)) {
                    $subscription->billing_day_of_month = max(1, min(28, (int) $start->day));
                }
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SaaSPlan::class, 'saas_plan_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SaaSInvoice::class, 'saas_subscription_id');
    }

    /**
     * Resolves the custom monthly price set by the Super Admin, falling back to the base plan price.
     */
    public function getBillingAmount(): float
    {
        return (float) ($this->custom_price_monthly ?? $this->plan->price_monthly);
    }

    /**
     * Deducts fees from credit balance (e.g., if a school paid in advance).
     */
    public function deductFromCreditBalance(): bool
    {
        $due = $this->getBillingAmount();

        if ($this->credit_balance >= $due) {
            $this->decrement('credit_balance', $due);

            $nextDate = Carbon::parse($this->next_payment_date)->addDays(30)->toDateString();

            $this->update([
                'last_payment_date' => now()->toDateString(),
                'next_payment_date' => $nextDate,
                'ends_at' => Carbon::parse($nextDate),
                'status' => 'active',
            ]);

            return true;
        }

        return false;
    }

    public function isTrialing(): bool
    {
        return $this->status === 'trialing';
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'grace_period']);
    }

    /**
     * True while the tenant is still inside the free period that starts at
     * registration and ends on billing_start_date. No reminders or suspensions
     * fire during this window.
     */
    public function isInFreePeriod(): bool
    {
        if (! $this->billing_start_date) {
            return $this->status === 'trialing';
        }

        return now()->lt($this->billing_start_date);
    }

    /**
     * The day-of-month the cycle repeats on. Defaults to the day of the billing
     * start date when the super admin has not overridden it.
     */
    public function billingDay(): int
    {
        if ($this->billing_day_of_month) {
            return max(1, min(28, (int) $this->billing_day_of_month));
        }

        if ($this->billing_start_date) {
            return max(1, min(28, (int) $this->billing_start_date->day));
        }

        return max(1, min(28, (int) now()->day));
    }

    /**
     * Number of months an invoice covers for the subscription's billing period.
     */
    public function billingMonths(): int
    {
        return match ($this->billing_period) {
            'quarterly' => 3,
            'yearly' => 12,
            default => 1,
        };
    }

    /**
     * The next date the tenant should be billed, honouring the per-tenant
     * billing day of month and never falling before the first billing date.
     */
    public function nextBillingDate(): Carbon
    {
        $today = now()->startOfDay();
        $day = $this->billingDay();

        $candidate = $today->copy()->startOfMonth()->day($day)->startOfDay();
        if ($candidate->lt($today)) {
            $candidate = $candidate->copy()->addMonthNoOverflow()->day($day)->startOfDay();
        }

        if ($this->billing_start_date && $candidate->lt($this->billing_start_date->copy()->startOfDay())) {
            $candidate = $this->billing_start_date->copy()->startOfDay();
        }

        return $candidate;
    }

    public function isExpired(): bool
    {
        return in_array($this->status, ['expired', 'suspended']);
    }

    public function getDaysRemaining(): int
    {
        if (! $this->next_payment_date) {
            return 0;
        }

        return (int) max(0, now()->diffInDays($this->next_payment_date, false));
    }
}
