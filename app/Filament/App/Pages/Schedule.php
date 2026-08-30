<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ManagesTasks;
use App\Models\User;
use App\Models\UserTask;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Communication\Models\EventCalendar;

/**
 * Unified Schedule — the visual source of truth for events AND tasks.
 *
 * One page exposing MONTH / WEEK / DAY / AGENDA views over the same two
 * records (school events + personal tasks). Clicking a date reveals a daily
 * agenda with quick-create for both events and tasks; timed items can be
 * dragged between dates. Everything is tenant-scoped and tasks are only ever
 * queried through the `visibleTo` scope (privacy enforced at query time).
 */
class Schedule extends Page
{
    use ManagesTasks;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Schedule & Tasks';

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): ?string
    {

        return __(static::$navigationGroup);

    }

    protected static ?string $title = 'Schedule';

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    protected static string $view = 'filament.app.pages.schedule';

    public string $viewMode = 'month';

    public string $month = '';

    public string $currentDate = '';

    public string $search = '';

    public bool $showEvents = true;

    public bool $showTasks = true;

    public bool $importantOnly = false;

    public bool $assignedOnly = false;

    public bool $mineOnly = false;

    public bool $showCompleted = false;

    public bool $dayPanelOpen = false;

    // Prev/next navigation step: auto (follows the active view) or a fixed
    // week / month / year jump.
    public string $step = 'auto';

    // Event quick-create / edit form
    public bool $eventModalOpen = false;

    public bool $editingEvent = false;

    public ?int $eventId = null;

    public array $eventForm = [];

    public bool $ignoreConflicts = false;

    public array $conflicts = [];

    // Delete confirmation for events (task delete state lives in the trait)
    public ?int $deleteEventId = null;

    // Date-picker month used by the mini navigation calendar(s)
    public string $pickerMonth = '';

    // Agenda range control
    public string $rangePreset = 'next7';

    public ?string $rangeStart = null;

    public ?string $rangeEnd = null;

    protected $listeners = [
        'eventCalendarUpdated' => '$refresh',
        'taskCreated' => '$refresh',
    ];

    public function mount(): void
    {
        $this->month = $this->month ?: now()->format('Y-m');
        $this->currentDate = $this->currentDate ?: now()->format('Y-m-d');
        $this->pickerMonth = $this->pickerMonth ?: $this->month;

        $this->openFromDeepLink();
    }

    /**
     * Open a specific task/event directly when arriving via a deep link
     * (e.g. from the topbar Command Center: ?task=12 or ?event=7).
     */
    protected function openFromDeepLink(): void
    {
        $taskId = request()->query('task');
        $eventId = request()->query('event');

        if ($taskId !== null && is_numeric($taskId)) {
            $task = $this->findOwnedTask((int) $taskId);
            if (! $task) {
                return;
            }

            $this->currentDate = $task->due_date?->format('Y-m-d') ?? $this->currentDate;
            $this->month = $task->due_date?->format('Y-m') ?? $this->month;
            $this->pickerMonth = $this->month;
            $this->editTask($task->id);

            return;
        }

        if ($eventId !== null && is_numeric($eventId)) {
            $event = EventCalendar::query()->find((int) $eventId);
            if (! $event) {
                return;
            }

            $this->currentDate = $event->start_time->format('Y-m-d');
            $this->month = $event->start_time->format('Y-m');
            $this->pickerMonth = $this->month;
            $this->editEvent($event->id);
        }
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()?->school_id !== null;
    }

    protected function user(): ?User
    {
        return auth()->user();
    }

    // ── View / navigation ─────────────────────────────────────────────────

    public function setView(string $view): void
    {
        if (in_array($view, ['month', 'week', 'day', 'agenda'], true)) {
            $this->viewMode = $view;
            $this->dayPanelOpen = false;
        }
    }

    public function goToday(): void
    {
        $today = now();
        $this->month = $today->format('Y-m');
        $this->currentDate = $today->format('Y-m-d');
        $this->pickerMonth = $this->month;
        $this->dayPanelOpen = false;
    }

    public function prevPeriod(): void
    {
        $this->nudgePeriod(-1);
    }

    public function nextPeriod(): void
    {
        $this->nudgePeriod(1);
    }

    protected function nudgePeriod(int $direction): void
    {
        $date = Carbon::parse($this->currentDate);

        if ($this->step === 'auto') {
            switch ($this->viewMode) {
                case 'month':
                    $this->currentDate = $date->addMonthsNoOverflow($direction)->format('Y-m-d');
                    break;
                case 'week':
                    $this->currentDate = $date->addWeeks($direction)->format('Y-m-d');
                    break;
                case 'day':
                    $this->currentDate = $date->addDays($direction)->format('Y-m-d');
                    break;
                case 'agenda':
                    $this->currentDate = $date->addDays($direction * 7)->format('Y-m-d');
                    break;
            }
        } else {
            $this->currentDate = match ($this->step) {
                'week' => $date->addWeeks($direction)->format('Y-m-d'),
                'month' => $date->addMonthsNoOverflow($direction)->format('Y-m-d'),
                'year' => $date->addYears($direction)->format('Y-m-d'),
                default => $this->currentDate,
            };
        }

        $this->month = Carbon::parse($this->currentDate)->format('Y-m');
        $this->pickerMonth = $this->month;
        $this->dayPanelOpen = false;
    }

    public function setStep(string $step): void
    {
        if (in_array($step, ['auto', 'week', 'month', 'year'], true)) {
            $this->step = $step;
        }
    }

    public function selectDate(string $date): void
    {
        if (! $this->validDate($date)) {
            return;
        }

        $this->currentDate = $date;
        $this->pickerMonth = Carbon::parse($date)->format('Y-m');

        if ($this->viewMode === 'month') {
            $this->month = $this->pickerMonth;
        }

        $this->dayPanelOpen = true;
    }

    public function jumpToDate(string $date): void
    {
        if (! $this->validDate($date)) {
            return;
        }

        $this->currentDate = $date;
        $this->month = Carbon::parse($date)->format('Y-m');
        $this->pickerMonth = $this->month;
        $this->dayPanelOpen = true;
    }

    public function closeDayPanel(): void
    {
        $this->dayPanelOpen = false;
    }

    public function jumpYear(int $direction): void
    {
        $this->currentDate = Carbon::parse($this->currentDate)->addYears($direction)->format('Y-m-d');
        $this->pickerMonth = Carbon::parse($this->currentDate)->format('Y-m');
        $this->month = $this->pickerMonth;
    }

    public function dismissConflicts(): void
    {
        $this->conflicts = [];
        $this->ignoreConflicts = false;
    }

    /**
     * Panel-aware home link used by the schedule toolbar.
     */
    public function homeUrl(): string
    {
        return Dashboard::getUrl();
    }

    /**
     * Default event category for a fresh event form. Students override this
     * so their events are categorised as academic by default.
     */
    protected function defaultEventCategory(): string
    {
        return 'general';
    }

    /**
     * Target roles applied when creating a new event. The staff schedule keeps
     * events untargeted (visible to the whole school); students override this
     * so events they create only reach students.
     */
    protected function eventTargetRoles(): ?array
    {
        return null;
    }

    protected function validDate(string $date): bool
    {
        try {
            Carbon::parse($date);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function previousPickerMonth(): void
    {
        $this->pickerMonth = Carbon::parse($this->pickerMonth.'-01')->subMonth()->format('Y-m');
    }

    public function nextPickerMonth(): void
    {
        $this->pickerMonth = Carbon::parse($this->pickerMonth.'-01')->addMonth()->format('Y-m');
    }

    public function previousPickerYear(): void
    {
        $this->pickerMonth = Carbon::parse($this->pickerMonth.'-01')->subYear()->format('Y-m');
    }

    public function nextPickerYear(): void
    {
        $this->pickerMonth = Carbon::parse($this->pickerMonth.'-01')->addYear()->format('Y-m');
    }

    // ── Range resolution (performance: only load what is visible) ─────────

    protected function resolveRange(): array
    {
        $date = Carbon::parse($this->currentDate);

        switch ($this->viewMode) {
            case 'month':
                $first = Carbon::parse($this->month.'-01');
                $gridStart = $first->copy()->startOfWeek(Carbon::SUNDAY)->startOfDay();
                $gridEnd = $first->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY)->endOfDay();

                return [$gridStart, $gridEnd];

            case 'week':
                $start = $date->copy()->startOfWeek(Carbon::SUNDAY)->startOfDay();
                $end = $start->copy()->addDays(6)->endOfDay();

                return [$start, $end];

            case 'day':
                return [$date->copy()->startOfDay(), $date->copy()->endOfDay()];

            case 'agenda':
            default:
                [$from, $to] = $this->resolveAgendaRange($date);

                return [$from, $to];
        }
    }

    protected function resolveAgendaRange(Carbon $anchor): array
    {
        switch ($this->rangePreset) {
            case 'today':
                return [$anchor->copy()->startOfDay(), $anchor->copy()->endOfDay()];

            case 'this_week':
                return [
                    $anchor->copy()->startOfWeek(Carbon::SUNDAY)->startOfDay(),
                    $anchor->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay(),
                ];

            case 'next30':
                return [$anchor->copy()->startOfDay(), $anchor->copy()->addDays(29)->endOfDay()];

            case 'custom':
                if ($this->rangeStart && $this->rangeEnd && $this->rangeStart <= $this->rangeEnd) {
                    return [
                        Carbon::parse($this->rangeStart)->startOfDay(),
                        Carbon::parse($this->rangeEnd)->endOfDay(),
                    ];
                }

                return [$anchor->copy()->startOfDay(), $anchor->copy()->addDays(6)->endOfDay()];

            case 'next7':
            default:
                return [$anchor->copy()->startOfDay(), $anchor->copy()->addDays(6)->endOfDay()];
        }
    }

    public function setRangePreset(string $preset): void
    {
        if (in_array($preset, ['today', 'this_week', 'next7', 'next30', 'custom'], true)) {
            $this->rangePreset = $preset;
            if ($preset === 'custom' && ! $this->rangeStart) {
                $this->rangeStart = $this->currentDate;
                $this->rangeEnd = Carbon::parse($this->currentDate)->addWeek()->format('Y-m-d');
            }
        }
    }

    /**
     * Two-click range selection: first call pins the start, second pins the
     * end (calling it on the same date again resets the range).
     */
    public function selectRangeDate(string $date): void
    {
        if (! $this->validDate($date)) {
            return;
        }

        if ($this->rangePreset !== 'custom') {
            $this->rangePreset = 'custom';
            $this->rangeStart = $date;
            $this->rangeEnd = null;

            return;
        }

        if (! $this->rangeStart) {
            $this->rangeStart = $date;

            return;
        }

        if ($this->rangeEnd) {
            $this->rangeStart = $date;
            $this->rangeEnd = null;

            return;
        }

        $start = Carbon::parse(min($this->rangeStart, $date));
        $end = Carbon::parse(max($this->rangeStart, $date));
        $this->rangeStart = $start->format('Y-m-d');
        $this->rangeEnd = $end->format('Y-m-d');
    }

    public function clearRange(): void
    {
        $this->rangeStart = null;
        $this->rangeEnd = null;
        $this->rangePreset = 'next7';
    }

    // ── Data loading ──────────────────────────────────────────────────────

    public function getEventsProperty(): Collection
    {
        if (! $this->showEvents) {
            return collect();
        }

        [$start, $end] = $this->resolveRange();

        return EventCalendar::query()
            ->overlappingRange($start, $end)
            ->with(['organizer:id,name', 'creator:id,name'])
            ->search($this->search)
            ->orderBy('start_time')
            ->get();
    }

    public function getTasksProperty(): Collection
    {
        $user = $this->user();
        if (! $user || ! $this->showTasks) {
            return collect();
        }

        [$start, $end] = $this->resolveRange();

        return UserTask::query()
            ->visibleTo($user->id)
            ->whereNotNull('due_date')
            ->dueBetween($start->toDateString(), $end->toDateString())
            ->with(['assignee:id,name', 'creator:id,name'])
            ->search($this->search)
            ->when($this->importantOnly, fn ($q) => $q->important())
            ->when($this->assignedOnly, fn ($q) => $q->assignedTo($user->id))
            ->when($this->mineOnly, fn ($q) => $q->createdBy($user->id))
            ->when(! $this->showCompleted, fn ($q) => $q->open())
            ->orderBy('due_date')
            ->orderBy('due_time')
            ->get();
    }

    /**
     * Occurrence dates for a recurring (or plain) record within a range.
     * Plain records return their own date(s); recurring records expand onto
     * their repeat dates so no duplicate rows are ever stored.
     *
     * @return array<int, string> list of "Y-m-d"
     */
    protected function occurrenceDates(Carbon $baseDate, string $rule, Carbon $rangeStart, Carbon $rangeEnd, ?Carbon $endTime = null): array
    {
        $dates = [];
        $cursor = $baseDate->copy();
        $guard = 0;

        while ($cursor->lte($rangeEnd) && $guard < 500) {
            $guard++;

            if ($cursor->gte($rangeStart)) {
                if ($endTime && $endTime->gt($cursor)) {
                    $span = min($endTime->copy()->endOfDay(), $rangeEnd);
                    for ($d = $cursor->copy(); $d->lte($span); $d->addDay()) {
                        $dates[$d->format('Y-m-d')] = $d->format('Y-m-d');
                    }
                } else {
                    $dates[$cursor->format('Y-m-d')] = $cursor->format('Y-m-d');
                }
            }

            if ($rule === UserTask::RECURRENCE_NONE) {
                break;
            }

            switch ($rule) {
                case 'daily':
                    $cursor->addDay();
                    break;
                case 'weekly':
                    $cursor->addWeek();
                    break;
                case 'monthly':
                    $cursor->addMonthNoOverflow();
                    break;
                default:
                    break 2;
            }
        }

        return array_values($dates);
    }

    protected function eventBucketMap(Collection $events, Carbon $start, Carbon $end): array
    {
        $map = [];

        foreach ($events as $event) {
            $base = $event->start_time;
            $occ = $this->occurrenceDates($base, $event->recurrence ?? 'none', $start, $end, $event->end_time);

            foreach ($occ as $date) {
                $map[$date][] = [
                    'type' => 'event',
                    'id' => $event->id,
                    'title' => $event->title,
                    'location' => $event->location,
                    'all_day' => (bool) $event->all_day,
                    'color' => $event->color,
                    'category' => $event->category,
                    'start_time' => $base->format('H:i'),
                    'end_time' => $event->end_time ? $event->end_time->format('H:i') : null,
                    'start_iso' => $event->start_time->toIso8601String(),
                    'end_iso' => $event->end_time?->toIso8601String(),
                ];
            }
        }

        return $map;
    }

    protected function taskBucketMap(Collection $tasks, Carbon $start, Carbon $end): array
    {
        $map = [];

        foreach ($tasks as $task) {
            $base = $task->due_date->copy()->startOfDay();
            $rule = $task->recurrence ?? 'none';

            if ($task->isDone() && $rule !== 'none') {
                continue;
            }

            $occ = $this->occurrenceDates($base, $rule, $start, $end);

            foreach ($occ as $date) {
                $map[$date][] = [
                    'type' => 'task',
                    'id' => $task->id,
                    'title' => $task->title,
                    'due_time' => $task->due_time,
                    'important' => (bool) $task->important,
                    'done' => $task->isDone(),
                    'priority' => $task->priority,
                    'assigned_to_id' => $task->assigned_to_id,
                    'due_date' => $date,
                ];
            }
        }

        return $map;
    }

    // ── Month grid ────────────────────────────────────────────────────────

    public function getMonthGridProperty(): array
    {
        $first = Carbon::parse($this->month.'-01');
        $gridStart = $first->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $first->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY)->endOfDay();
        $today = now()->format('Y-m-d');

        $events = $this->showEvents ? $this->events : collect();
        $tasks = $this->showTasks ? $this->tasks : collect();
        $eventMap = $this->eventBucketMap($events, $gridStart, $gridEnd);
        $taskMap = $this->taskBucketMap($tasks, $gridStart, $gridEnd);

        $cells = [];
        $cursor = $gridStart->copy();

        for ($i = 0; $i < 42; $i++) {
            $date = $cursor->format('Y-m-d');
            $cells[] = [
                'date' => $date,
                'day' => $cursor->day,
                'isCurrentMonth' => $cursor->format('Y-m') === $this->month,
                'isToday' => $date === $today,
                'isSelected' => $date === $this->currentDate,
                'isPast' => $date < $today,
                'events' => $eventMap[$date] ?? [],
                'tasks' => $taskMap[$date] ?? [],
            ];
            $cursor->addDay();
        }

        return [
            'label' => $first->format('F Y'),
            'weekdays' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            'cells' => $cells,
        ];
    }

    public function getWeekDaysProperty(): array
    {
        $date = Carbon::parse($this->currentDate);
        $start = $date->copy()->startOfWeek(Carbon::SUNDAY);
        $end = $start->copy()->addDays(6)->endOfDay();
        $today = now()->format('Y-m-d');

        $eventMap = $this->showEvents ? $this->eventBucketMap($this->events, $start, $end) : [];
        $taskMap = $this->showTasks ? $this->taskBucketMap($this->tasks, $start, $end) : [];

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i);
            $dateKey = $day->format('Y-m-d');
            $days[] = [
                'date' => $dateKey,
                'label' => $day->format('D'),
                'dayNum' => $day->day,
                'isToday' => $dateKey === $today,
                'isSelected' => $dateKey === $this->currentDate,
                'events' => $eventMap[$dateKey] ?? [],
                'tasks' => $taskMap[$dateKey] ?? [],
            ];
        }

        return $days;
    }

    // ── Day agenda ────────────────────────────────────────────────────────

    public function getDayAgendaProperty(): array
    {
        $date = Carbon::parse($this->currentDate);
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $eventMap = $this->showEvents ? $this->eventBucketMap($this->events, $start, $end) : [];
        $taskMap = $this->showTasks ? $this->taskBucketMap($this->tasks, $start, $end) : [];

        $dateKey = $date->format('Y-m-d');
        $events = $eventMap[$dateKey] ?? [];
        $tasks = $taskMap[$dateKey] ?? [];

        $allDay = collect($events)->where('all_day', true)->values()->all();
        $timed = collect($events)->where('all_day', false)
            ->sortBy(fn ($e) => $e['start_time'])
            ->values()->all();
        $tasksNoTime = collect($tasks)->where(fn ($t) => empty($t['due_time']))->values()->all();
        $tasksTimed = collect($tasks)->where(fn ($t) => ! empty($t['due_time']))
            ->sortBy('due_time')->values()->all();

        return [
            'date' => $dateKey,
            'label' => $date->format('l, j F Y'),
            'short' => $date->format('j F'),
            'isToday' => $dateKey === now()->format('Y-m-d'),
            'allDayEvents' => $allDay,
            'timedEvents' => $timed,
            'tasksNoTime' => $tasksNoTime,
            'tasksTimed' => $tasksTimed,
        ];
    }

    // ── Agenda (multi-day list) ───────────────────────────────────────────

    public function getAgendaItemsProperty(): Collection
    {
        [$start, $end] = $this->resolveAgendaRange(Carbon::parse($this->currentDate));

        $eventMap = $this->showEvents ? $this->eventBucketMap($this->events, $start, $end) : [];
        $taskMap = $this->showTasks ? $this->taskBucketMap($this->tasks, $start, $end) : [];

        $items = collect();

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $events = $eventMap[$key] ?? [];
            $tasks = $taskMap[$key] ?? [];

            if (empty($events) && empty($tasks)) {
                continue;
            }

            $entries = collect($events)->map(fn ($e) => $e + ['sort' => $e['start_time']])
                ->concat(collect($tasks)->map(fn ($t) => $t + ['sort' => $t['due_time'] ?? '99:99']))
                ->sortBy('sort');

            $items->push([
                'date' => $key,
                'label' => $d->format('l, j F'),
                'isToday' => $key === now()->format('Y-m-d'),
                'entries' => $entries->values()->all(),
            ]);
        }

        return $items;
    }

    public function getAgendaRangeLabelProperty(): string
    {
        [$start, $end] = $this->resolveAgendaRange(Carbon::parse($this->currentDate));

        return $start->format('j M Y').' – '.$end->format('j M Y');
    }

    // ── Mini calendar (date picker) ───────────────────────────────────────

    public function getPickerMonthsProperty(): array
    {
        $months = [];
        $today = now()->format('Y-m-d');

        foreach ([0, 1] as $offset) {
            $first = Carbon::parse($this->pickerMonth.'-01')->addMonthsNoOverflow($offset);
            $gridStart = $first->copy()->startOfWeek(Carbon::SUNDAY);
            $gridEnd = $first->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

            $cells = [];
            $cursor = $gridStart->copy();
            for ($i = 0; $i < 42; $i++) {
                $date = $cursor->format('Y-m-d');
                $cells[] = [
                    'date' => $date,
                    'day' => $cursor->day,
                    'isCurrentMonth' => $cursor->format('Y-m') === $first->format('Y-m'),
                    'isToday' => $date === $today,
                    'isSelected' => $date === $this->currentDate,
                ];
                $cursor->addDay();
            }

            $months[] = [
                'label' => $first->format('F Y'),
                'key' => $first->format('Y-m'),
                'weekdays' => ['S', 'M', 'T', 'W', 'T', 'F', 'S'],
                'cells' => $cells,
            ];
        }

        return $months;
    }

    // ── Event quick-create / edit ─────────────────────────────────────────

    public function openEventModal(?string $date = null, bool $allDay = false): void
    {
        $target = $date ? Carbon::parse($date) : Carbon::parse($this->currentDate);
        $this->eventId = null;
        $this->editingEvent = false;
        $this->ignoreConflicts = false;
        $this->conflicts = [];
        $this->eventForm = [
            'title' => '',
            'category' => $this->defaultEventCategory(),
            'all_day' => $allDay,
            'start_date' => $target->format('Y-m-d'),
            'start_time' => '09:00',
            'end_date' => $target->format('Y-m-d'),
            'end_time' => '10:00',
            'location' => '',
            'description' => '',
            'color' => '#4f46e5',
            'reminder_minutes' => '30',
            'recurrence' => 'none',
        ];
        $this->eventModalOpen = true;
    }

    public function editEvent(int $id): void
    {
        $event = EventCalendar::query()->find($id);
        if (! $event) {
            return;
        }

        $this->eventId = $event->id;
        $this->editingEvent = true;
        $this->ignoreConflicts = false;
        $this->conflicts = [];
        $this->eventForm = [
            'title' => $event->title,
            'category' => $event->category,
            'all_day' => (bool) $event->all_day,
            'start_date' => $event->start_time->format('Y-m-d'),
            'start_time' => $event->start_time->format('H:i'),
            'end_date' => $event->end_time?->format('Y-m-d') ?? $event->start_time->format('Y-m-d'),
            'end_time' => $event->end_time?->format('H:i') ?? '10:00',
            'location' => $event->location,
            'description' => $event->description,
            'color' => $event->color,
            'reminder_minutes' => (string) $event->reminder_minutes,
            'recurrence' => $event->recurrence,
        ];
        $this->eventModalOpen = true;
    }

    public function closeEventModal(): void
    {
        $this->eventModalOpen = false;
        $this->eventId = null;
        $this->editingEvent = false;
        $this->conflicts = [];
    }

    public function saveEvent(): void
    {
        $user = $this->user();
        if (! $user) {
            return;
        }

        $this->validate([
            'eventForm.title' => ['required', 'string', 'max:255'],
            'eventForm.category' => ['required', 'string'],
            'eventForm.start_date' => ['required', 'date'],
            'eventForm.start_time' => ['nullable', 'date_format:H:i'],
            'eventForm.end_date' => ['nullable', 'date'],
            'eventForm.end_time' => ['nullable', 'date_format:H:i'],
        ]);

        $allDay = (bool) ($this->eventForm['all_day'] ?? false);
        $startDate = Carbon::parse($this->eventForm['start_date']);
        $endDate = $this->eventForm['end_date'] ? Carbon::parse($this->eventForm['end_date']) : $startDate->copy();

        $start = $allDay
            ? $startDate->copy()->startOfDay()
            : $startDate->copy()->setTimeFromTimeString($this->eventForm['start_time'] ?: '09:00');

        $end = $allDay
            ? $endDate->copy()->endOfDay()
            : $endDate->copy()->setTimeFromTimeString($this->eventForm['end_time'] ?: $this->eventForm['start_time'] ?: '10:00');

        if ($end->lte($start)) {
            $end = $start->copy()->addHour();
        }

        // Conflict detection for timed events (informational, never blocking).
        if (! $allDay && ! $this->ignoreConflicts) {
            $conflicts = EventCalendar::query()
                ->where('id', '!=', $this->eventId)
                ->where(function ($q) use ($start, $end) {
                    $q->where('start_time', '<', $end)
                        ->where('end_time', '>', $start);
                })
                ->orderBy('start_time')
                ->get(['id', 'title', 'start_time', 'end_time', 'all_day']);

            if ($conflicts->isNotEmpty()) {
                $this->conflicts = $conflicts->map(function ($e) {
                    return [
                        'id' => $e->id,
                        'title' => $e->title,
                        'start' => $e->start_time->format('H:i'),
                        'end' => $e->end_time->format('H:i'),
                    ];
                })->all();

                return;
            }
        }

        $data = [
            'school_id' => $user->school_id,
            'title' => trim($this->eventForm['title']),
            'category' => $this->eventForm['category'],
            'description' => $this->eventForm['description'] ?: null,
            'start_time' => $start,
            'end_time' => $end,
            'all_day' => $allDay,
            'location' => $this->eventForm['location'] ?: null,
            'color' => $this->eventForm['color'] ?: '#4f46e5',
            'reminder_minutes' => $this->eventForm['reminder_minutes'] !== '' ? (int) $this->eventForm['reminder_minutes'] : null,
            'recurrence' => $this->eventForm['recurrence'] ?? 'none',
            'target_roles' => $this->eventTargetRoles(),
        ];

        if ($this->editingEvent && $this->eventId) {
            $event = EventCalendar::query()->find($this->eventId);
            if (! $event) {
                return;
            }
            $data['target_roles'] = $event->target_roles;
            $event->update($data);
        } else {
            EventCalendar::create($data + ['created_by_id' => $user->id, 'organizer_id' => $user->id]);
        }

        $this->dispatch('eventCalendarUpdated');
        $this->closeEventModal();
    }

    public function ignoreConflictsAndSave(): void
    {
        $this->ignoreConflicts = true;
        $this->saveEvent();
    }

    public function deleteEvent(int $id): void
    {
        $this->deleteEventId = $id;
    }

    public function cancelDeleteEvent(): void
    {
        $this->deleteEventId = null;
    }

    public function confirmDeleteEvent(): void
    {
        $event = EventCalendar::query()->find($this->deleteEventId);
        if ($event) {
            $event->delete();
        }
        $this->deleteEventId = null;
        $this->dispatch('eventCalendarUpdated');
    }

    // ── Drag & drop scheduling ────────────────────────────────────────────

    public function moveEvent(int $id, string $date, ?string $time = null): void
    {
        if (! $this->validDate($date)) {
            return;
        }

        $event = EventCalendar::query()->find($id);
        if (! $event) {
            return;
        }

        $target = Carbon::parse($date);
        $duration = $event->start_time->diffInMinutes($event->end_time);

        $start = $event->all_day
            ? $target->copy()->startOfDay()
            : $target->copy()->setTimeFromTimeString($time ?: $event->start_time->format('H:i'));

        $end = $event->all_day
            ? $target->copy()->endOfDay()
            : $start->copy()->addMinutes(max($duration, 30));

        $event->update([
            'start_time' => $start,
            'end_time' => $end,
        ]);

        $this->dispatch('eventCalendarUpdated');
    }

    // ── Filters ───────────────────────────────────────────────────────────

    public function toggleShowEvents(): void
    {
        $this->showEvents = ! $this->showEvents;
    }

    public function toggleShowTasks(): void
    {
        $this->showTasks = ! $this->showTasks;
    }

    public function toggleFilter(string $key): void
    {
        match ($key) {
            'important' => $this->importantOnly = ! $this->importantOnly,
            'assigned' => $this->assignedOnly = ! $this->assignedOnly,
            'mine' => $this->mineOnly = ! $this->mineOnly,
            'completed' => $this->showCompleted = ! $this->showCompleted,
            default => null,
        };
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->importantOnly = false;
        $this->assignedOnly = false;
        $this->mineOnly = false;
        $this->showCompleted = false;
        $this->showEvents = true;
        $this->showTasks = true;
    }
}
