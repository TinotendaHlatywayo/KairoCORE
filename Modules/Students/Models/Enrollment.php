<?php

namespace Modules\Students\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Term;

class Enrollment extends Model
{
    use BelongsToTenant;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PROMOTED = 'promoted';
    public const STATUS_REPEATED = 'repeated';
    public const STATUS_TRANSFERRED_OUT = 'transferred_out';
    public const STATUS_GRADUATED = 'graduated';

    protected $fillable = [
        'school_id',
        'student_id',
        'academic_year_id',
        'term_id',
        'course_id',
        'section_id',
        'roll_number',
        'status',
        'effective_date',
        'reason',
        'performed_by_id',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }
}
