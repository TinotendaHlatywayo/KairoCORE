<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class LearnerStreak extends Model
{
    use BelongsToTenant;

    protected $table = 'learner_streaks';

    protected $fillable = [
        'school_id',
        'student_id',
        'current_streak',
        'longest_streak',
        'last_activity_date',
        'streak_start_date',
        'total_active_days',
    ];

    protected $casts = [
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'last_activity_date' => 'date',
        'streak_start_date' => 'date',
        'total_active_days' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(\Modules\Students\Models\Student::class);
    }

    public function recordActivity(?string $activityType = null): bool
    {
        $today = now()->toDateString();

        if ($this->last_activity_date === $today) {
            return false;
        }

        $yesterday = now()->subDay()->toDateString();

        if ($this->last_activity_date === $yesterday) {
            $this->increment('current_streak');
        } else {
            $this->update([
                'current_streak' => 1,
                'streak_start_date' => $today,
            ]);
        }

        $this->increment('total_active_days');
        $this->update(['last_activity_date' => $today]);

        if ($this->current_streak > $this->longest_streak) {
            $this->update(['longest_streak' => $this->current_streak]);
        }

        return true;
    }

    public static function forStudent(int $schoolId, int $studentId): self
    {
        return static::firstOrCreate(
            ['school_id' => $schoolId, 'student_id' => $studentId],
            [
                'current_streak' => 0,
                'longest_streak' => 0,
                'total_active_days' => 0,
            ]
        );
    }
}
