<?php

namespace Tests\Feature;

use App\Livewire\PlatformCommandCenter;
use App\Livewire\TopbarCommandCenter;
use App\Models\School;
use App\Models\User;
use App\Models\UserTask;
use App\Notifications\PlatformTaskAssignedNotification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Admin\Models\CustomRole;
use Tests\TestCase;

class PlatformTaskManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');
    }

    private function platformAdmin(): User
    {
        return User::whereNull('school_id')->where('account_status', User::STATUS_ACTIVE)->firstOrFail();
    }

    private function school(): School
    {
        return School::findOrFail(config('tenancy.single_tenant_id'));
    }

    private function schoolAdmin(School $school): User
    {
        $role = CustomRole::where('school_id', $school->id)->where('role_key', 'administrator')->firstOrFail();

        return User::query()
            ->where('school_id', $school->id)
            ->where('account_status', User::STATUS_ACTIVE)
            ->where(function ($q) use ($role) {
                $q->where('custom_role_id', $role->id)
                    ->orWhere('requested_role', 'administrator');
            })
            ->orderBy('name')
            ->firstOrFail();
    }

    public function test_platform_admin_can_create_a_personal_task(): void
    {
        DB::beginTransaction();
        try {
            $admin = $this->platformAdmin();

            Livewire::actingAs($admin)
                ->test(PlatformCommandCenter::class)
                ->call('openAddTask')
                ->set('taskTitle', 'Personal platform task')
                ->set('taskDate', now()->addDay()->toDateString())
                ->call('saveTask')
                ->assertHasNoErrors()
                ->call('toggle')
                ->assertSee('Personal platform task')
                ->call('setTab', 'notifications')
                ->assertSee('Notifications');

            $this->assertDatabaseHas('user_tasks', [
                'title' => 'Personal platform task',
                'school_id' => null,
                'created_by_id' => $admin->id,
                'assigned_to_id' => $admin->id,
                'status' => UserTask::STATUS_OPEN,
            ], 'mysql');
        } finally {
            DB::rollBack();
        }
    }

    public function test_platform_admin_can_push_a_task_to_a_school_and_its_admin_is_notified(): void
    {
        DB::beginTransaction();
        try {
            $platformAdmin = $this->platformAdmin();
            $school = $this->school();
            $schoolAdmin = $this->schoolAdmin($school);

            Livewire::actingAs($platformAdmin)
                ->test(PlatformCommandCenter::class)
                ->call('openAddTask')
                ->set('taskTitle', 'Submit quarterly report')
                ->set('taskDate', now()->addDays(3)->toDateString())
                ->set('taskSchoolId', $school->id)
                ->call('saveTask')
                ->assertHasNoErrors();

            $task = UserTask::withoutTenantScope()
                ->where('created_by_id', $platformAdmin->id)
                ->where('title', 'Submit quarterly report')
                ->first();

            $this->assertNotNull($task, 'The delegated task must be persisted.');
            $this->assertSame($school->id, $task->school_id, 'The task must belong to the target school.');
            $this->assertSame($schoolAdmin->id, $task->assigned_to_id, 'The task must go to the school administrator.');

            $this->assertDatabaseHas('notifications', [
                'notifiable_id' => $schoolAdmin->id,
                'notifiable_type' => User::class,
                'type' => PlatformTaskAssignedNotification::class,
            ], 'mysql');
        } finally {
            DB::rollBack();
        }
    }

    public function test_delegated_task_appears_in_the_schools_task_manager(): void
    {
        DB::beginTransaction();
        try {
            $platformAdmin = $this->platformAdmin();
            $school = $this->school();
            $schoolAdmin = $this->schoolAdmin($school);

            UserTask::create([
                'school_id' => $school->id,
                'created_by_id' => $platformAdmin->id,
                'assigned_to_id' => $schoolAdmin->id,
                'title' => 'Weekly operations check-in',
                'status' => UserTask::STATUS_OPEN,
            ]);

            $this->actingAs($schoolAdmin);
            App::instance('current_tenant', $school);

            Livewire::actingAs($schoolAdmin)
                ->test(TopbarCommandCenter::class)
                ->call('toggle')
                ->assertSee('Weekly operations check-in');
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Single-tenant installs bind a current_tenant on every request, and the
     * BelongsToTenant hook then stamps school_id onto every insert. A personal
     * platform task must not be filed under that tenant.
     */
    public function test_a_personal_task_stays_unscoped_in_single_tenant_mode(): void
    {
        DB::beginTransaction();
        try {
            $admin = $this->platformAdmin();
            $school = $this->school();

            Config::set('tenancy.mode', 'single');
            Config::set('tenancy.single_tenant_id', $school->id);
            App::instance('current_tenant', $school);

            Livewire::actingAs($admin)
                ->test(PlatformCommandCenter::class)
                ->call('openAddTask')
                ->set('taskTitle', 'Personal task on a single-tenant install')
                ->call('saveTask')
                ->assertHasNoErrors();

            $task = UserTask::withoutTenantScope()
                ->where('created_by_id', $admin->id)
                ->where('title', 'Personal task on a single-tenant install')
                ->firstOrFail();

            $this->assertNull($task->school_id, 'A personal platform task must not be filed under the tenant.');
        } finally {
            DB::rollBack();
        }
    }
}
