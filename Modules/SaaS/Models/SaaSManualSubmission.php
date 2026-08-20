<?php

namespace Modules\SaaS\Models;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaaSManualSubmission extends Model
{
    use SoftDeletes;

    protected $table = 'saas_manual_submissions';

    protected $fillable = [
        'school_id', 'saas_invoice_id', 'reference_number', 'amount',
        'currency', 'payment_date', 'bank_name', 'notes',
        'receipt_file_path', 'status', 'rejection_reason',
        'reviewed_by_id', 'reviewed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SaaSInvoice::class, 'saas_invoice_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }
}
