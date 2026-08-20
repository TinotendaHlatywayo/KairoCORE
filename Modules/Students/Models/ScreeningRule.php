<?php

namespace Modules\Students\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;

class ScreeningRule extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'source_course_id',
        'target_course_id',
        'target_section_id',
        'rule_type',
        'subject_id',
        'min_percentage',
    ];

    protected $casts = [
        'min_percentage' => 'decimal:2',
    ];

    public function sourceCourse()
    {
        return $this->belongsTo(Course::class, 'source_course_id');
    }

    public function targetCourse()
    {
        return $this->belongsTo(Course::class, 'target_course_id');
    }

    public function targetSection()
    {
        return $this->belongsTo(Section::class, 'target_section_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
