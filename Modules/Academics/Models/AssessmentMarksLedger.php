<?php

namespace Modules\Academics\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Students\Models\Enrollment;

class AssessmentMarksLedger extends Model
{
    use BelongsToTenant;

    protected $table = 'assessment_marks_ledger';

    protected $fillable = [
        'school_id',
        'enrollment_id',
        'assessment_id',
        'assessment_sub_component_id',
        'marks_obtained',
        'status',
        'teacher_comment',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }

    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function subComponent()
    {
        return $this->belongsTo(AssessmentSubComponent::class, 'assessment_sub_component_id');
    }
}
