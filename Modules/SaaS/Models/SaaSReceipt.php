<?php

namespace Modules\SaaS\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SaaSReceipt extends Model
{
    protected $table = 'saas_receipts';

    protected $fillable = [
        'uuid', 'school_id', 'saas_invoice_id', 'saas_transaction_id',
        'receipt_number', 'amount_paid', 'currency', 'issued_at',
        'verification_token',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'issued_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($receipt) {
            $receipt->uuid = (string) Str::uuid();
            $receipt->verification_token = hash_hmac(
                'md5',
                sprintf('%s|%s|%s', $receipt->school_id, $receipt->receipt_number, $receipt->amount_paid),
                config('app.key')
            );
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SaaSInvoice::class, 'saas_invoice_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(SaaSTransaction::class, 'saas_transaction_id');
    }
}
