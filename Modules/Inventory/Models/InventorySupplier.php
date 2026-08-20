<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventorySupplier extends Model
{
    use BelongsToTenant;

    protected $table = 'inventory_suppliers';

    protected $fillable = [
        'school_id',
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'rating',
        'lead_time_days',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class, 'inventory_supplier_id');
    }

    public function procurements(): HasMany
    {
        return $this->hasMany(InventoryProcurement::class, 'supplier_id');
    }
}
