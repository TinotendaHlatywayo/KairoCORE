<?php

namespace Modules\Hostels\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostelBuilding extends Model
{
    use BelongsToTenant;

    protected $fillable = ['school_id', 'hostel_id', 'name', 'description'];

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class, 'hostel_id');
    }

    public function floors(): HasMany
    {
        return $this->hasMany(HostelFloor::class, 'building_id');
    }
}
