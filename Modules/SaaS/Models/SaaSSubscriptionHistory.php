<?php

namespace Modules\SaaS\Models;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaaSSubscriptionHistory extends Model
{
    protected $table = 'saas_subscription_histories';

    protected $fillable = [
        'school_id', 'saas_subscription_id', 'action_type',
        'old_plan_id', 'new_plan_id', 'change_notes', 'performed_by_id',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(SaaSSubscription::class, 'saas_subscription_id');
    }

    public function oldPlan(): BelongsTo
    {
        return $this->belongsTo(SaaSPlan::class, 'old_plan_id');
    }

    public function newPlan(): BelongsTo
    {
        return $this->belongsTo(SaaSPlan::class, 'new_plan_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }
}
