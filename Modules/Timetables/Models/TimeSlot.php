<?php

namespace Modules\Timetables\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeSlot extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'template_id',
        'name',
        'type',
        'start_time',
        'end_time',
        'is_break',
        'color',
        'is_locked',
        'period_order',
        'duration_minutes',
    ];

    protected $casts = [
        'is_break' => 'boolean',
        'is_locked' => 'boolean',
        'period_order' => 'integer',
        'duration_minutes' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (TimeSlot $slot) {
            if ($slot->isDirty('type')) {
                $slot->is_break = $slot->type !== 'teaching';
            }

            if ($slot->start_time && $slot->end_time) {
                $start = strtotime($slot->start_time);
                $end = strtotime($slot->end_time);

                if ($start !== false && $end !== false) {
                    $slot->duration_minutes = max(0, (int) round(($end - $start) / 60));
                }
            }
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TimetableTemplate::class, 'template_id');
    }

    /**
     * Determine whether this slot overlaps any other slot in the same school.
     * Used to surface conflicts in the Time Slots resource.
     */
    public function hasConflicts(): bool
    {
        return static::query()
            ->where('school_id', $this->school_id)
            ->where('id', '!=', $this->id)
            ->where(function ($query) {
                $query->where('start_time', '<', $this->end_time)
                    ->where('end_time', '>', $this->start_time);
            })
            ->exists();
    }
}