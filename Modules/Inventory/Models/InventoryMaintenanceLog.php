<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMaintenanceLog extends Model
{
    use BelongsToTenant;

    protected $table = 'inventory_maintenance_logs';

    protected $fillable = [
        'school_id',
        'inventory_item_id',
        'performed_by',
        'maintenance_date',
        'next_due_date',
        'cost',
        'description',
        'status',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'next_due_date' => 'date',
        'cost' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
