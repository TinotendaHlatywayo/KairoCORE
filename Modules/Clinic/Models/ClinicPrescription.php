<?php

namespace Modules\Clinic\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Models\InventoryItem;

class ClinicPrescription extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id', 'clinic_visit_id', 'inventory_item_id', 'medicine_name',
        'dosage', 'frequency', 'quantity_prescribed', 'quantity_dispensed', 'dispensed_at',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(ClinicVisit::class, 'clinic_visit_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
