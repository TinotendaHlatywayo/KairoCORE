<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcurementRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'request_number',
        'requester_id',
        'department_id',
        'status',
        'urgency',
        'notes',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProcurementRequestItem::class, 'procurement_request_id');
    }
}
