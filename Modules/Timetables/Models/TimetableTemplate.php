<?php

namespace Modules\Timetables\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TimetableTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable = ['school_id', 'name', 'is_active', 'settings'];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function ($template) {
            if ($template->is_active) {
                // Automatically deactivate all other templates for this school
                static::where('school_id', $template->school_id)
                    ->where('id', '!=', $template->id)
                    ->update(['is_active' => false]);
            }
        });
    }

    public function timeSlots()
    {
        return $this->hasMany(TimeSlot::class, 'template_id');
    }
}
