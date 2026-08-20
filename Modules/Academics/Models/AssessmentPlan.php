<?php

namespace Modules\Academics\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AssessmentPlan extends Model
{
    use BelongsToTenant;

    protected $table = 'assessment_plans';

    protected $fillable = [
        'school_id',
        'term_id',
        'course_id',
        'subject_id',
        'created_by_id',
    ];

    public function components()
    {
        return $this->hasMany(AssessmentPlanComponent::class, 'assessment_plan_id');
    }
}
