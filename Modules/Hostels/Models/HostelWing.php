<?php

namespace Modules\Hostels\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostelWing extends Model
{
    use BelongsToTenant;

    protected $fillable = ['school_id', 'floor_id', 'name', 'description'];

    public function floor(): BelongsTo
    {
        return $this->belongsTo(HostelFloor::class, 'floor_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(HostelRoom::class, 'wing_id');
    }
}
