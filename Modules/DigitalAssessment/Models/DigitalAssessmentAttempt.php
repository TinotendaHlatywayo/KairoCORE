<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\DigitalAssessment\Enums\AttemptStatus;
use App\Traits\BelongsToTenant;

class DigitalAssessmentAttempt extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'digital_assessment_attempts';

    protected $fillable = [
        'school_id',
        'digital_assessment_id',
        'student_id',
        'enrollment_id',
        'attempt_number',
        'started_at',
        'submitted_at',
        'duration_seconds',
        'score',
        'percentage',
        'auto_score',
        'manual_score',
        'final_score',
        'marks_obtained',
        'max_possible_marks',
        'status',
        'ip_address',
        'user_agent',
        'suspicious_activity_log',
        'feedback_viewed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'duration_seconds' => 'integer',
        'score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'auto_score' => 'decimal:2',
        'manual_score' => 'decimal:2',
        'final_score' => 'decimal:2',
        'marks_obtained' => 'decimal:2',
        'max_possible_marks' => 'decimal:2',
        'status' => AttemptStatus::class,
        'suspicious_activity_log' => 'array',
        'feedback_viewed_at' => 'datetime',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(DigitalAssessment::class, 'digital_assessment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(\Modules\Students\Models\Student::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(\Modules\Students\Models\Enrollment::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(DigitalAssessmentResponse::class, 'digital_assessment_attempt_id');
    }

    public function autoSaves(): HasMany
    {
        return $this->hasMany(DigitalAssessmentAutoSave::class, 'digital_assessment_attempt_id');
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', AttemptStatus::InProgress);
    }

    public function scopeComplete($query)
    {
        return $query->whereIn('status', [
            AttemptStatus::Submitted,
            AttemptStatus::AutoSubmitted,
            AttemptStatus::Graded,
            AttemptStatus::Published,
        ]);
    }

    public function isComplete(): bool
    {
        return $this->status->isComplete();
    }

    public function calculateDuration(): int
    {
        if ($this->submitted_at && $this->started_at) {
            return (int) $this->started_at->diffInSeconds($this->submitted_at);
        }

        return 0;
    }

    public function calculatePercentage(): float
    {
        if ($this->max_possible_marks <= 0) {
            return 0;
        }

        return round(($this->marks_obtained / $this->max_possible_marks) * 100, 2);
    }

    public function hasPassed(): bool
    {
        $assessment = $this->assessment;

        if (! $assessment || $assessment->pass_mark <= 0) {
            return true;
        }

        return $this->percentage >= $assessment->pass_mark;
    }
}
