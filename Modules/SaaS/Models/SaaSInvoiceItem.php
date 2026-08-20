<?php

namespace Modules\SaaS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaaSInvoiceItem extends Model
{
    protected $table = 'saas_invoice_items';

    protected $fillable = ['saas_invoice_id', 'description', 'quantity', 'unit_price', 'total'];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SaaSInvoice::class, 'saas_invoice_id');
    }
}
