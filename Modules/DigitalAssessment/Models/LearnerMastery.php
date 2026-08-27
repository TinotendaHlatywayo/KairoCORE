<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\DigitalAssessment\Enums\MasteryLabel;
use App\Traits\BelongsToTenant;

class LearnerMastery extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'learner_mastery';

    protected $fillable = [
        'school_id',
        'enrollment_id',
        'student_id',
        'subject_id',
        'topic',
        'subtopic',
        'mastery_score',
        'mastery_label',
        'total_assessments',
        'correct_responses',
        'total_responses',
        'last_assessed_at',
    ];

    protected $casts = [
        'mastery_score' => 'decimal:2',
        'mastery_label' => MasteryLabel::class,
        'total_assessments' => 'integer',
        'correct_responses' => 'integer',
        'total_responses' => 'integer',
        'last_assessed_at' => 'datetime',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(\Modules\Students\Models\Enrollment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(\Modules\Students\Models\Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(\Modules\Academics\Models\Subject::class);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeForSubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForTopic($query, string $topic)
    {
        return $query->where('topic', $topic);
    }

    public static function getMasteryLabelFromScore(float $score): MasteryLabel
    {
        return MasteryLabel::fromScore($score);
    }
}
