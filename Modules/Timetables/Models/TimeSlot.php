<?php

namespace Modules\Timetables\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'template_id',
        'name',
        'start_time',
        'end_time',
        'is_break',
        'color',
        'is_locked',
    ];

    protected $casts = [
        'is_break' => 'boolean',
    ];
}
