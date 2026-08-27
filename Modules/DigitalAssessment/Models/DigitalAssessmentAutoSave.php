<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalAssessmentAutoSave extends Model
{
    protected $table = 'digital_assessment_auto_saves';

    protected $fillable = [
        'digital_assessment_attempt_id',
        'question_bank_id',
        'response_data',
        'saved_at',
    ];

    protected $casts = [
        'response_data' => 'array',
        'saved_at' => 'datetime',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(DigitalAssessmentAttempt::class, 'digital_assessment_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }
}
