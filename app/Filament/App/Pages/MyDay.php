<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ManagesTasks;
use App\Models\UserTask;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Communication\Models\EventCalendar;

/**
 * My Day — the personal, action-oriented Todo companion to the Schedule.
 *
 * Stays focused on tasks (no event duplication) while showing "Today's
 * Schedule" purely as contextual awareness of calendar events. Provides the
 * standard Todo views: MY DAY, IMPORTANT, ASSIGNED TO ME, MY TASKS and
 * COMPLETED, plus an OVERDUE group.
 */
class MyDay extends Page
{
    use ManagesTasks;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationGroup = 'Schedule & Tasks';

    public static function getNavigationGroup(): ?string
    {

        return __(static::$navigationGroup);

    }

    protected static ?string $title = 'My Day';

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    protected static string $view = 'filament.app.pages.my-day';

    public string $tab = 'my_day';

    public string $search = '';

    public bool $showCompleted = false;

    public const TABS = [
        'my_day' => 'My Day',
        'important' => 'Important',
        'assigned' => 'Assigned to Me',
        'mine' => 'My Tasks',
        'completed' => 'Completed',
    ];

    protected $listeners = [
        'taskCreated' => '$refresh',
        'eventCalendarUpdated' => '$refresh',
    ];

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()?->school_id !== null;
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, self::TABS)) {
            $this->tab = $tab;
        }
    }

    public function getTabCountsProperty(): array
    {
        $user = $this->user();
        if (! $user) {
            return array_fill_keys(array_keys(self::TABS), 0);
        }

        return [
            'my_day' => UserTask::query()->visibleTo($user->id)->open()->dueToday()->count(),
            'important' => UserTask::query()->visibleTo($user->id)->open()->important()->count(),
            'assigned' => UserTask::query()->visibleTo($user->id)->open()->assignedTo($user->id)->count(),
            'mine' => UserTask::query()->visibleTo($user->id)->open()->createdBy($user->id)->count(),
            'completed' => UserTask::query()->visibleTo($user->id)->done()->count(),
        ];
    }

    public function getOverdueTasksProperty(): Collection
    {
        $user = $this->user();
        if (! $user) {
            return collect();
        }

        return UserTask::query()
            ->visibleTo($user->id)
            ->overdue()
            ->with(['assignee:id,name', 'creator:id,name'])
            ->search($this->search)
            ->orderByDesc('due_date')
            ->get();
    }

    public function getMyDayTasksProperty(): Collection
    {
        $user = $this->user();
        if (! $user) {
            return collect();
        }

        return UserTask::query()
            ->visibleTo($user->id)
            ->open()
            ->dueToday()
            ->with(['assignee:id,name', 'creator:id,name'])
            ->search($this->search)
            ->orderByRaw('CASE WHEN due_time IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_time')
            ->get();
    }

    public function getTodayScheduleProperty(): Collection
    {
        $user = $this->user();
        if (! $user) {
            return collect();
        }

        $day = Carbon::today();

        return EventCalendar::query()
            ->where(function ($q) use ($day) {
                $q->whereDate('start_time', $day->toDateString())
                    ->orWhere(function ($q) use ($day) {
                        $q->where('start_time', '<', $day)
                            ->where('end_time', '>=', $day->copy()->endOfDay());
                    });
            })
            ->orderBy('start_time')
            ->limit(12)
            ->get();
    }

    public function getTodayProperty(): array
    {
        $day = Carbon::today();

        return [
            'date' => $day->format('Y-m-d'),
            'label' => $day->format('l, j F Y'),
        ];
    }

    public function getActiveTasksProperty(): Collection
    {
        $user = $this->user();
        if (! $user) {
            return collect();
        }

        $query = UserTask::query()
            ->visibleTo($user->id)
            ->with(['assignee:id,name', 'creator:id,name'])
            ->search($this->search);

        match ($this->tab) {
            'my_day' => $query->open()->dueToday(),
            'important' => $query->open()->important(),
            'assigned' => $query->open()->assignedTo($user->id),
            'mine' => $query->open()->createdBy($user->id),
            'completed' => $query->done(),
            default => $query->open(),
        };

        return $query
            ->when($this->tab === 'completed', fn ($q) => $q->orderByDesc('completed_at'))
            ->orderBy('due_date')
            ->orderBy('due_time')
            ->limit(120)
            ->get();
    }

    public function toggleShowCompleted(): void
    {
        $this->showCompleted = ! $this->showCompleted;
    }
}
