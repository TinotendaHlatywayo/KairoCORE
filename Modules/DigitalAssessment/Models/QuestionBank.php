<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\DigitalAssessment\Enums\QuestionDifficulty;
use Modules\DigitalAssessment\Enums\QuestionStatus;
use Modules\DigitalAssessment\Enums\QuestionType;
use Modules\Students\Models\Student;
use App\Traits\BelongsToTenant;

class QuestionBank extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'question_bank';

    protected $fillable = [
        'school_id',
        'subject_id',
        'created_by_id',
        'title',
        'description',
        'question_type',
        'question_text',
        'question_html',
        'explanation',
        'explanation_html',
        'options',
        'correct_answer',
        'manual_marking',
        'matching_pairs',
        'ordering_items',
        'fill_blank_answer',
        'short_answer',
        'numeric_answer',
        'marks',
        'difficulty',
        'topic',
        'subtopic',
        'learning_objective',
        'competency',
        'curriculum_reference',
        'grade_level',
        'tags',
        'images',
        'status',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
        'manual_marking' => 'boolean',
        'matching_pairs' => 'array',
        'ordering_items' => 'array',
        'tags' => 'array',
        'images' => 'array',
        'marks' => 'decimal:2',
        'question_type' => QuestionType::class,
        'difficulty' => QuestionDifficulty::class,
        'status' => QuestionStatus::class,
        'last_used_at' => 'datetime',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(\Modules\Academics\Models\Subject::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_id');
    }

    public function assessmentQuestions(): HasMany
    {
        return $this->hasMany(DigitalAssessmentQuestion::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(DigitalAssessmentResponse::class);
    }

    public function analytics(): HasOne
    {
        return $this->hasOne(QuestionAnalytics::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', QuestionStatus::Published);
    }

    public function scopeForSubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForDifficulty($query, QuestionDifficulty $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeForTopic($query, string $topic)
    {
        return $query->where('topic', $topic);
    }

    public function scopeOfType($query, QuestionType $type)
    {
        return $query->where('question_type', $type);
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    public function isAutoMarkable(): bool
    {
        // Teachers can opt any question type out of auto marking; those
        // questions are graded manually in the marking queue instead.
        if ($this->manual_marking) {
            return false;
        }

        return $this->question_type->isAutoMarkable();
    }
}
