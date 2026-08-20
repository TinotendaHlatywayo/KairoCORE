<?php

namespace Modules\Hostels\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostelInspection extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id', 'room_id', 'inspector_user_id', 'inspection_date',
        'cleanliness_score', 'inventory_status_score', 'orderliness_score', 'notes', 'passes_inspection',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(HostelRoom::class, 'room_id');
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_user_id');
    }
}
