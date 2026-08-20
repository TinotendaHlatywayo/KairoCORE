<?php

namespace Modules\Hostels\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostelFloor extends Model
{
    use BelongsToTenant;

    protected $fillable = ['school_id', 'building_id', 'floor_number', 'floor_name'];

    public function building(): BelongsTo
    {
        return $this->belongsTo(HostelBuilding::class, 'building_id');
    }

    public function wings(): HasMany
    {
        return $this->hasMany(HostelWing::class, 'floor_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(HostelRoom::class, 'floor_id');
    }
}
