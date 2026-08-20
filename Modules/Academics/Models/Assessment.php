<?php

namespace Modules\Academics\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    use BelongsToTenant;

    protected $table = 'assessments';

    protected $fillable = [
        'school_id',
        'assessment_plan_component_id',
        'section_id',
        'name',
        'assessment_date',
        'max_mark',
        'included_in_report',
        'status',
        'created_by_id',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'included_in_report' => 'boolean',
    ];

    public function component()
    {
        return $this->belongsTo(AssessmentPlanComponent::class, 'assessment_plan_component_id');
    }

    public function subComponents()
    {
        return $this->hasMany(AssessmentSubComponent::class, 'assessment_id');
    }

    public function marks()
    {
        return $this->hasMany(AssessmentMarksLedger::class, 'assessment_id');
    }
}
