<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\DigitalAssessment\Enums\AssessmentCategory;
use Modules\DigitalAssessment\Enums\AssessmentMode;
use Modules\DigitalAssessment\Enums\AssessmentStatus;
use Modules\DigitalAssessment\Enums\FeedbackMode;
use App\Traits\BelongsToTenant;

class DigitalAssessment extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'digital_assessments';

    protected $fillable = [
        'school_id',
        'subject_id',
        'section_id',
        'academic_year_id',
        'term_id',
        'created_by_id',
        'title',
        'description',
        'instructions',
        'assessment_mode',
        'assessment_category',
        'contributes_to_grade',
        'assessment_type_id',
        'difficulty',
        'duration_minutes',
        'total_marks',
        'pass_mark',
        'max_attempts',
        'attempts_allowed',
        'randomize_questions',
        'randomize_options',
        'show_feedback',
        'feedback_mode',
        'shuffle_question_pool',
        'allow_backward_navigation',
        'allow_question_skipping',
        'calculator_enabled',
        'password_protection',
        'anti_cheating_enabled',
        'question_pool_config',
        'availability_start_at',
        'availability_end_at',
        'deadline_at',
        'late_submission_allowed',
        'auto_submit',
        'adaptive_mode',
        'adaptive_base_difficulty',
        'adaptive_window_size',
        'adaptive_adjustment_rate',
        'status',
        'published_at',
        'settings',
    ];

    protected $casts = [
        'contributes_to_grade' => 'boolean',
        'randomize_questions' => 'boolean',
        'randomize_options' => 'boolean',
        'show_feedback' => 'boolean',
        'shuffle_question_pool' => 'boolean',
        'allow_backward_navigation' => 'boolean',
        'allow_question_skipping' => 'boolean',
        'calculator_enabled' => 'boolean',
        'password_protection' => 'boolean',
        'anti_cheating_enabled' => 'boolean',
        'late_submission_allowed' => 'boolean',
        'auto_submit' => 'boolean',
        'adaptive_mode' => 'boolean',
        'adaptive_base_difficulty' => 'integer',
        'adaptive_window_size' => 'integer',
        'adaptive_adjustment_rate' => 'decimal:2',
        'total_marks' => 'decimal:2',
        'pass_mark' => 'decimal:2',
        'assessment_mode' => AssessmentMode::class,
        'assessment_category' => AssessmentCategory::class,
        'feedback_mode' => FeedbackMode::class,
        'status' => AssessmentStatus::class,
        'question_pool_config' => 'array',
        'settings' => 'array',
        'availability_start_at' => 'datetime',
        'availability_end_at' => 'datetime',
        'deadline_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(\Modules\Academics\Models\Subject::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(\Modules\Academics\Models\Section::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(\Modules\Academics\Models\AcademicYear::class, 'academic_year_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(\Modules\Academics\Models\Term::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_id');
    }

    public function assessmentType(): BelongsTo
    {
        return $this->belongsTo(\Modules\Academics\Models\AssessmentType::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(DigitalAssessmentQuestion::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(DigitalAssessmentAttempt::class);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [AssessmentStatus::Published, AssessmentStatus::Active]);
    }

    public function scopeForSection($query, int $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    public function scopeAvailable($query)
    {
        $now = now();

        return $query->where('status', AssessmentStatus::Published)
            ->where(function ($q) use ($now) {
                $q->whereNull('availability_start_at')
                    ->orWhere('availability_start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('availability_end_at')
                    ->orWhere('availability_end_at', '>=', $now);
            });
    }

    public function isAvailable(): bool
    {
        if (! $this->status->canStudentsAccess()) {
            return false;
        }

        $now = now();

        if ($this->availability_start_at && $this->availability_start_at->isAfter($now)) {
            return false;
        }

        if ($this->availability_end_at && $this->availability_end_at->isBefore($now)) {
            return false;
        }

        return true;
    }

    public function isAdaptive(): bool
    {
        return $this->assessment_mode === AssessmentMode::Adaptive;
    }

    public function getCalculatedTotalMarks(): float
    {
        if ($this->total_marks > 0) {
            return (float) $this->total_marks;
        }

        return (float) $this->questions()->sum('marks_override') ?: (float) $this->questions()->sum('question_bank.marks');
    }

    public function attemptsRemainingForStudent(int $studentId): int
    {
        $usedAttempts = $this->attempts()
            ->where('student_id', $studentId)
            ->count();

        return max(0, $this->max_attempts - $usedAttempts);
    }
}
