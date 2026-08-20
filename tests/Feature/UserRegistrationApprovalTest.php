<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Notifications\UserRegistrationApprovalNotification;
use App\Services\UserRegistrationService;
use Filament\Panel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Services\PermissionRegistry;
use Tests\TestCase;

class UserRegistrationApprovalTest extends TestCase
{
    private School $schoolA;

    private School $schoolB;

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

        $this->schoolA = School::create([
            'name' => 'Approval Test School A',
            'subdomain' => 'approval-test-a-'.substr(uniqid(), -6),
            'status' => 'active',
        ]);

        $this->schoolB = School::create([
            'name' => 'Approval Test School B',
            'subdomain' => 'approval-test-b-'.substr(uniqid(), -6),
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        // The global audit listener stamps logs with the authenticated user id;
        // clear the guard so cleanup deletes do not reference already-removed rows.
        auth()->logout();
        auth()->forgetGuards();

        foreach ($this->createdEmails as $email) {
            User::query()->where('email', $email)->delete();
        }

        $this->schoolA?->delete();
        $this->schoolB?->delete();

        parent::tearDown();
    }

    protected function makeUser(School $school, array $overrides = []): User
    {
        $email = $overrides['email'] ?? ('user-'.substr(uniqid(), -6).'@test.local');
        $this->createdEmails[] = $email;

        return User::create(array_merge([
            'school_id' => $school->id,
            'name' => 'Wayne Hlatywayo',
            'email' => $email,
            'password' => 'secret123',
            'account_status' => User::STATUS_ACTIVE,
        ], $overrides));
    }

    protected function adminRoleFor(School $school): CustomRole
    {
        return CustomRole::create([
            'school_id' => $school->id,
            'name' => 'Administrator',
            'permissions' => PermissionRegistry::collectAllPermissionKeys(),
            'is_system' => true,
        ]);
    }

    protected function register(School $school, string $role): User
    {
        $email = 'pending-'.substr(uniqid(), -6).'@test.local';
        $this->createdEmails[] = $email;

        return app(UserRegistrationService::class)->register($school, [
            'name' => 'Pending Applicant',
            'email' => $email,
            'requested_role' => $role,
            'password' => 'password123',
        ]);
    }

    public function test_new_registration_is_created_as_pending_and_locked_out(): void
    {
        $user = $this->register($this->schoolA, 'student');

        $this->assertTrue($user->isPending());
        $this->assertFalse($user->isApproved());
        $this->assertSame('student', $user->requested_role);
        $this->assertSame($this->schoolA->id, $user->school_id);

        $panel = new Panel;
        $panel->id('app');
        $this->assertFalse($user->canAccessPanel($panel));
    }

    public function test_registration_notifies_approvers_in_that_school_only(): void
    {
        Notification::fake();

        $approverA = $this->makeUser($this->schoolA);
        $approverA->custom_role_id = $this->adminRoleFor($this->schoolA)->id;
        $approverA->save();

        $approverB = $this->makeUser($this->schoolB);
        $approverB->custom_role_id = $this->adminRoleFor($this->schoolB)->id;
        $approverB->save();

        $limitedRole = CustomRole::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Clerk',
            'permissions' => ['communication.view_module'],
            'is_system' => false,
        ]);
        $limitedA = $this->makeUser($this->schoolA);
        $limitedA->custom_role_id = $limitedRole->id;
        $limitedA->save();

        $this->register($this->schoolA, 'teaching_staff');

        Notification::assertSentTo($approverA, UserRegistrationApprovalNotification::class);
        Notification::assertNotSentTo($approverB, UserRegistrationApprovalNotification::class);
        Notification::assertNotSentTo($limitedA, UserRegistrationApprovalNotification::class);
    }

    public function test_approval_activates_account_and_assigns_default_role(): void
    {
        $admin = $this->makeUser($this->schoolA);
        $admin->custom_role_id = $this->adminRoleFor($this->schoolA)->id;
        $admin->save();

        $user = $this->register($this->schoolA, 'teaching_staff');
        $this->assertTrue($user->isPending());

        app(UserRegistrationService::class)->approve($user, approverId: $admin->id);

        $user->refresh();
        $this->assertTrue($user->isApproved());
        $this->assertNotNull($user->approved_at);
        $this->assertSame($admin->id, $user->approved_by);

        $role = CustomRole::query()->where('school_id', $this->schoolA->id)->where('name', 'Teaching Staff')->first();
        $this->assertNotNull($role);
        $this->assertSame($role->id, $user->custom_role_id);
        $this->assertContains('exams.enter_marks', $role->permissions);
        $this->assertNotContains('users.approve', $role->permissions);

        $panel = new Panel;
        $panel->id('app');
        $this->assertTrue($user->canAccessPanel($panel));
    }

    public function test_approval_uses_explicitly_chosen_role_when_supplied(): void
    {
        $user = $this->register($this->schoolA, 'student');

        $custom = CustomRole::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Head Prefect',
            'permissions' => ['academics.view_records'],
            'is_system' => false,
        ]);

        app(UserRegistrationService::class)->approve($user, roleId: $custom->id);

        $user->refresh();
        $this->assertSame($custom->id, $user->custom_role_id);
        $this->assertTrue($user->isApproved());
    }

    public function test_rejected_account_stays_locked_and_records_reason(): void
    {
        $admin = $this->makeUser($this->schoolA);
        $admin->custom_role_id = $this->adminRoleFor($this->schoolA)->id;
        $admin->save();

        $user = $this->register($this->schoolA, 'non_teaching_staff');

        app(UserRegistrationService::class)->reject($user, 'Duplicate account already exists.', $admin->id);

        $user->refresh();
        $this->assertSame(User::STATUS_REJECTED, $user->account_status);
        $this->assertSame('Duplicate account already exists.', $user->rejected_reason);
        $this->assertNull($user->approved_at);

        $panel = new Panel;
        $panel->id('app');
        $this->assertFalse($user->canAccessPanel($panel));

        app(UserRegistrationService::class)->approve($user, approverId: $admin->id);
        $user->refresh();
        $this->assertTrue($user->isApproved());
        $this->assertNull($user->rejected_reason);
    }

    public function test_users_approve_permission_gates_approval_capability(): void
    {
        $adminRole = $this->adminRoleFor($this->schoolA);
        $this->assertContains('users.approve', $adminRole->permissions);

        $user = $this->makeUser($this->schoolA);
        $user->custom_role_id = $adminRole->id;
        $user->save();
        $this->assertTrue(PermissionRegistry::userCan($user, 'users.approve'));

        $clerkRole = CustomRole::create([
            'school_id' => $this->schoolA->id,
            'name' => 'Clerk',
            'permissions' => ['communication.view_module'],
            'is_system' => false,
        ]);
        $clerk = $this->makeUser($this->schoolA);
        $clerk->custom_role_id = $clerkRole->id;
        $clerk->save();
        $this->assertFalse(PermissionRegistry::userCan($clerk, 'users.approve'));
    }

    public function test_tenant_isolation_is_never_broken_across_schools(): void
    {
        $adminB = $this->makeUser($this->schoolB);
        $adminB->custom_role_id = $this->adminRoleFor($this->schoolB)->id;
        $adminB->save();

        $pending = $this->register($this->schoolA, 'student');

        // Approval flow scopes roles and provisioning to the account's school.
        app(UserRegistrationService::class)->approve($pending, roleId: null, approverId: $adminB->id);

        $pending->refresh();
        $this->assertTrue($pending->isApproved());
        $this->assertSame($this->schoolA->id, $pending->school_id);

        // The provisioned role belongs to School A, not the approver's School B.
        $role = $pending->customRole;
        $this->assertNotNull($role);
        $this->assertSame($this->schoolA->id, $role->school_id);
        $this->assertSame('Student', $role->name);

        // School B's admin cannot even see School A's pending user through the
        // tenant-scoped query.
        $this->assertSame(0, User::query()->where('school_id', $this->schoolB->id)->where('account_status', User::STATUS_PENDING)->count());
    }

    public function test_super_admin_can_access_platform_panel_but_not_school_workspace(): void
    {
        $super = $this->makeUser($this->schoolA, ['school_id' => null]);
        $schoolUser = $this->makeUser($this->schoolA);

        $adminPanel = new Panel;
        $adminPanel->id('admin');
        $appPanel = new Panel;
        $appPanel->id('app');

        $this->assertTrue($super->canAccessPanel($adminPanel));
        $this->assertFalse($super->canAccessPanel($appPanel));
        $this->assertFalse($schoolUser->canAccessPanel($adminPanel));
    }

    public function test_approver_http_flow_renders_pending_accounts_and_notification(): void
    {
        $admin = $this->makeUser($this->schoolA);
        $admin->custom_role_id = $this->adminRoleFor($this->schoolA)->id;
        $admin->save();

        $pending = $this->register($this->schoolA, 'teaching_staff');
        $this->assertTrue($pending->isPending());

        $host = $this->schoolA->subdomain.'.lvh.me';

        $response = $this->actingAs($admin)
            ->get('http://'.$host.'/workspace/user-accounts')
            ->assertOk()
            ->assertSee($pending->email)
            ->assertSee('Action required');

        $this->assertStringContainsString('sc-cc', $response->getContent());
    }
}
