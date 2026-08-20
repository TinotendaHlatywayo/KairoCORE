<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FixedAsset extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'inventory_item_id',
        'asset_number',
        'serial_number',
        'acquisition_date',
        'purchase_cost',
        'salvage_value',
        'useful_life_years',
        'depreciation_method',
        'current_value',
        'warranty_expiry',
        'funding_source',
        'insurance_policy_number',
        'assigned_location_id',
        'custodian_id',
        'status',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'warranty_expiry' => 'date',
        'purchase_cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'current_value' => 'decimal:2',
        'useful_life_years' => 'integer',
    ];

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'assigned_location_id');
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custodian_id');
    }

    public function depreciationSchedules(): HasMany
    {
        return $this->hasMany(DepreciationSchedule::class, 'fixed_asset_id');
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(AssetMaintenanceLog::class, 'fixed_asset_id');
    }
}
