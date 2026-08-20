<?php

namespace Modules\Hostels\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hostel extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = ['school_id', 'name', 'type', 'capacity', 'description', 'status'];

    public function buildings(): HasMany
    {
        return $this->hasMany(HostelBuilding::class, 'hostel_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(HostelRoom::class, 'hostel_id');
    }

    public function staff(): HasMany
    {
        return $this->hasMany(HostelStaff::class, 'hostel_id');
    }
}
