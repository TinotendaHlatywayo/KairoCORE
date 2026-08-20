<?php

namespace Modules\Hostels\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HostelRoom extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = ['school_id', 'hostel_id', 'wing_id', 'floor_id', 'room_number', 'name', 'room_type', 'condition', 'status', 'capacity'];

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class, 'hostel_id');
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(HostelFloor::class, 'floor_id');
    }

    public function wing(): BelongsTo
    {
        return $this->belongsTo(HostelWing::class, 'wing_id');
    }

    public function beds(): HasMany
    {
        return $this->hasMany(HostelBed::class, 'room_id');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(HostelInspection::class, 'room_id');
    }
}
