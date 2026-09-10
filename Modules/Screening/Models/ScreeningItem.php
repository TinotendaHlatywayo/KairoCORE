<?php

namespace Modules\Screening\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;

class ScreeningItem extends Model
{
    use BelongsToTenant;

    public const DECISION_PLACED = 'placed';
    public const DECISION_UNPLACED = 'unplaced';

    protected $table = 'screening_items';

    protected $fillable = [
        'school_id',
        'screening_run_id',
        'student_id',
        'source_enrollment_id',
        'overall_score',
        'decision',
        'target_course_id',
        'target_section_id',
        'reason',
    ];

    protected $casts = [
        'overall_score' => 'decimal:2',
    ];

    public function screeningRun(): BelongsTo
    {
        return $this->belongsTo(ScreeningRun::class);
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