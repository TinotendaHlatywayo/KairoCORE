<?php

namespace Modules\Inventory\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenanceLog extends Model
{
    use BelongsToTenant;

    protected $table = 'asset_maintenance_logs';

    protected $fillable = [
        'school_id',
        'fixed_asset_id',
        'title',
        'type',
        'schedule_type',
        'recurrence_interval_days',
        'scheduled_date',
        'completed_date',
        'cost',
        'performed_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
        'cost' => 'decimal:2',
        'recurrence_interval_days' => 'integer',
    ];

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }
}
