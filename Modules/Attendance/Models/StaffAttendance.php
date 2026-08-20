<?php

namespace Modules\Attendance\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    use BelongsToTenant;

    protected $table = 'staff_attendances';

    protected $fillable = [
        'school_id',
        'user_id',
        'date',
        'status',
        'check_in_time',
        'check_out_time',
        'method',
        'marked_by_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by_id');
    }
}
