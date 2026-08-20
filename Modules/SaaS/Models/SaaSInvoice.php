<?php

namespace Modules\SaaS\Models;

use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SaaSInvoice extends Model
{
    use SoftDeletes;

    protected $table = 'saas_invoices';

    protected $fillable = [
        'uuid', 'school_id', 'saas_subscription_id', 'invoice_number',
        'issue_date', 'due_date', 'subtotal', 'discount', 'tax_amount',
        'total', 'currency', 'status', 'is_locked', 'payment_instructions',
        'integrity_hash',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'is_locked' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($invoice) {
            $invoice->uuid = (string) Str::uuid();
            $invoice->integrity_hash = static::calculateIntegrityHash($invoice);
        });
        static::updating(function ($invoice) {
            $invoice->integrity_hash = static::calculateIntegrityHash($invoice);
        });
    }

    public static function calculateIntegrityHash(SaaSInvoice $invoice): string
    {
        return hash_hmac(
            'sha256',
            sprintf('%s|%s|%s|%s', $invoice->school_id, $invoice->invoice_number, $invoice->total, $invoice->status),
            config('app.key')
        );
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(SaaSSubscription::class, 'saas_subscription_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaaSInvoiceItem::class, 'saas_invoice_id');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(SaaSReceipt::class, 'saas_invoice_id');
    }
}
