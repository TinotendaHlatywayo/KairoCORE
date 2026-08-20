<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use BelongsToTenant;

    protected $table = 'inventory_items';

    protected $fillable = [
        'school_id',
        'category_id',
        'sku',
        'barcode',
        'name',
        'description',
        'item_type', // consumable, returnable, fixed_asset
        'unit_of_measure',
        'reorder_level',
        'current_quantity',
        'average_unit_cost',
        'is_saleable',
        'sale_price',
        'meta_data', // Highly flexible JSON field to store custom properties
    ];

    protected $casts = [
        'meta_data' => 'array',
        'is_saleable' => 'boolean',
        'reorder_level' => 'integer',
        'current_quantity' => 'integer',
        'average_unit_cost' => 'decimal:4',
        'sale_price' => 'decimal:2',
    ];

    /**
     * Get the taxonomy category this item belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'category_id');
    }

    /**
     * Get the individual batches/lots associated with this item (Crucial for consumables).
     */
    public function batches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class, 'inventory_item_id');
    }

    /**
     * Get the serialized physical instances if this item is a capitalized fixed asset.
     */
    public function fixedAssets(): HasMany
    {
        return $this->hasMany(FixedAsset::class, 'inventory_item_id');
    }

    /**
     * Immutable stock movement history (Replaces old 'transactions' table).
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(InventoryStockMovement::class, 'inventory_item_id');
    }

    /**
     * Active distributions (issued/borrowed states for returnable non-consumables).
     */
    public function issuances(): HasMany
    {
        return $this->hasMany(InventoryIssuance::class, 'inventory_item_id');
    }
}
