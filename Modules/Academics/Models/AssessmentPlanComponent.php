<?php

namespace Modules\Academics\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentPlanComponent extends Model
{
    protected $table = 'assessment_plan_components';

    protected $fillable = [
        'assessment_plan_id',
        'name',
        'weight_percentage',
        'evaluation_rule',
        'rule_value_parameter',
    ];

    public function plan()
    {
        return $this->belongsTo(AssessmentPlan::class, 'assessment_plan_id');
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class, 'assessment_plan_component_id');
    }
}
