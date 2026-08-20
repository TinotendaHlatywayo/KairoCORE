<?php

namespace Modules\Timetables\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Classroom;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Academics\Models\Term;
use Modules\Attendance\Models\StudentAttendance;

class TimetableLesson extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'template_id', // Add this line
        'academic_year_id',
        'term_id',
        'course_id',
        'section_id',
        'subject_id',
        'teacher_id',
        'classroom_id',
        'time_slot_id',
        'day_of_week',
        'custom_label',
        'color',
        'is_locked',
    ];

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

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function attendances()
    {
        return $this->hasMany(StudentAttendance::class, 'timetable_lesson_id');
    }
}
