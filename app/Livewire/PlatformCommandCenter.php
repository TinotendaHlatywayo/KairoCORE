<?php

namespace App\Livewire;

use App\Models\School;
use App\Models\User;
use App\Models\UserTask;
use App\Notifications\PlatformMessageNotification;
use App\Notifications\PlatformTaskAssignedNotification;
use Illuminate\Support\Collection;
use Livewire\Component;
use Modules\Admin\Models\CustomRole;

/**
 * Platform (super admin) Command Center.
 *
 * Sister of the tenant "Command Center": a live Date & Time trigger sits in the
 * platform topbar and opens a dropdown with two views:
 *
 *   - Tasks         → the platform admin's personal tasks PLUS the cross-tenant
 *                     tasks they have pushed to schools. New tasks can be kept
 *                     private or dispatched to a school's administrator, who
 *                     receives it in their own workspace Task Manager.
 *   - Notifications → the admin's platform notifications, with history.
 */
class PlatformCommandCenter extends Component
{
    public bool $isOpen = false;

    public bool $showHistory = false;

    public int $historyDays = 30;

    /** Active panel tab: "tasks" or "notifications". */
    public string $tab = 'tasks';

    // ── Add-task form state ───────────────────────────────────────────────
    public bool $showAddTask = false;

    public ?string $taskTitle = null;

    public ?string $taskDescription = null;

    public ?string $taskDate = null;

    public ?string $taskTime = null;

    /** null = a personal task for the current admin; otherwise a school id. */
    public ?int $taskSchoolId = null;

    public ?int $taskAssigneeId = null;

    protected $listeners = ['$refresh' => '$refresh', 'notificationSent' => '$refresh'];

    public function toggle(): void
    {
        $this->isOpen = ! $this->isOpen;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->showHistory = false;
        $this->closeAddTask();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['tasks', 'notifications'], true) ? $tab : 'tasks';
        $this->showHistory = false;
    }

    protected function user(): ?User
    {
        return auth()->user();
    }

    // ── Tasks ─────────────────────────────────────────────────────────────

    /**
     * The platform admin's own personal tasks (not tied to any school).
     */
    public function getPersonalTasksProperty(): Collection
    {
        $user = $this->user();

        if (! $user) {
            return collect();
        }

        return UserTask::withoutTenantScope()
            ->whereNull('school_id')
            ->visibleTo($user->id)
            ->whereNull('cleared_at')
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->orderBy('due_date')
            ->orderBy('due_time')
            ->limit(30)
            ->get();
    }

    /**
     * Cross-tenant tasks this platform admin has pushed to schools. Scoped by
     * creator so a platform admin never sees another admin's delegations.
     */
    public function getDispatchedTasksProperty(): Collection
    {
        $user = $this->user();

        if (! $user) {
            return collect();
        }

        return UserTask::withoutTenantScope()
            ->whereNotNull('school_id')
            ->where('created_by_id', $user->id)
            ->whereNull('cleared_at')
            ->with(['assignee:id,name', 'school:id,name,subdomain'])
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->orderBy('due_date')
            ->limit(30)
            ->get();
    }

    public function getOpenTaskCountProperty(): int
    {
        return $this->personalTasks->where('status', UserTask::STATUS_OPEN)->count()
            + $this->dispatchedTasks->where('status', UserTask::STATUS_OPEN)->count();
    }

    /** Active, selectable schools for a cross-tenant delegation. */
    public function getSchoolsProperty(): array
    {
        return School::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Active administrators of the selected target school, used both for the
     * assignee picker and to resolve a default recipient.
     */
    public function getSchoolAdminsProperty(): array
    {
        if (! $this->taskSchoolId) {
            return [];
        }

        $roleId = CustomRole::withoutTenantScope()
            ->where('school_id', $this->taskSchoolId)
            ->where('role_key', 'administrator')
            ->value('id');

        $query = User::withoutTenantScope()
            ->notPlatformManaged()
            ->where('school_id', $this->taskSchoolId)
            ->where('account_status', User::STATUS_ACTIVE);

        if ($roleId) {
            $query->where(function ($q) use ($roleId) {
                $q->where('custom_role_id', $roleId)
                    ->orWhere('requested_role', 'administrator');
            });
        } else {
            $query->where('requested_role', 'administrator');
        }

        return $query->orderBy('name')->pluck('name', 'id')->toArray();
    }

    protected function resolveSchoolAdministrator(int $schoolId): ?User
    {
        $roleId = CustomRole::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('role_key', 'administrator')
            ->value('id');

        if ($roleId) {
            $admin = User::withoutTenantScope()
                ->notPlatformManaged()
                ->where('school_id', $schoolId)
                ->where('account_status', User::STATUS_ACTIVE)
                ->where(function ($q) use ($roleId) {
                    $q->where('custom_role_id', $roleId)
                        ->orWhere('requested_role', 'administrator');
                })
                ->orderBy('name')
                ->first();

            if ($admin) {
                return $admin;
            }
        }

        return User::withoutTenantScope()
            ->notPlatformManaged()
            ->where('school_id', $schoolId)
            ->where('account_status', User::STATUS_ACTIVE)
            ->where('requested_role', 'administrator')
            ->orderBy('name')
            ->first();
    }

    public function openAddTask(): void
    {
        $this->tab = 'tasks';
        $this->showAddTask = true;
    }

    public function closeAddTask(): void
    {
        $this->showAddTask = false;
        $this->taskTitle = null;
        $this->taskDescription = null;
        $this->taskDate = null;
        $this->taskTime = null;
        $this->taskSchoolId = null;
        $this->taskAssigneeId = null;
    }

    public function updatedTaskSchoolId(): void
    {
        $this->taskAssigneeId = array_key_first($this->schoolAdmins) ?: null;
    }

    public function saveTask(): void
    {
        $user = $this->user();

        if (! $user) {
            return;
        }

        $this->validate([
            'taskTitle' => ['required', 'string', 'max:255'],
            'taskDescription' => ['nullable', 'string', 'max:1000'],
            'taskDate' => ['nullable', 'date'],
            'taskTime' => ['nullable', 'date_format:H:i'],
            'taskSchoolId' => ['nullable', 'integer', 'exists:schools,id'],
        ]);

        try {
            $schoolId = null;
            $assigneeId = $user->id;

            if ($this->taskSchoolId) {
                $school = School::find($this->taskSchoolId);

                if (! $school) {
                    $this->addError('taskSchoolId', __('That school could not be found.'));

                    return;
                }

                $admin = $this->taskAssigneeId
                    ? User::withoutTenantScope()
                        ->where('school_id', $school->id)
                        ->where('account_status', User::STATUS_ACTIVE)
                        ->find($this->taskAssigneeId)
                    : null;

                $admin ??= $this->resolveSchoolAdministrator($school->id);

                if (! $admin) {
                    $this->addError('taskSchoolId', __('This school has no active administrator to receive the task.'));

                    return;
                }

                $schoolId = $school->id;
                $assigneeId = $admin->id;
            }

            $task = UserTask::withoutTenantScope()->create([
                'school_id' => $schoolId,
                'created_by_id' => $user->id,
                'assigned_to_id' => $assigneeId,
                'title' => trim((string) $this->taskTitle),
                'description' => $this->taskDescription ?: null,
                'due_date' => $this->taskDate ?: null,
                'due_time' => $this->taskTime ?: null,
                'status' => UserTask::STATUS_OPEN,
            ]);

            // In single-tenant mode the BelongsToTenant hook stamps school_id
            // onto every insert, which would file a personal platform task
            // under the tenant school. A personal task belongs to no school.
            if ($schoolId === null && $task->school_id !== null) {
                $task->forceFill(['school_id' => null])->saveQuietly();
            }

            if ($schoolId && $assigneeId !== $user->id) {
                $recipient = User::withoutTenantScope()->find($assigneeId);
                $recipient?->notify(new PlatformTaskAssignedNotification($task));
            }

            $this->closeAddTask();

            $this->dispatch('notificationSent');
        } catch (\Throwable $e) {
            // Never let an unexpected failure surface as a bare 500 on the
            // platform topbar: log it with a trace for diagnosis and keep the
            // form open with a message the admin can act on.
            report($e);

            $this->addError('taskTitle', __('The task could not be saved. Please try again.'));
        }
    }

    protected function findPlatformTask(int $taskId): ?UserTask
    {
        $user = $this->user();

        if (! $user) {
            return null;
        }

        return UserTask::withoutTenantScope()
            ->where(function ($q) use ($user) {
                $q->where(function ($personal) use ($user) {
                    $personal->whereNull('school_id')
                        ->where(function ($owner) use ($user) {
                            $owner->where('assigned_to_id', $user->id)
                                ->orWhere('created_by_id', $user->id);
                        });
                })->orWhere(function ($delegated) use ($user) {
                    $delegated->whereNotNull('school_id')
                        ->where('created_by_id', $user->id);
                });
            })
            ->find($taskId);
    }

    public function toggleTaskDone(int $taskId): void
    {
        $task = $this->findPlatformTask($taskId);

        if (! $task) {
            return;
        }

        $task->update([
            'status' => $task->isDone() ? UserTask::STATUS_OPEN : UserTask::STATUS_DONE,
            'completed_at' => $task->isDone() ? null : now(),
        ]);
    }

    public function deleteTask(int $taskId): void
    {
        $task = $this->findPlatformTask($taskId);

        if (! $task) {
            return;
        }

        // A delegated task lives in a school's Task Manager, so dismissing it
        // here only hides it from the platform list; only personal tasks are
        // actually deleted.
        if ($task->school_id) {
            $task->update(['cleared_at' => now()]);

            return;
        }

        $task->delete();
    }

    public function clearTasks(): void
    {
        $user = $this->user();

        if (! $user) {
            return;
        }

        UserTask::withoutTenantScope()
            ->whereNull('school_id')
            ->visibleTo($user->id)
            ->whereNull('cleared_at')
            ->update(['cleared_at' => now(), 'status' => UserTask::STATUS_DONE]);

        // Delegated tasks are the school's; only drop them from this list.
        UserTask::withoutTenantScope()
            ->whereNotNull('school_id')
            ->where('created_by_id', $user->id)
            ->whereNull('cleared_at')
            ->update(['cleared_at' => now()]);
    }

    // ── Notifications ─────────────────────────────────────────────────────
    public function getNotificationsProperty(): Collection
    {
        $user = $this->user();

        if (! $user) {
            return collect();
        }

        return $user->notifications()
            ->whereNull('cleared_at')
            ->latest()
            ->limit(8)
            ->get();
    }

    public function getUnreadCountProperty(): int
    {
        $user = $this->user();

        if (! $user) {
            return 0;
        }

        return $user->unreadNotifications()
            ->whereNull('cleared_at')
            ->count();
    }

    /**
     * Older notifications (past N days) shown when History is toggled on.
     */
    public function getHistoryProperty(): Collection
    {
        $user = $this->user();

        if (! $user) {
            return collect();
        }

        return $user->notifications()
            ->where('created_at', '>=', now()->subDays($this->historyDays)->startOfDay())
            ->latest()
            ->limit(50)
            ->get();
    }

    public function markAllRead(): void
    {
        $user = $this->user();

        if (! $user) {
            return;
        }

        $user->unreadNotifications()
            ->whereNull('cleared_at')
            ->update(['read_at' => now()]);
    }

    public function markRead(string $id): void
    {
        $user = $this->user();

        if (! $user) {
            return;
        }

        $user->notifications()
            ->whereKey($id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function clearNotifications(): void
    {
        $user = $this->user();

        if (! $user) {
            return;
        }

        // Mark as cleared rather than deleting, so no notification is ever
        // permanently lost (mirrors the tenant Command Center behaviour).
        $user->notifications()
            ->whereNull('cleared_at')
            ->update(['cleared_at' => now(), 'read_at' => now()]);

        $this->showHistory = false;
    }

    public function toggleHistory(): void
    {
        $this->showHistory = ! $this->showHistory;
    }

    public function notificationUrl($notification): ?string
    {
        return data_get($notification->data, 'url') ?: null;
    }

    public function titleFor($notification): string
    {
        $data = $notification->data;

        if (($data['format'] ?? null) === 'task_assigned') {
            return (string) ($data['task_title'] ?? __('Task assigned'));
        }

        return (string) (
            $data['subject']
            ?? $data['title']
            ?? __('Notification')
        );
    }

    public function previewFor($notification): string
    {
        $data = $notification->data;

        if (($data['format'] ?? null) === 'task_assigned') {
            $parts = array_filter([
                $data['assigner_name'] ? __('From :name', ['name' => $data['assigner_name']]) : null,
                $data['due_date'] ? __('Due :date', ['date' => $data['due_date']]) : null,
            ]);

            return implode(' · ', $parts);
        }

        return (string) (
            $data['preview']
            ?? $data['body']
            ?? $data['message']
            ?? ''
        );
    }

    public function iconFor(string $type): string
    {
        return match ($type) {
            PlatformMessageNotification::class => '💬',
            default => '🔔',
        };
    }

    public function render()
    {
        return view('livewire.platform-command-center');
    }
}
