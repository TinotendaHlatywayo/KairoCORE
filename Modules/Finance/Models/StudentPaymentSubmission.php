<?php

namespace Modules\Finance\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Students\Models\Student;

class StudentPaymentSubmission extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'school_id', 'invoice_id', 'student_id', 'gateway', 'reference_number',
        'amount', 'currency', 'payment_date', 'bank_name', 'notes',
        'proof_file_path', 'transaction_reference', 'status', 'rejection_reason',
        'reviewed_by_id', 'reviewed_at',
        'source_bank_name', 'source_account_number', 'destination_bank_account_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function destinationBankAccount(): BelongsTo
    {
        return $this->belongsTo(SchoolBankAccount::class, 'destination_bank_account_id');
    }
}
