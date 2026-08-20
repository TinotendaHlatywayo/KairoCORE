<?php

namespace Tests\Feature;

use App\Livewire\StudentTopbarCommandCenter;
use App\Livewire\TopbarCommandCenter;
use App\Models\School;
use App\Models\User;
use App\Models\UserTask;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\UserRegistrationApprovalNotification;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Services\PermissionRegistry;
use Tests\TestCase;

class TopbarCommandCenterTest extends TestCase
{
    private School $school;

    private array $createdEmails = [];

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.env', 'local');
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');

        $this->school = School::create([
            'name' => 'Command Center Test School',
            'subdomain' => 'cc-test-'.substr(uniqid(), -8),
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        auth()->logout();
        auth()->forgetGuards();

        foreach ($this->createdEmails as $email) {
            User::query()->where('email', $email)->delete();
        }

        if ($this->school) {
            UserTask::query()->where('school_id', $this->school->id)->delete();
            $this->school->forceDelete();
        }

        parent::tearDown();
    }

    protected function user(array $overrides = []): User
    {
        $email = $overrides['email'] ?? ('cc-user-'.substr(uniqid(), -8).'@test.local');
        $this->createdEmails[] = $email;

        return User::create(array_merge([
            'school_id' => $this->school->id,
            'name' => 'Command Center User',
            'email' => $email,
            'password' => 'secret123',
            'account_status' => User::STATUS_ACTIVE,
        ], $overrides));
    }

    protected function adminRole(): CustomRole
    {
        return CustomRole::create([
            'school_id' => $this->school->id,
            'name' => 'Administrator',
            'permissions' => PermissionRegistry::collectAllPermissionKeys(),
            'is_system' => true,
        ]);
    }

    protected function basicRole(): CustomRole
    {
        return CustomRole::create([
            'school_id' => $this->school->id,
            'name' => 'Student',
            'permissions' => ['tasks.view', 'tasks.create', 'academics.view_records'],
            'is_system' => false,
        ]);
    }

    protected function makeTask(User $forUser, ?User $assignee = null, array $overrides = []): UserTask
    {
        return UserTask::create(array_merge([
            'school_id' => $this->school->id,
            'created_by_id' => $forUser->id,
            'assigned_to_id' => $assignee?->id ?? $forUser->id,
            'title' => 'Draft final report',
            'due_date' => now()->addDays(2)->toDateString(),
            'status' => UserTask::STATUS_OPEN,
        ], $overrides));
    }

    public function test_user_sees_only_tasks_assigned_to_or_created_by_them(): void
    {
        $me = $this->user();
        $other = $this->user();

        $mine = $this->makeTask($me);
        $this->makeTask($other);

        Livewire::actingAs($me)
            ->test(TopbarCommandCenter::class)
            ->assertViewHas('tasks', function ($tasks) use ($mine) {
                return $tasks->contains('id', $mine->id)
                    && $tasks->every(fn ($task) => $task->assigned_to_id === auth()->id());
            });
    }

    public function test_standard_user_cannot_assign_tasks(): void
    {
        $me = $this->user();
        $me->custom_role_id = $this->basicRole()->id;
        $me->save();

        Livewire::actingAs($me)->test(TopbarCommandCenter::class)
            ->assertSet('canAssign', false)
            ->assertSet('assigneeOptions', []);
    }

    public function test_admin_can_assign_task_to_another_user(): void
    {
        $admin = $this->user();
        $admin->custom_role_id = $this->adminRole()->id;
        $admin->save();

        $assignee = $this->user();

        Livewire::actingAs($admin)
            ->test(TopbarCommandCenter::class)
            ->assertSet('canAssign', true)
            ->call('openAddTask', now()->addDays(1)->toDateString())
            ->set('taskTitle', 'Prepare staff meeting minutes')
            ->set('taskAssigneeId', $assignee->id)
            ->call('saveTask')
            ->assertDispatched('taskCreated');

        $task = UserTask::query()
            ->where('school_id', $this->school->id)
            ->where('title', 'Prepare staff meeting minutes')
            ->first();
        $this->assertNotNull($task);
        $this->assertSame($assignee->id, $task->assigned_to_id);
        $this->assertSame($admin->id, $task->created_by_id);
        $this->assertSame($this->school->id, $task->school_id);
    }

    public function test_self_creation_defaults_to_current_user(): void
    {
        $me = $this->user();

        Livewire::actingAs($me)
            ->test(TopbarCommandCenter::class)
            ->call('openAddTask', today()->toDateString())
            ->set('taskTitle', 'Submit leave form')
            ->call('saveTask');

        $task = UserTask::query()
            ->where('school_id', $this->school->id)
            ->where('title', 'Submit leave form')
            ->first();
        $this->assertNotNull($task);
        $this->assertSame($me->id, $task->assigned_to_id);
        $this->assertSame($me->id, $task->created_by_id);
    }

    public function test_preset_last7_filters_tasks_by_due_date(): void
    {
        $me = $this->user();

        $inside = $this->makeTask($me, null, ['title' => 'Inside window', 'due_date' => today()->toDateString()]);
        $this->makeTask($me, null, ['title' => 'Outside window', 'due_date' => today()->addDays(20)->toDateString()]);

        Livewire::actingAs($me)
            ->test(TopbarCommandCenter::class)
            ->call('choosePreset', 'last7')
            ->assertViewHas('tasks', function ($tasks) use ($inside) {
                return $tasks->contains('id', $inside->id)
                    && $tasks->every(fn ($task) => $task->due_date->greaterThanOrEqualTo(today()->subDays(6)));
            });
    }

    public function test_two_click_custom_range_selection(): void
    {
        $me = $this->user();

        Livewire::actingAs($me)
            ->test(TopbarCommandCenter::class)
            ->call('choosePreset', 'custom')
            ->call('selectRangeDate', '2026-08-10')
            ->assertSet('rangeStart', '2026-08-10')
            ->call('selectRangeDate', '2026-08-05')
            ->assertSet('rangeStart', '2026-08-05')
            ->assertSet('rangeEnd', '2026-08-10')
            ->assertSet('preset', 'custom');
    }

    public function test_toggle_done_and_delete_task(): void
    {
        $me = $this->user();
        $task = $this->makeTask($me);

        Livewire::actingAs($me)
            ->test(TopbarCommandCenter::class)
            ->call('toggleTaskDone', $task->id);

        $this->assertSame(UserTask::STATUS_DONE, $task->fresh()->status);

        Livewire::actingAs($me)
            ->test(TopbarCommandCenter::class)
            ->call('deleteTask', $task->id);

        $this->assertNull(UserTask::find($task->id));
    }

    public function test_clear_all_hides_tasks_and_notifications_from_interface_but_keeps_them_on_calendar(): void
    {
        $me = $this->user();
        $me->custom_role_id = $this->adminRole()->id;
        $me->save();

        $openOne = $this->makeTask($me);
        $openTwo = $this->makeTask($me, null, ['title' => 'Second task']);
        $me->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => UserRegistrationApprovalNotification::class,
            'data' => json_encode(['format' => 'approval', 'url' => '#']),
        ]);

        Livewire::actingAs($me)
            ->test(TopbarCommandCenter::class)
            ->assertSet('canClear', true)
            ->assertSet('openTaskCount', 2)
            ->assertSet('unreadNotificationCount', 1)
            ->call('clearAllTasks')
            ->assertSet('openTaskCount', 0)
            ->assertSet('unreadNotificationCount', 0)
            ->assertSet('tasks', fn ($tasks) => $tasks->isEmpty());

        // Tasks are marked done + cleared, NOT deleted — the calendar keeps them.
        $this->assertSame(UserTask::STATUS_DONE, $openOne->fresh()->status);
        $this->assertSame(UserTask::STATUS_DONE, $openTwo->fresh()->status);
        $this->assertNotNull($openOne->fresh()->cleared_at);
        $this->assertNotNull($openTwo->fresh()->cleared_at);

        // Notifications are dismissed but still retained (history).
        $this->assertNotNull($me->fresh()->notifications()->first()->cleared_at);

        // The calendar day still carries the task.
        $component = Livewire::actingAs($me)->test(TopbarCommandCenter::class);
        $days = $component->get('calendarDays')['days'];
        $withTask = collect($days)->firstWhere('hasTasks', true);
        $this->assertNotNull($withTask);
    }

    public function test_dnd_toggle_persists_on_user(): void
    {
        $me = $this->user();

        Livewire::actingAs($me)
            ->test(TopbarCommandCenter::class)
            ->call('setDnd', true)
            ->assertSet('dnd', true);

        $this->assertTrue((bool) $me->fresh()->do_not_disturb);
    }

    public function test_clear_notifications_removes_tracked_notifications(): void
    {
        $me = $this->user();

        $me->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => UserRegistrationApprovalNotification::class,
            'data' => json_encode(['format' => 'approval', 'url' => '#']),
        ]);

        Livewire::actingAs($me)
            ->test(TopbarCommandCenter::class)
            ->assertSet('unreadNotificationCount', 1)
            ->call('clearNotifications')
            ->assertSet('unreadNotificationCount', 0);
    }

    public function test_clear_notifications_dismisses_all_from_interface_but_keeps_history_rows(): void
    {
        $me = $this->user();

        // A tracked type and a non-tracked (arbitrary) type.
        $me->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => UserRegistrationApprovalNotification::class,
            'data' => json_encode(['format' => 'approval', 'url' => '#']),
        ]);
        $me->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => TaskAssignedNotification::class,
            'data' => json_encode(['format' => 'task', 'url' => '#']),
        ]);
        $me->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => Notification::class,
            'data' => json_encode(['format' => 'other']),
        ]);

        Livewire::actingAs($me)
            ->test(TopbarCommandCenter::class)
            ->assertSet('unreadNotificationCount', 2)
            ->call('clearNotifications')
            ->assertSet('unreadNotificationCount', 0);

        // Rows are NOT deleted (history keeps them for 30 days) — they are
        // merely dismissed from the interface (marked cleared + read).
        $remaining = $me->fresh()->notifications()->get();
        $this->assertCount(3, $remaining);
        $this->assertTrue($remaining->every(fn ($n) => $n->cleared_at !== null && $n->read_at !== null));
    }

    public function test_student_portal_clear_notifications_clears_interface(): void
    {
        $me = $this->user();
        Filament::setCurrentPanel(Filament::getPanel('student'));

        $me->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => UserRegistrationApprovalNotification::class,
            'data' => json_encode(['format' => 'approval', 'url' => '#']),
        ]);

        Livewire::actingAs($me)
            ->test(StudentTopbarCommandCenter::class)
            ->assertSet('unreadNotificationCount', 1)
            ->call('clearNotifications')
            ->assertSet('unreadNotificationCount', 0);

        $this->assertNotNull($me->fresh()->notifications()->first()->cleared_at);
    }

    public function test_notification_history_returns_last_30_days_including_untracked(): void
    {
        $me = $this->user();

        $me->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => UserRegistrationApprovalNotification::class,
            'data' => json_encode(['format' => 'approval', 'url' => '#']),
            'created_at' => now()->subDays(5),
        ]);
        $me->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => Notification::class,
            'data' => json_encode(['format' => 'other']),
            'created_at' => now()->subDays(3),
        ]);
        $me->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => UserRegistrationApprovalNotification::class,
            'data' => json_encode(['format' => 'approval', 'url' => '#']),
            'created_at' => now()->subDays(35),
        ]);

        Livewire::actingAs($me)
            ->test(TopbarCommandCenter::class)
            ->assertSet('notificationHistory', function ($history) {
                return $history->count() === 2;
            });
    }

    public function test_tasks_are_colour_coded_by_assignee_category(): void
    {
        $me = $this->user();

        // A user in each category, plus a self-assigned task.
        $student = $this->user(['requested_role' => 'student']);
        $teaching = $this->user(['requested_role' => 'teaching_staff']);
        $nonTeaching = $this->user(['requested_role' => 'non_teaching_staff']);
        $admin = $this->user(['requested_role' => 'administrator']);

        $selfTask = $this->makeTask($me);
        $studentTask = $this->makeTask($me, $student, ['title' => 'Student task']);
        $teachingTask = $this->makeTask($me, $teaching, ['title' => 'Teaching task']);
        $nonTeachingTask = $this->makeTask($me, $nonTeaching, ['title' => 'Non-teaching task']);
        $adminTask = $this->makeTask($me, $admin, ['title' => 'Admin task']);

        $component = Livewire::actingAs($me)->test(TopbarCommandCenter::class);
        $html = $component->html();

        foreach ([
            [$selfTask->id, 'sc-cc-task-self', 'Me'],
            [$studentTask->id, 'sc-cc-task-student', 'Student'],
            [$teachingTask->id, 'sc-cc-task-teaching_staff', 'Teaching'],
            [$nonTeachingTask->id, 'sc-cc-task-non_teaching_staff', 'Non-Teaching'],
            [$adminTask->id, 'sc-cc-task-administrator', 'Admin'],
        ] as [$taskId, $class, $label]) {
            $this->assertStringContainsString($class, $html, "Task {$taskId} should carry {$class}");
            $this->assertStringContainsString($label, $html, "Task {$taskId} should show the {$label} chip");
        }
    }
}
