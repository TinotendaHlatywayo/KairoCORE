<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceivedItem extends Model
{
    protected $fillable = [
        'goods_received_note_id',
        'inventory_item_id',
        'quantity_accepted',
        'quantity_rejected',
        'rejection_reason',
        'batch_number',
        'expiry_date',
    ];

    protected $casts = [
        'quantity_accepted' => 'integer',
        'quantity_rejected' => 'integer',
        'expiry_date' => 'date',
    ];

    public function grn(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class, 'goods_received_note_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
