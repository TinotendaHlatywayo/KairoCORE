<?php

namespace Modules\SaaS\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SaaSTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'saas_transactions';

    protected $fillable = [
        'uuid', 'school_id', 'saas_invoice_id', 'payment_gateway_key',
        'transaction_reference', 'amount', 'currency', 'status',
        'processed_at', 'gateway_raw_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'gateway_raw_response' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($transaction) {
            $transaction->uuid = (string) Str::uuid();
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

    public function receipt(): HasOne
    {
        return $this->hasOne(SaaSReceipt::class, 'saas_transaction_id');
    }
}
