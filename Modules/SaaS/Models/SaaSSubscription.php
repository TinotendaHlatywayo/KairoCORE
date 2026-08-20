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
