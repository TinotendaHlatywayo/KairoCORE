<?php

namespace Modules\Finance\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'bank_account_id',
        'invoice_id',
        'receipt_number',
        'reference_number',
        'amount',
        'currency',
        'payment_method',
        'payment_date',
        'is_refund',
        'excess_handling',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'is_refund' => 'boolean',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(SchoolBankAccount::class, 'bank_account_id');
    }
}
