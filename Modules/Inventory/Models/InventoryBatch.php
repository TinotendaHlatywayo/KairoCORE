<?php

namespace Modules\Inventory\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryBatch extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'inventory_item_id',
        'batch_number',
        'expiry_date',
        'initial_quantity',
        'current_quantity',
        'unit_cost',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'initial_quantity' => 'integer',
        'current_quantity' => 'integer',
        'unit_cost' => 'decimal:4',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
