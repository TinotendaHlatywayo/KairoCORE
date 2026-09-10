<?php

namespace Modules\Promotion\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;

class PromotionItem extends Model
{
    use BelongsToTenant;

    public const DECISION_PROMOTED = 'promoted';
    public const DECISION_REPEATED = 'repeated';
    public const DECISION_NEEDS_SCREENING = 'needs_screening';
    public const DECISION_GRADUATED = 'graduated';

    protected $fillable = [
        'school_id',
        'promotion_run_id',
        'student_id',
        'source_enrollment_id',
        'decision',
        'target_course_id',
        'target_section_id',
        'reason',
    ];

    public function promotionRun(): BelongsTo
    {
        return $this->belongsTo(PromotionRun::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function sourceEnrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'source_enrollment_id');
    }

    public function targetCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'target_course_id');
    }

    public function targetSection(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'target_section_id');
    }
}
