<?php

namespace Modules\Academics\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academics\Models\Section;
use Modules\Timetables\Models\TimetableLesson;

class CourseSubject extends Model
{
    use BelongsToTenant;

    protected $table = 'course_subject';

    protected $fillable = [
        'school_id',
        'course_id',
        'section_id',
        'subject_id',
        'teacher_id',
        'role',
        'periods_per_week',
        'double_periods_per_week',
        'triple_periods_per_week',
        'room_preference',
    ];

    protected $casts = [
        'periods_per_week' => 'integer',
        'double_periods_per_week' => 'integer',
        'triple_periods_per_week' => 'integer',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
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

        $lessons = TimetableLesson::where('teacher_id', $this->teacher_id)
            ->where('school_id', $this->school_id)
            ->whereNotNull('time_slot_id')
            ->with('timeSlot')
            ->get(['id', 'day_of_week', 'time_slot_id']);

        if ($lessons->count() < 2) {
            return false;
        }

        foreach ($lessons as $i => $lesson) {
            foreach ($lessons as $j => $other) {
                if ($j <= $i) {
                    continue;
                }

                if ($lesson->day_of_week !== $other->day_of_week) {
                    continue;
                }

                if ($this->timeSlotsOverlap($lesson->timeSlot, $other->timeSlot)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function timeSlotsOverlap($a, $b): bool
    {
        if (! $a || ! $b || ! $a->start_time || ! $a->end_time || ! $b->start_time || ! $b->end_time) {
            return false;
        }

        $aStart = strtotime($a->start_time);
        $aEnd = strtotime($a->end_time);
        $bStart = strtotime($b->start_time);
        $bEnd = strtotime($b->end_time);

        if ($aStart === false || $aEnd === false || $bStart === false || $bEnd === false) {
            return false;
        }

        return $aStart < $bEnd && $bStart < $aEnd;
    }
}
