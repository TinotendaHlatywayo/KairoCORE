<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementRequestItem extends Model
{
    protected $fillable = [
        'procurement_request_id',
        'item_name',
        'inventory_item_id',
        'quantity',
        'estimated_unit_cost',
        'specifications',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'estimated_unit_cost' => 'decimal:2',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class, 'procurement_request_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
