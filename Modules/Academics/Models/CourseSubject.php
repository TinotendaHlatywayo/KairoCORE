<?php

namespace Modules\Academics\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Timetables\Models\TimetableLesson;

class CourseSubject extends Model
{
    use BelongsToTenant;

    protected $table = 'course_subject';

    protected $fillable = [
        'school_id',
        'course_id',
        'subject_id',
        'teacher_id',
        'role',
        'periods_per_week',
        'room_preference',
    ];

    protected $casts = [
        'periods_per_week' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function hasScheduleConflict(): bool
    {
        if (! $this->teacher_id) {
            return false;
        }

        return TimetableLesson::where('teacher_id', $this->teacher_id)
            ->where('school_id', $this->school_id)
            ->get()
            ->filter(function ($lesson) {
                return TimetableLesson::where('teacher_id', $this->teacher_id)
                    ->where('id', '!=', $lesson->id)
                    ->where('day', $lesson->day)
                    ->where(function ($q) use ($lesson) {
                        $q->where('start_time', '<', $lesson->end_time)
                            ->where('end_time', '>', $lesson->start_time);
                    })
                    ->exists();
            })->isNotEmpty();
    }
}
