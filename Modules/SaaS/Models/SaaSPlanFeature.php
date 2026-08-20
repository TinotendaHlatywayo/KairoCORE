<?php

namespace Modules\SaaS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaaSPlanFeature extends Model
{
    protected $table = 'saas_plan_features';

    protected $fillable = ['saas_plan_id', 'feature_key', 'feature_value'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SaaSPlan::class, 'saas_plan_id');
    }
}
