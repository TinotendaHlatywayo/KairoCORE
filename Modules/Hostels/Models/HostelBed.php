<?php

namespace Modules\Hostels\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostelBed extends Model
{
    use BelongsToTenant;

    protected $fillable = ['school_id', 'room_id', 'bed_number', 'qr_code', 'barcode', 'condition', 'status', 'cleaning_status'];

    public function room(): BelongsTo
    {
        return $this->belongsTo(HostelRoom::class, 'room_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(HostelAllocation::class, 'bed_id');
    }
}
