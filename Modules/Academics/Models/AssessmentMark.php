<?php

namespace Modules\Academics\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Students\Models\Enrollment;

class AssessmentMark extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'enrollment_id',
        'assessment_type_id',
        'subject_id',
        'marks_obtained',
        'teacher_initials',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function assessmentType()
    {
        return $this->belongsTo(AssessmentType::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
