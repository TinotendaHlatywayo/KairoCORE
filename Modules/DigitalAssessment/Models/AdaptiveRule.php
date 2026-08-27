<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class AdaptiveRule extends Model
{
    use BelongsToTenant;

    protected $table = 'adaptive_rules';

    protected $fillable = [
        'school_id',
        'adaptive_assessment_id',
        'rule_type',
        'threshold_from',
        'threshold_to',
        'condition_op',
        'adjustment',
        'target_question_bank_id',
        'target_difficulty',
        'priority',
    ];

    protected $casts = [
        'threshold_from' => 'integer',
        'threshold_to' => 'integer',
        'adjustment' => 'integer',
        'target_difficulty' => 'integer',
        'priority' => 'integer',
    ];

    public function adaptiveAssessment(): BelongsTo
    {
        return $this->belongsTo(AdaptiveAssessment::class, 'adaptive_assessment_id');
    }

    public function targetQuestion(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'target_question_bank_id');
    }
}
