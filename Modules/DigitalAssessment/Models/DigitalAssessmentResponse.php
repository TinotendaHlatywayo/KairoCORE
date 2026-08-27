<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalAssessmentResponse extends Model
{
    protected $table = 'digital_assessment_responses';

    protected $fillable = [
        'digital_assessment_attempt_id',
        'question_bank_id',
        'learner_answer',
        'correct_answer',
        'is_correct',
        'marks_awarded',
        'marks_possible',
        'file_path',
        'original_filename',
        'file_size',
        'file_mime',
        'time_spent_seconds',
        'answered_at',
        'confidence_level',
        'question_difficulty_at_time',
        'question_selection_reason',
        'teacher_feedback',
        'feedback_viewed_at',
        'marked_by_id',
        'marked_at',
    ];

    protected $casts = [
        'learner_answer' => 'array',
        'correct_answer' => 'array',
        'is_correct' => 'boolean',
        'marks_awarded' => 'decimal:2',
        'marks_possible' => 'decimal:2',
        'file_size' => 'integer',
        'time_spent_seconds' => 'integer',
        'answered_at' => 'datetime',
        'confidence_level' => 'integer',
        'feedback_viewed_at' => 'datetime',
        'marked_at' => 'datetime',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(DigitalAssessmentAttempt::class, 'digital_assessment_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'marked_by_id');
    }

    public function isMarked(): bool
    {
        return $this->marked_at !== null;
    }

    public function needsManualMarking(): bool
    {
        return $this->marks_awarded === null && $this->question?->isAutoMarkable() === false;
    }
}
