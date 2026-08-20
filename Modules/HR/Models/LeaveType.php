<?php

namespace Modules\HR\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'days_per_year',
        'carry_forward',
        'max_accumulation',
        'probation_restricted_days',
        'gender_restriction',
        'service_length_months_required',
    ];

    protected $casts = [
        'days_per_year' => 'integer',
        'carry_forward' => 'boolean',
        'max_accumulation' => 'integer',
        'probation_restricted_days' => 'integer',
        'service_length_months_required' => 'integer',
    ];

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
