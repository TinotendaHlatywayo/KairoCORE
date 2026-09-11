<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementOrderItem extends Model
{
    protected $fillable = [
        'procurement_order_id',
        'inventory_item_id',
        'quantity_ordered',
        'quantity_received',
        'unit_cost',
        'is_fixed_asset',
    ];

    protected $casts = [
        'quantity_ordered' => 'integer',
        'quantity_received' => 'integer',
        'unit_cost' => 'decimal:4',
        'is_fixed_asset' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProcurementOrder::class, 'procurement_order_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
