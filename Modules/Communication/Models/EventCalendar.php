<?php

namespace Modules\Communication\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * School-wide calendar event (staff meetings, parents meetings, assemblies,
 * sports, examinations, holidays). Events are tenant-scoped and shared with
 * the whole school; they remain a distinct entity from personal tasks.
 */
class EventCalendar extends Model
{
    use BelongsToTenant;

    protected $table = 'communication_events';

    public const RECURRENCE_NONE = 'none';

    public const RECURRENCE_DAILY = 'daily';

    public const RECURRENCE_WEEKLY = 'weekly';

    public const RECURRENCE_MONTHLY = 'monthly';

    public const RECURRENCES = [
        'none' => 'Does not repeat',
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
    ];

    public const CATEGORIES = [
        'academic' => 'Academic Event',
        'meetings' => 'Board/Staff Meeting',
        'sports' => 'Sports Event',
        'examinations' => 'Examination Period',
        'holiday' => 'Public Holiday',
        'general' => 'General Event',
    ];

    protected $fillable = [
        'school_id',
        'created_by_id',
        'organizer_id',
        'title',
        'description',
        'category',
        'start_time',
        'end_time',
        'all_day',
        'location',
        'reminder_minutes',
        'reminder_sent_at',
        'color',
        'target_roles',
        'target_sections',
        'recurrence',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'all_day' => 'boolean',
        'reminder_sent_at' => 'datetime',
        'target_roles' => 'array',
        'target_sections' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    /**
     * Events overlapping the given range (inclusive), including multi-day
     * events that merely intersect the window. Used by every calendar view so
     * only the required range is ever loaded.
     */
    public function scopeOverlappingRange(Builder $query, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $query->where(function (Builder $q) use ($start, $end) {
            $q->whereBetween('start_time', [$start, $end])
                ->orWhere(function (Builder $q) use ($start) {
                    $q->where('start_time', '<', $start)
                        ->where('end_time', '>=', $start);
                })
                ->orWhere(function (Builder $q) use ($end) {
                    $q->where('start_time', '<=', $end)
                        ->where('end_time', '>', $end);
                });
        });
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function scopeStartOn(Builder $query, CarbonInterface $date): Builder
    {
        return $query->whereDate('start_time', $date->toDateString());
    }

    public function scopeUpcoming(Builder $query, ?int $limit = null): Builder
    {
        $query->where('start_time', '>=', now());

        if ($limit) {
            $query->limit($limit);
        }

        return $query;
    }
}
