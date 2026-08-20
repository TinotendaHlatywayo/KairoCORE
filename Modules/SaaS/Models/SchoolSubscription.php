<?php

namespace Modules\SaaS\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;

class SchoolSubscription extends Model
{
    protected $table = 'school_subscriptions';

    protected $fillable = [
        'school_id',
        'saas_plan_id',
        'status',
        'trial_ends_at',
        'ends_at',
        'storage_limit_gb',
        'max_users',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function plan()
    {
        return $this->belongsTo(SaaSPlan::class, 'saas_plan_id');
    }
}
