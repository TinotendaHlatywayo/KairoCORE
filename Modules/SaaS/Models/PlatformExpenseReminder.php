<?php

namespace Modules\SaaS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A reminder already sent for a recurring platform expense occurrence. Doubles
 * as the de-duplication key so the scheduler never emails the operator twice
 * for the same upcoming payment.
 */
class PlatformExpenseReminder extends Model
{
    protected $table = 'platform_expense_reminders';

    protected $fillable = [
        'platform_expense_id',
        'due_date',
        'stage',
        'sent_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'sent_at' => 'datetime',
    ];

    public const STAGE_UPCOMING = 'upcoming';

    public const STAGE_DAY_BEFORE = 'day_before';

    public const STAGE_DUE = 'due';

    public function platformExpense(): BelongsTo
    {
        return $this->belongsTo(PlatformExpense::class, 'platform_expense_id');
    }
}
