<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryStockAdjustment extends Model
{
    use BelongsToTenant;

    protected $table = 'inventory_stock_adjustments';

    protected $fillable = [
        'school_id',
        'inventory_location_id',
        'adjustment_number',
        'status', // draft, completed
        'conducted_date',
        'conducted_by_id',
    ];

    protected $casts = [
        'conducted_date' => 'date',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'inventory_location_id');
    }

    public function conductedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conducted_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryStockAdjustmentItem::class, 'stock_adjustment_id');
    }
}
