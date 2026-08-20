<?php

namespace Modules\Academics\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class MarkCorrectionRequest extends Model
{
    use BelongsToTenant;

    protected $table = 'mark_correction_requests';

    protected $fillable = [
        'school_id',
        'assessment_marks_ledger_id',
        'teacher_id',
        'old_mark',
        'new_mark',
        'old_status',
        'new_status',
        'reason_for_change',
        'approval_status',
        'approved_by_id',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function ledger()
    {
        return $this->belongsTo(AssessmentMarksLedger::class, 'assessment_marks_ledger_id');
    }
}
