<?php

namespace Modules\SaaS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SaaSPlan extends Model
{
    use SoftDeletes;

    protected $table = 'saas_plans';

    protected $fillable = [
        'uuid', 'name', 'slug', 'description', 'price_monthly',
        'price_quarterly', 'price_yearly', 'currency', 'trial_days',
        'grace_days', 'is_active', 'is_popular', 'features_payload',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_quarterly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'features_payload' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($plan) {
            $plan->uuid = (string) Str::uuid();
            if (empty($plan->slug)) {
                $plan->slug = Str::slug($plan->name);
            }
        });
    }

    public function features(): HasMany
    {
        return $this->hasMany(SaaSPlanFeature::class, 'saas_plan_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(SaaSSubscription::class, 'saas_plan_id');
    }
}
