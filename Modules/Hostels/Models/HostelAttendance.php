<?php

namespace Modules\Hostels\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostelAttendance extends Model
{
    use BelongsToTenant;

    protected $fillable = ['school_id', 'hostel_id', 'recorded_by_user_id', 'date', 'type'];

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class, 'hostel_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(HostelAttendanceStudent::class, 'hostel_attendance_id');
    }
}
