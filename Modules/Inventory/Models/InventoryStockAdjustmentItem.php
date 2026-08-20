<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryStockAdjustmentItem extends Model
{
    protected $table = 'inventory_stock_adjustment_items';

    protected $fillable = [
        'stock_adjustment_id',
        'inventory_item_id',
        'system_quantity',
        'physical_quantity',
        'variance',
        'reason',
    ];

    protected $casts = [
        'system_quantity' => 'integer',
        'physical_quantity' => 'integer',
        'variance' => 'integer',
    ];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(InventoryStockAdjustment::class, 'stock_adjustment_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
