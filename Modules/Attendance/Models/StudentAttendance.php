<?php

namespace Modules\Attendance\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Students\Models\Student;
use Modules\Timetables\Models\TimetableLesson;

class StudentAttendance extends Model
{
    use BelongsToTenant;

    protected $table = 'student_attendances';

    protected $fillable = [
        'school_id',
        'student_id',
        'timetable_lesson_id',
        'date',
        'status',
        'remarks',
        'marked_by_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function timetableLesson()
    {
        return $this->belongsTo(TimetableLesson::class, 'timetable_lesson_id');
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by_id');
    }
}
