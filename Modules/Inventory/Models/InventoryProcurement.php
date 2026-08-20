<?php

declare(strict_types=1);

namespace Modules\Inventory\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryProcurement extends Model
{
    use BelongsToTenant;

    protected $table = 'inventory_procurements';

    protected $fillable = [
        'school_id',
        'requested_by_id',
        'approved_by_id',
        'supplier_id',
        'type',
        'status',
        'total_amount',
        'items_payload',
        'reference_number',
    ];

    protected $casts = [
        'items_payload' => 'array',
        'total_amount' => 'decimal:2',
    ];

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(InventorySupplier::class, 'supplier_id');
    }
}
