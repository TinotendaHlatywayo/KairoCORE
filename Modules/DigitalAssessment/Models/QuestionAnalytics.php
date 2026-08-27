<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class QuestionAnalytics extends Model
{
    use BelongsToTenant;

    protected $table = 'question_analytics';

    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'question_bank_id',
        'total_attempts',
        'correct_count',
        'incorrect_count',
        'skipped_count',
        'percentage_correct',
        'average_response_time_seconds',
        'average_confidence',
        'last_calculated_at',
    ];

    protected $casts = [
        'total_attempts' => 'integer',
        'correct_count' => 'integer',
        'incorrect_count' => 'integer',
        'skipped_count' => 'integer',
        'percentage_correct' => 'decimal:2',
        'average_response_time_seconds' => 'integer',
        'average_confidence' => 'decimal:2',
        'last_calculated_at' => 'datetime',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function recalculate(): void
    {
        $responses = \Modules\DigitalAssessment\Models\DigitalAssessmentResponse::where('question_bank_id', $this->question_bank_id)
            ->whereNotNull('marks_awarded')
            ->get();

        $total = $responses->count();
        $correct = $responses->where('is_correct', true)->count();
        $incorrect = $total - $correct;

        $this->update([
            'total_attempts' => $total,
            'correct_count' => $correct,
            'incorrect_count' => $incorrect,
            'percentage_correct' => $total > 0 ? round(($correct / $total) * 100, 2) : 0,
            'average_response_time_seconds' => $total > 0 ? (int) $responses->avg('time_spent_seconds') : 0,
            'average_confidence' => $total > 0 ? round((float) $responses->whereNotNull('confidence_level')->avg('confidence_level'), 2) : 0,
            'last_calculated_at' => now(),
        ]);
    }
}
