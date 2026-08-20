<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryIssuance extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'inventory_item_id',
        'inventory_location_id',
        'inventory_batch_id',
        'issued_to_type',
        'issued_to_id',
        'quantity',
        'is_returnable',
        'expected_return_date',
        'actual_return_date',
        'condition_on_issue',
        'condition_on_return',
        'status',
        'remarks',
        'issued_by_id',
    ];

    protected $casts = [
        'is_returnable' => 'boolean',
        'expected_return_date' => 'date',
        'actual_return_date' => 'date',
        'quantity' => 'integer',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class, 'inventory_batch_id');
    }

    public function issuedTo(): MorphTo
    {
        return $this->morphTo();
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_id');
    }
}
