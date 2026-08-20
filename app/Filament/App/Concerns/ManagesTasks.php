<?php

namespace App\Filament\App\Concerns;

use App\Models\User;
use App\Models\UserTask;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Support\Carbon;
use Modules\Academics\Models\Section;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Students\Models\Student;

/**
 * Shared personal-task behaviour used by the Schedule and My Day pages.
 *
 * Privacy invariant: every fetch goes through UserTask::visibleTo($userId),
 * so a task can never be resolved by blind ID manipulation.
 */
trait ManagesTasks
{
    public bool $taskModalOpen = false;

    public bool $editingTask = false;

    public ?int $taskId = null;

    public array $taskForm = [];

    public ?int $deleteTaskId = null;

    protected function user(): ?User
    {
        return auth()->user();
    }

    public function getCanAssignProperty(): bool
    {
        return PermissionRegistry::userCan($this->user(), 'tasks.assign');
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
     * Custom roles for the assignee picker (role → all members OR search).
     *
     * @return array<int, array{id: int, name: string, member_count: int}>
     */
    public function getAssigneeRolesProperty(): array
    {
        $user = $this->user();
        if (! $user || ! $this->canAssign) {
            return [];
        }

        $roles = CustomRole::query()
            ->where('school_id', $user->school_id)
            ->orderBy('name')
            ->get();

        // Single grouped count instead of one COUNT query per role.
        $memberCounts = User::query()
            ->where('school_id', $user->school_id)
            ->whereNotNull('custom_role_id')
            ->where('account_status', User::STATUS_ACTIVE)
            ->selectRaw('custom_role_id, COUNT(*) as total')
            ->groupBy('custom_role_id')
            ->pluck('total', 'custom_role_id');

        return $roles
            ->map(fn (CustomRole $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'member_count' => (int) ($memberCounts[$role->id] ?? 0),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Members per custom role, for the "search within role" flow.
     *
     * @return array<int, array{id: int, name: string, email: string}>
     */
    public function getAssigneeRoleMembersProperty(): array
    {
        $user = $this->user();
        if (! $user || ! $this->canAssign) {
            return [];
        }

        return User::query()
            ->where('school_id', $user->school_id)
            ->whereNotNull('custom_role_id')
            ->where('account_status', User::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'custom_role_id', 'name', 'email'])
            ->groupBy('custom_role_id')
            ->map(fn ($members) => $members
                ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
                ->values()
                ->all())
            ->toArray();
    }

    /**
     * Searchable staff list (all active non-student users) for the picker.
     *
     * @return array<int, array{id: int, name: string, email: string}>
     */
    public function getAssigneeStaffProperty(): array
    {
        $user = $this->user();
        if (! $user || ! $this->canAssign) {
            return [];
        }

        return User::query()
            ->where('school_id', $user->school_id)
            ->where('account_status', User::STATUS_ACTIVE)
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->filter(fn (User $u) => ! $u->isStudent())
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])
            ->values()
            ->all();
    }

    /**
     * Searchable student list with school-ID, email, level and class.
     *
     * @return array<int, array{id: int, user_id: int, name: string, email: string, school_id: string, level: ?string, section: ?string}>
     */
    public function getAssigneeStudentsProperty(): array
    {
        $user = $this->user();
        if (! $user || ! $this->canAssign) {
            return [];
        }

        return Student::query()
            ->where('school_id', $user->school_id)
            ->whereNotNull('user_id')
            ->with(['currentEnrollment.course', 'currentEnrollment.section'])
            ->orderBy('first_name')
            ->get()
            ->map(function (Student $student) {
                return [
                    'id' => $student->id,
                    'user_id' => (int) $student->user_id,
                    'name' => $student->full_name,
                    'email' => $student->parent_email ?: '',
                    'school_id' => $student->student_id_number ?: '',
                    'level' => $student->currentEnrollment?->course?->name,
                    'section' => $student->currentEnrollment?->section?->name,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Distinct levels (course names) for "whole level" student assignment.
     *
     * @return array<int, string>
     */
    public function getAssigneeLevelsProperty(): array
    {
        $user = $this->user();
        if (! $user || ! $this->canAssign) {
            return [];
        }

        return Student::query()
            ->where('school_id', $user->school_id)
            ->whereNotNull('user_id')
            ->with('currentEnrollment.course')
            ->get()
            ->map(fn (Student $s) => $s->currentEnrollment?->course?->name)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Sections (classes) for "specific class" student assignment.
     *
     * @return array<int, array{id: int, name: string, level: ?string}>
     */
    public function getAssigneeSectionsProperty(): array
    {
        $user = $this->user();
        if (! $user || ! $this->canAssign) {
            return [];
        }

        return Section::query()
            ->where('school_id', $user->school_id)
            ->with('course')
            ->orderBy('name')
            ->get(['id', 'school_id', 'course_id', 'name'])
            ->map(fn (Section $section) => [
                'id' => $section->id,
                'name' => $section->name,
                'level' => $section->course?->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Resolve the picker spec into a list of target user IDs (fan-out).
     *
     * Spec shapes:
     *   {"mode":"self"}
     *   {"mode":"role","role_id":2,"all":true}
     *   {"mode":"role","role_id":2,"all":false,"member_ids":[1,3]}
     *   {"mode":"staff","staff_ids":[1,3]}
     *   {"mode":"students","scope":"school"}
     *   {"mode":"students","scope":"level","level":"Form 2"}
     *   {"mode":"students","scope":"class","section_id":5}
     *   {"mode":"students","scope":"individuals","student_ids":[10,11]}
     *
     * @return array<int, int> unique user IDs
     */
    protected function resolveAssigneeSpec(array $spec): array
    {
        $user = $this->user();
        if (! $user || ! $this->canAssign) {
            return [$user?->id];
        }

        $schoolId = $user->school_id;
        $mode = $spec['mode'] ?? 'self';

        if ($mode === 'self') {
            return [$user->id];
        }

        if ($mode === 'role') {
            $roleId = (int) ($spec['role_id'] ?? 0);
            $query = User::query()
                ->where('school_id', $schoolId)
                ->where('custom_role_id', $roleId)
                ->where('account_status', User::STATUS_ACTIVE);

            if (empty($spec['all'])) {
                $memberIds = array_map('intval', $spec['member_ids'] ?? []);
                $query->whereIn('id', $memberIds);
            }

            return $query->pluck('id')->all();
        }

        if ($mode === 'staff') {
            $staffIds = array_map('intval', $spec['staff_ids'] ?? []);

            return User::query()
                ->where('school_id', $schoolId)
                ->where('account_status', User::STATUS_ACTIVE)
                ->whereIn('id', $staffIds)
                ->pluck('id')
                ->all();
        }

        if ($mode === 'students') {
            $scope = $spec['scope'] ?? 'school';
            $query = Student::query()
                ->where('school_id', $schoolId)
                ->whereNotNull('user_id');

            if ($scope === 'level') {
                $query->whereHas('currentEnrollment.course', fn ($q) => $q->where('name', $spec['level'] ?? ''));
            } elseif ($scope === 'class') {
                $query->whereHas('currentEnrollment', fn ($q) => $q->where('section_id', (int) ($spec['section_id'] ?? 0)));
            } elseif ($scope === 'individuals') {
                $studentIds = array_map('intval', $spec['student_ids'] ?? []);
                $query->whereIn('id', $studentIds);
            }

            return $query->pluck('user_id')->filter()->all();
        }

        return [$user->id];
    }

    public function openTaskModal(?string $date = null): void
    {
        $target = $date ? Carbon::parse($date) : Carbon::now();
        $this->taskId = null;
        $this->editingTask = false;
        $this->taskForm = [
            'title' => '',
            'description' => '',
            'due_date' => $target->format('Y-m-d'),
            'due_time' => null,
            'priority' => 'medium',
            'important' => false,
            'assignee_spec' => json_encode(['mode' => 'self']),
            'reminder_at' => null,
            'recurrence' => 'none',
        ];
        $this->taskModalOpen = true;
    }

    public function editTask(int $id): void
    {
        $task = $this->findOwnedTask($id);
        if (! $task) {
            return;
        }

        $this->taskId = $task->id;
        $this->editingTask = true;
        $this->taskForm = [
            'title' => $task->title,
            'description' => $task->description,
            'due_date' => $task->due_date?->format('Y-m-d'),
            'due_time' => $task->due_time,
            'priority' => $task->priority,
            'important' => (bool) $task->important,
            'assignee_spec' => $this->assigneeSpecForEdit($task),
            'reminder_at' => $task->reminder_at?->format('Y-m-d\TH:i'),
            'recurrence' => $task->recurrence,
        ];
        $this->taskModalOpen = true;
    }

    public function closeTaskModal(): void
    {
        $this->taskModalOpen = false;
        $this->taskId = null;
        $this->editingTask = false;
    }

    public function saveTask(): void
    {
        $user = $this->user();
        if (! $user) {
            return;
        }

        $this->validate([
            'taskForm.title' => ['required', 'string', 'max:255'],
            'taskForm.due_date' => ['nullable', 'date'],
            'taskForm.due_time' => ['nullable', 'date_format:H:i'],
            'taskForm.reminder_at' => ['nullable', 'date'],
            'taskForm.priority' => ['required', 'in:low,medium,high'],
        ]);

        $spec = json_decode($this->taskForm['assignee_spec'] ?? '', true) ?: ['mode' => 'self'];
        $assigneeIds = array_values(array_unique(array_filter($this->resolveAssigneeSpec($spec))));
        if (empty($assigneeIds)) {
            $assigneeIds = [$user->id];
        }

        $base = [
            'title' => trim($this->taskForm['title']),
            'description' => $this->taskForm['description'] ?: null,
            'due_date' => $this->taskForm['due_date'] ?: null,
            'due_time' => $this->taskForm['due_time'] ?: null,
            'priority' => $this->taskForm['priority'] ?? 'medium',
            'important' => (bool) ($this->taskForm['important'] ?? false),
            'reminder_at' => $this->taskForm['reminder_at'] ? Carbon::parse($this->taskForm['reminder_at']) : null,
            'recurrence' => $this->taskForm['recurrence'] ?? 'none',
        ];

        $createdTasks = [];

        if ($this->editingTask && $this->taskId) {
            $task = $this->findOwnedTask($this->taskId);
            if (! $task) {
                return;
            }

            $wasAssignedTo = $task->assigned_to_id;
            $task->update([...$base, 'assigned_to_id' => $assigneeIds[0]]);

            if ($assigneeIds[0] !== $user->id && $assigneeIds[0] !== $wasAssignedTo) {
                $recipient = User::query()->find($assigneeIds[0]);
                $recipient?->notify(new TaskAssignedNotification($task->fresh()));
            }

            $createdTasks[] = $task;
        } else {
            foreach ($assigneeIds as $assigneeId) {
                $task = UserTask::create([
                    ...$base,
                    'school_id' => $user->school_id,
                    'created_by_id' => $user->id,
                    'assigned_to_id' => $assigneeId,
                    'status' => UserTask::STATUS_OPEN,
                ]);
                $createdTasks[] = $task;

                if ($assigneeId !== $user->id) {
                    $recipient = User::query()->find($assigneeId);
                    $recipient?->notify(new TaskAssignedNotification($task));
                }
            }
        }

        $this->dispatch('taskCreated');
        $this->closeTaskModal();
    }

    public function toggleTaskDone(int $id): void
    {
        $task = $this->findOwnedTask($id);
        if (! $task) {
            return;
        }

        $task->isDone() ? $task->markOpen() : $task->markDone();
        $this->dispatch('taskCreated');
    }

    public function toggleTaskImportant(int $id): void
    {
        $task = $this->findOwnedTask($id);
        if (! $task) {
            return;
        }

        $task->update(['important' => ! $task->important]);
        $this->dispatch('taskCreated');
    }

    public function deleteTask(int $id): void
    {
        $this->deleteTaskId = $id;
    }

    public function cancelDeleteTask(): void
    {
        $this->deleteTaskId = null;
    }

    public function confirmDeleteTask(): void
    {
        $task = $this->findOwnedTask($this->deleteTaskId);
        if ($task) {
            $task->delete();
        }
        $this->deleteTaskId = null;
        $this->dispatch('taskCreated');
    }

    public function moveTask(int $id, string $date, ?string $time = null): void
    {
        if (! $this->validDate($date)) {
            return;
        }

        $task = $this->findOwnedTask($id);
        if (! $task) {
            return;
        }

        $task->update([
            'due_date' => Carbon::parse($date)->toDateString(),
            'due_time' => $time ?: $task->due_time,
        ]);

        $this->dispatch('taskCreated');
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

    protected function findOwnedTask(?int $taskId): ?UserTask
    {
        $user = $this->user();
        if (! $user || ! $taskId) {
            return null;
        }

        return UserTask::query()
            ->visibleTo($user->id)
            ->find($taskId);
    }

    /**
     * Build the picker spec for an existing task so editing a fan-out copy
     * shows the correct single assignee.
     */
    protected function assigneeSpecForEdit(UserTask $task): string
    {
        $user = $this->user();

        if (! $task->assigned_to_id || $task->assigned_to_id === $user?->id) {
            return json_encode(['mode' => 'self']);
        }

        $student = Student::query()
            ->where('school_id', $task->school_id)
            ->where('user_id', $task->assigned_to_id)
            ->first();

        if ($student) {
            return json_encode([
                'mode' => 'students',
                'scope' => 'individuals',
                'student_ids' => [(int) $student->id],
            ]);
        }

        return json_encode([
            'mode' => 'staff',
            'staff_ids' => [(int) $task->assigned_to_id],
        ]);
    }
}
