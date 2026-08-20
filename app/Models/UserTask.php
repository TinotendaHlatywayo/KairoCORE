<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Personal / assigned task shown in the unified Schedule + Todo experience.
 *
 * Tasks are tenant-scoped. A user may view tasks either assigned to them or
 * created by them. Users holding the "tasks.assign" permission may create
 * tasks for (and re-assign them to) other school members.
 *
 * Privacy is enforced at query time: the `visibleTo` scope is the ONLY way a
 * task can be fetched inside Livewire components / controllers, so changing
 * an ID can never reveal another user's task.
 */
class UserTask extends Model
{
    use BelongsToTenant;

    public const STATUS_OPEN = 'open';

    public const STATUS_DONE = 'done';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITIES = [
        self::PRIORITY_LOW => 'Low',
        self::PRIORITY_MEDIUM => 'Medium',
        self::PRIORITY_HIGH => 'High',
    ];

    public const RECURRENCE_NONE = 'none';

    public const RECURRENCE_DAILY = 'daily';

    public const RECURRENCE_WEEKLY = 'weekly';

    public const RECURRENCE_MONTHLY = 'monthly';

    public const RECURRENCES = [
        self::RECURRENCE_NONE => 'Does not repeat',
        self::RECURRENCE_DAILY => 'Daily',
        self::RECURRENCE_WEEKLY => 'Weekly',
        self::RECURRENCE_MONTHLY => 'Monthly',
    ];

    protected $fillable = [
        'school_id',
        'created_by_id',
        'assigned_to_id',
        'title',
        'description',
        'due_date',
        'due_time',
        'priority',
        'important',
        'status',
        'completed_at',
        'reminder_at',
        'reminder_sent_at',
        'recurrence',
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'important' => 'boolean',
        'completed_at' => 'datetime',
        'reminder_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['is_overdue'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeDone(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DONE);
    }

    public function scopeImportant(Builder $query): Builder
    {
        return $query->where('important', true);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_OPEN)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString());
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->where('due_date', now()->toDateString());
    }

    /**
     * The only query path allowed for fetching tasks in the schedule/todo UI.
     * A task is reachable by a user if it was assigned to them or created by
     * them — never by blind ID lookups.
     */
    public function scopeVisibleTo(Builder $query, int $userId): Builder
    {
        return $query->where(function (Builder $q) use ($userId) {
            $q->where('assigned_to_id', $userId)->orWhere('created_by_id', $userId);
        });
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to_id', $userId);
    }

    public function scopeCreatedBy(Builder $query, int $userId): Builder
    {
        return $query->where('created_by_id', $userId);
    }

    public function scopeDueBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        if (! $from && ! $to) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($from, $to) {
            if ($from) {
                $q->whereDate('due_date', '>=', $from);
            }
            if ($to) {
                $q->whereDate('due_date', '<=', $to);
            }
        });
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%");
        });
    }

    public function isDone(): bool
    {
        return $this->status === self::STATUS_DONE;
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OPEN
            && $this->due_date !== null
            && $this->due_date->lt(now()->startOfDay());
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->isOverdue();
    }

    public function markDone(): void
    {
        $this->status = self::STATUS_DONE;
        $this->completed_at = now();
        $this->save();
    }

    public function markOpen(): void
    {
        $this->status = self::STATUS_OPEN;
        $this->completed_at = null;
        $this->save();
    }
}
