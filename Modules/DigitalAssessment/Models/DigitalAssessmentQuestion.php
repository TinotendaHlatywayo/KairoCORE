<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalAssessmentQuestion extends Model
{
    protected $table = 'digital_assessment_questions';

    public $timestamps = false;

    protected $fillable = [
        'digital_assessment_id',
        'question_bank_id',
        'question_order',
        'marks_override',
        'pool_name',
        'pool_weight',
    ];

    protected $casts = [
        'marks_override' => 'decimal:2',
        'pool_weight' => 'integer',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(DigitalAssessment::class, 'digital_assessment_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function getEffectiveMarks(): float
    {
        return (float) ($this->marks_override ?? $this->question?->marks ?? 0);
    }
}
