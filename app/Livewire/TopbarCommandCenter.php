<?php

namespace App\Livewire;

use App\Filament\App\Pages\MyDay;
use App\Filament\App\Pages\Schedule;
use App\Models\User;
use App\Models\UserTask;
use App\Notifications\EventReminderNotification;
use App\Notifications\NewApplicationNotification;
use App\Notifications\ProfilePhotoRejectedNotification;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskOverdueNotification;
use App\Notifications\TaskReminderNotification;
use App\Notifications\UserRegistrationApprovalNotification;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Communication\Models\EventCalendar;

/**
 * Unified, centered topbar Command Center.
 *
 * A single live Date & Time trigger sits at the exact horizontal centre of the
 * header. Clicking it opens a split-pane dropdown:
 *
 *   - LEFT  → Task Manager (personal/assigned tasks, notifications,
 *             Do-Not-Disturb toggle, Clear All, Clear Notifications).
 *   - RIGHT → Interactive month calendar with event/task markers, quick range
 *             presets and hover-preview range selection.
 *
 * Clicking any calendar day slides open a nested "Add a Task" panel. Users with
 * the "tasks.assign" permission may assign tasks (with an optional deadline) to
 * any school member; everyone else can only create/view their own tasks.
 */
class TopbarCommandCenter extends Component
{
    public const PRESETS = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'last7' => 'Last 7 days',
        'last30' => 'Last 30 days',
        'last_quarter' => 'Last quarter',
        'custom' => 'Custom range',
    ];

    public static function PRESETS(): array
    {
        return [
            'today' => __('Today'),
            'yesterday' => __('Yesterday'),
            'last7' => __('Last 7 days'),
            'last30' => __('Last 30 days'),
            'last_quarter' => __('Last quarter'),
            'custom' => __('Custom range'),
        ];
    }

    public bool $isOpen = false;

    public bool $showNotificationHistory = false;

    // Notification-history filters: category (all/chat/registration/system)
    // and age window in days.
    public string $historyCategory = 'all';

    public int $historyDays = 30;

    public string $month = '';

    public ?string $selectedDate = null;

    public ?string $taskTitle = null;

    public ?string $taskDescription = null;

    public ?int $taskAssigneeId = null;

    public ?string $taskDueTime = null;

    public ?string $addTab = 'task';

    public ?string $eventTitle = null;

    public ?string $eventStart = null;

    public ?string $eventEnd = null;

    public string $eventColor = '#6366f1';

    public ?string $preset = null;

    public ?string $rangeStart = null;

    public ?string $rangeEnd = null;

    public bool $dnd = false;

    protected $listeners = [
        'admissionNotificationsSent' => 'refresh',
        'registrationNotificationsSent' => 'refresh',
        'eventCalendarUpdated' => 'refresh',
        'ticketStatusUpdated' => 'refresh',
        'taskCreated' => 'refresh',
    ];

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        $this->dnd = (bool) auth()->user()?->do_not_disturb;
    }

    public function refresh(): void {}

    public function toggle(): void
    {
        $this->isOpen ? $this->close() : $this->open();
    }

    public function open(): void
    {
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->selectedDate = null;
    }

    protected function user(): ?User
    {
        return auth()->user();
    }

    /**
     * Panel-aware link to the full schedule page, used by the Command Center
     * footer. Students override this to point at their own schedule.
     */
    public function scheduleUrl(): string
    {
        return Schedule::getUrl();
    }

    /**
     * Panel-aware link to the "My Day" companion page. Students have no such
     * page and link back to their schedule instead.
     */
    public function myDayUrl(): string
    {
        return MyDay::getUrl();
    }

    public function getCanAssignProperty(): bool
    {
        return PermissionRegistry::userCan($this->user(), 'tasks.assign');
    }

    public function getCanClearProperty(): bool
    {
        return PermissionRegistry::userCan($this->user(), 'tasks.clear');
    }

    public function getAssigneeOptionsProperty(): array
    {
        $user = $this->user();
        if (! $user || ! $this->canAssign) {
            return [];
        }

        return User::query()
            ->where('school_id', $user->school_id)
            ->where('account_status', User::STATUS_ACTIVE)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Resolve the active date range from the chosen preset (or custom range).
     *
     * @return array{start: string|null, end: string|null}|null
     */
    protected function resolvedRange(): ?array
    {
        $today = now()->startOfDay();

        return match ($this->preset) {
            'today' => ['start' => $today->toDateString(), 'end' => $today->copy()->endOfDay()->toDateString()],
            'yesterday' => ['start' => $today->copy()->subDay()->toDateString(), 'end' => $today->copy()->subDay()->toDateString()],
            'last7' => ['start' => $today->copy()->subDays(6)->toDateString(), 'end' => $today->toDateString()],
            'last30' => ['start' => $today->copy()->subDays(29)->toDateString(), 'end' => $today->toDateString()],
            'last_quarter' => ['start' => $today->copy()->startOfQuarter()->toDateString(), 'end' => $today->copy()->endOfQuarter()->toDateString()],
            'custom' => ($this->rangeStart && $this->rangeEnd)
                ? ['start' => $this->rangeStart, 'end' => $this->rangeEnd]
                : null,
            default => null,
        };
    }

    public function getActiveRangeProperty(): ?array
    {
        return $this->resolvedRange();
    }

    public function choosePreset(string $preset): void
    {
        $this->preset = in_array($preset, array_keys(self::PRESETS()), true) ? $preset : null;

        if ($preset === 'custom') {
            $this->rangeStart = null;
            $this->rangeEnd = null;
        }
    }

    /**
     * Two-click range selection (Custom preset): first click sets the start,
     * second click locks the end.
     */
    public function selectRangeDate(string $date): void
    {
        if (! $this->rangeStart) {
            $this->rangeStart = $date;
            $this->rangeEnd = null;
            $this->preset = 'custom';

            return;
        }

        if ($date < $this->rangeStart) {
            [$this->rangeStart, $date] = [$date, $this->rangeStart];
        }
        $this->rangeEnd = $date;
        $this->preset = 'custom';
    }

    public function clearRange(): void
    {
        $this->preset = null;
        $this->rangeStart = null;
        $this->rangeEnd = null;
    }

    // ── Calendar navigation ───────────────────────────────────────────────

    public function goToCurrentMonth(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function previousMonth(): void
    {
        $this->month = Carbon::parse($this->month.'-01')->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::parse($this->month.'-01')->addMonth()->format('Y-m');
    }

    // ── Calendar data ─────────────────────────────────────────────────────

    protected function monthEvents(): Collection
    {
        $start = Carbon::parse($this->month.'-01')->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        return EventCalendar::query()
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_time', [$start, $end]);
                $query->orWhere(function ($query) use ($start) {
                    $query->where('start_time', '<', $start)
                        ->where('end_time', '>=', $start);
                });
            })
            ->orderBy('start_time')
            ->get();
    }

    protected function monthTasks(): Collection
    {
        $user = $this->user();
        if (! $user) {
            return collect();
        }

        $start = Carbon::parse($this->month.'-01')->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();

        return UserTask::query()
            ->visibleTo($user->id)
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->get();
    }

    public function getCalendarDaysProperty(): array
    {
        $current = Carbon::parse($this->month.'-01');
        $today = now();

        $events = $this->monthEvents()->groupBy(fn ($e) => $e->start_time->format('Y-m-d'));
        $tasks = $this->monthTasks()->groupBy(fn ($t) => $t->due_date?->format('Y-m-d'));
        $range = $this->resolvedRange();

        $days = [];
        $leadingBlanks = $current->copy()->startOfMonth()->dayOfWeek;

        for ($i = 0; $i < $leadingBlanks; $i++) {
            $days[] = null;
        }

        for ($day = 1; $day <= $current->daysInMonth; $day++) {
            $date = $current->copy()->day($day)->format('Y-m-d');
            $dayEvents = $events[$date] ?? collect();
            $dayTasks = $tasks[$date] ?? collect();

            $days[] = [
                'day' => $day,
                'date' => $date,
                'isToday' => $date === $today->format('Y-m-d'),
                'hasEvents' => $dayEvents->isNotEmpty(),
                'eventColor' => $dayEvents->first()?->color ?? null,
                'hasTasks' => $dayTasks->isNotEmpty(),
                'taskCount' => $dayTasks->count(),
                'inRange' => $range ? ($date >= $range['start'] && $date <= $range['end']) : false,
                'isRangeStart' => $range ? $date === $range['start'] : false,
                'isRangeEnd' => $range ? $date === $range['end'] : false,
                'isPast' => $date < $today->format('Y-m-d'),
            ];
        }

        return [
            'label' => $current->format('F Y'),
            'weekdays' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            'days' => $days,
        ];
    }

    // ── Tasks ─────────────────────────────────────────────────────────────

    public function getTasksProperty(): Collection
    {
        $user = $this->user();
        if (! $user) {
            return collect();
        }

        $range = $this->resolvedRange();

        return UserTask::query()
            ->visibleTo($user->id)
            ->dueBetween($range['start'] ?? null, $range['end'] ?? null)
            ->whereNull('cleared_at')
            ->with([
                'assignee:id,name,requested_role,custom_role_id',
                'assignee.employee:user_id,role',
                'assignee.customRole:id,name',
            ])
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->orderBy('due_date')
            ->orderBy('due_time')
            ->limit(40)
            ->get();
    }

    public function getOpenTaskCountProperty(): int
    {
        $user = $this->user();
        if (! $user) {
            return 0;
        }

        return UserTask::query()->visibleTo($user->id)->whereNull('cleared_at')->open()->count();
    }

    public function openAddTask(string $date): void
    {
        $this->selectedDate = $date;
        $this->addTab = 'task';
        $this->taskTitle = null;
        $this->taskDescription = null;
        $this->taskAssigneeId = null;
        $this->taskDueTime = null;
    }

    public function openAddEvent(string $date): void
    {
        $this->selectedDate = $date;
        $this->addTab = 'event';
        $this->eventTitle = null;
        $this->eventStart = '09:00';
        $this->eventEnd = '10:00';
    }

    public function closeAddTask(): void
    {
        $this->selectedDate = null;
    }

    public function saveEvent(): void
    {
        $user = $this->user();
        if (! $user || ! $this->selectedDate) {
            return;
        }

        $this->validate([
            'eventTitle' => ['required', 'string', 'max:255'],
            'eventStart' => ['nullable', 'date_format:H:i'],
            'eventEnd' => ['nullable', 'date_format:H:i'],
        ]);

        EventCalendar::create([
            'school_id' => $user->school_id,
            'created_by_id' => $user->id,
            'organizer_id' => $user->id,
            'title' => trim($this->eventTitle),
            'category' => 'general',
            'start_time' => $this->selectedDate.' '.($this->eventStart ?? '09:00').':00',
            'end_time' => $this->selectedDate.' '.($this->eventEnd ?? '10:00').':00',
            'color' => $this->eventColor,
        ]);

        $this->eventTitle = null;
        $this->eventStart = null;
        $this->eventEnd = null;
        $this->selectedDate = null;

        $this->dispatch('eventCalendarUpdated');
    }

    public function saveTask(): void
    {
        $user = $this->user();
        if (! $user || ! $this->selectedDate) {
            return;
        }

        $this->validate([
            'taskTitle' => ['required', 'string', 'max:255'],
            'taskDescription' => ['nullable', 'string', 'max:1000'],
            'taskDueTime' => ['nullable', 'date_format:H:i'],
        ]);

        $assigneeId = $user->id;
        if ($this->canAssign && $this->taskAssigneeId) {
            $assignee = User::query()
                ->where('school_id', $user->school_id)
                ->where('account_status', User::STATUS_ACTIVE)
                ->find($this->taskAssigneeId);

            if ($assignee) {
                $assigneeId = $assignee->id;
            }
        }

        UserTask::create([
            'school_id' => $user->school_id,
            'created_by_id' => $user->id,
            'assigned_to_id' => $assigneeId,
            'title' => trim($this->taskTitle),
            'description' => $this->taskDescription ?: null,
            'due_date' => $this->selectedDate,
            'due_time' => $this->taskDueTime ?: null,
            'status' => UserTask::STATUS_OPEN,
        ]);

        $this->taskTitle = null;
        $this->taskDescription = null;
        $this->taskAssigneeId = null;
        $this->taskDueTime = null;
        $this->selectedDate = null;

        $this->dispatch('taskCreated');
    }

    public function toggleTaskDone(int $taskId): void
    {
        $task = $this->findOwnedTask($taskId);
        if (! $task) {
            return;
        }

        $task->update([
            'status' => $task->isDone() ? UserTask::STATUS_OPEN : UserTask::STATUS_DONE,
        ]);
    }

    public function deleteTask(int $taskId): void
    {
        $task = $this->findOwnedTask($taskId);
        if (! $task) {
            return;
        }

        $task->delete();
    }

    public function clearAllTasks(): void
    {
        if (! $this->canClear) {
            return;
        }

        $user = $this->user();
        if (! $user) {
            return;
        }

        // "Clear All" dismisses every notification and task from the Command
        // Center interface only. Tasks are marked cleared (and open ones done)
        // but stay on the calendar — they can only be removed by deleting them.
        $user->notifications()
            ->whereNull('cleared_at')
            ->update(['cleared_at' => now(), 'read_at' => now()]);

        UserTask::query()
            ->visibleTo($user->id)
            ->whereNull('cleared_at')
            ->update([
                'cleared_at' => now(),
                'status' => UserTask::STATUS_DONE,
            ]);
    }

    protected function findOwnedTask(int $taskId): ?UserTask
    {
        $user = $this->user();
        if (! $user) {
            return null;
        }

        return UserTask::query()
            ->visibleTo($user->id)
            ->find($taskId);
    }

    // ── Day agenda (per selected date) ────────────────────────────────────

    public function getDayAgendaProperty(): array
    {
        $user = $this->user();
        $date = $this->selectedDate;

        if (! $user || ! $date) {
            return ['date' => null, 'label' => '', 'events' => [], 'tasks' => []];
        }

        $day = Carbon::parse($date)->startOfDay();

        $events = EventCalendar::query()
            ->overlappingRange($day, $day->copy()->endOfDay())
            ->orderBy('start_time')
            ->get()
            ->map(function (EventCalendar $event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'color' => $event->color,
                    'location' => $event->location,
                    'start_time' => $event->start_time->format('H:i'),
                    'end_time' => $event->end_time->format('H:i'),
                    'all_day' => $event->all_day,
                ];
            });

        $tasks = UserTask::query()
            ->visibleTo($user->id)
            ->where('due_date', $date)
            ->orderBy('due_time')
            ->get()
            ->map(function (UserTask $task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'due_time' => $task->due_time,
                    'done' => $task->isDone(),
                    'important' => $task->important,
                    'priority' => $task->priority,
                ];
            });

        return [
            'date' => $date,
            'label' => $day->format('D, M j'),
            'events' => $events,
            'tasks' => $tasks,
        ];
    }

    // ── Do Not Disturb ────────────────────────────────────────────────────

    public function setDnd(bool $enabled): void
    {
        $user = $this->user();
        if (! $user) {
            return;
        }

        $user->update(['do_not_disturb' => $enabled]);
        $this->dnd = $enabled;
    }

    // ── Notifications ─────────────────────────────────────────────────────

    protected function trackedNotificationTypes(): array
    {
        return [
            NewApplicationNotification::class,
            UserRegistrationApprovalNotification::class,
            TaskAssignedNotification::class,
            TaskReminderNotification::class,
            TaskOverdueNotification::class,
            EventReminderNotification::class,
            ProfilePhotoRejectedNotification::class,
            \App\Notifications\PlatformMessageNotification::class,
        ];
    }

    /**
     * Coarse category for a notification class, used by the history filters:
     *  - chat         → platform ↔ tenant conversations
     *  - registration → admission applications / account registrations
     *  - system       → tasks, events, reminders, photo moderation, etc.
     */
    public static function notificationCategory(string $type): string
    {
        return match ($type) {
            \App\Notifications\PlatformMessageNotification::class => 'chat',
            NewApplicationNotification::class,
            UserRegistrationApprovalNotification::class => 'registration',
            default => 'system',
        };
    }

    public function getUnreadNotificationsProperty(): Collection
    {
        $user = $this->user();
        if (! $user) {
            return collect();
        }

        return $user->notifications()
            ->whereIn('type', $this->trackedNotificationTypes())
            ->whereNull('read_at')
            ->whereNull('cleared_at')
            ->latest()
            ->limit(6)
            ->get();
    }

    public function getUnreadNotificationCountProperty(): int
    {
        $user = $this->user();
        if (! $user) {
            return 0;
        }

        return $user->unreadNotifications()
            ->whereIn('type', $this->trackedNotificationTypes())
            ->whereNull('cleared_at')
            ->count();
    }

    public function markNotificationsRead(): void
    {
        $user = $this->user();
        if (! $user) {
            return;
        }

        $user->unreadNotifications()
            ->whereIn('type', $this->trackedNotificationTypes())
            ->whereNull('cleared_at')
            ->update(['read_at' => now()]);
    }

    public function clearNotifications(): void
    {
        $user = $this->user();
        if (! $user) {
            return;
        }

        // "Clear notifications" dismisses every notification from the
        // interface (badge + list) without deleting the rows, so the
        // 30-day History keeps a record. Notifications are merely marked
        // as cleared/read rather than struck through in place.
        $user->notifications()
            ->whereNull('cleared_at')
            ->update(['cleared_at' => now(), 'read_at' => now()]);
    }

    /**
     * Notification history for the past 30 days (any type), newest first.
     * Used by the "History" toggle in the Command Center. Shows every
     * notification row so nothing is permanently lost after "Clear
     * notifications". Filterable by category (chat / registration / system)
     * and by age window.
     */
    public function getNotificationHistoryProperty(): Collection
    {
        $user = $this->user();
        if (! $user) {
            return collect();
        }

        $days = in_array((int) $this->historyDays, [7, 14, 30], true) ? (int) $this->historyDays : 30;

        $query = $user->notifications()
            ->where('created_at', '>=', now()->subDays($days)->startOfDay());

        if (in_array($this->historyCategory, ['chat', 'registration', 'system'], true)) {
            $types = collect($this->trackedNotificationTypes())
                ->filter(fn (string $type) => static::notificationCategory($type) === $this->historyCategory)
                ->all();

            // Unknown/legacy types are treated as system notifications.
            if ($this->historyCategory === 'system') {
                $query->where(function ($q) use ($types) {
                    $q->whereIn('type', $types)
                        ->orWhereNotIn('type', collect($this->trackedNotificationTypes())->all());
                });
            } else {
                $query->whereIn('type', $types);
            }
        }

        return $query->latest()->limit(50)->get();
    }

    public function toggleNotificationHistory(): void
    {
        $this->showNotificationHistory = ! $this->showNotificationHistory;
    }

    /**
     * Resolve the click-through URL at RENDER time so even historical
     * notifications stored before deep links existed still navigate to the
     * right inbox. Audiences without inbox access (e.g. students) get no
     * link rather than a route error, and panels are pinned explicitly.
     */
    public function notificationUrl($notification): ?string
    {
        if ($url = data_get($notification->data, 'url')) {
            return $url;
        }

        $isChat = ($notification->data['category'] ?? null) === 'chat'
            || $notification->type === \App\Notifications\PlatformMessageNotification::class;

        if (! $isChat) {
            return null;
        }

        $user = $this->user();

        try {
            if ($user && $user->school_id === null) {
                return \App\Filament\Admin\Resources\PlatformMessageResource::getUrl(panel: 'admin')
                    .'?tableAction=view_thread&tableActionRecord='.data_get($notification->data, 'message_id');
            }

            if (
                $user
                && filled($user->school_id)
                && \Modules\Admin\Services\PermissionRegistry::checkPermission('communication.contact_platform')
            ) {
                // Tenants go to THEIR workspace inbox on THEIR OWN subdomain —
                // never the platform panel, never the central host (where their
                // session cookie does not exist).
                return tenant_workspace_url(
                    $user->school,
                    'workspace/platform-inboxes?tableAction=view_thread&tableActionRecord='.data_get($notification->data, 'message_id'),
                );
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    // ── Task assignee colour coding ────────────────────────────────────────

    /**
     * Category used to colour-code a task by its assignee: self, student,
     * teaching staff, non-teaching staff or administrator. Users created
     * through registration carry a requested_role; legacy staff accounts fall
     * back to their employee record's role/designation.
     */
    protected function assigneeCategory(UserTask $task): string
    {
        $user = $this->user();
        $assignee = $task->assignee;

        if (! $assignee || $task->assigned_to_id === $user?->id) {
            return 'self';
        }

        $role = $assignee->requested_role;
        if (in_array($role, ['administrator', 'student', 'teaching_staff', 'non_teaching_staff'], true)) {
            return $role;
        }

        if ($assignee->customRole) {
            $name = mb_strtolower($assignee->customRole->name);
            if (str_contains($name, 'admin')) {
                return 'administrator';
            }
            if (str_contains($name, 'teacher')) {
                return 'teaching_staff';
            }
            if (str_contains($name, 'student')) {
                return 'student';
            }
        }

        if ($assignee->employee) {
            $name = mb_strtolower($assignee->employee->role ?? '');
            if (str_contains($name, 'admin')) {
                return 'administrator';
            }
            if (str_contains($name, 'teacher')) {
                return 'teaching_staff';
            }
        }

        return 'non_teaching_staff';
    }

    /**
     * Human-readable label for the assignee category chip.
     */
    protected function assigneeCategoryLabel(string $category): string
    {
        return match ($category) {
            'self' => __('Me'),
            'student' => __('Student'),
            'teaching_staff' => __('Teaching'),
            'administrator' => __('Admin'),
            default => __('Non-Teaching'),
        };
    }

    /**
     * Combined badge shown on the trigger (hidden while Do Not Disturb is on).
     */
    public function getBadgeCountProperty(): int
    {
        if ($this->dnd) {
            return 0;
        }

        return $this->openTaskCount + $this->unreadNotificationCount;
    }

    public function render()
    {
        return view('livewire.topbar-command-center', [
            'currentMonth' => Carbon::parse($this->month.'-01'),
            'tasks' => $this->tasks,
        ]);
    }
}
