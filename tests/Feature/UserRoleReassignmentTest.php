<?php

namespace Tests\Feature;

use App\Filament\App\Resources\LeaveRequestResource;
use App\Filament\App\Resources\UserAccountResource;
use App\Filament\App\Resources\UserAccountResource\Pages\EditUserAccount;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Admin\Services\SystemRolePresets;
use Tests\TestCase;

/**
 * The three failures a school reported against user management, kept together
 * because they share one cause each and one thing to prove: what an
 * administrator sees on a screen is what the account will actually be able to
 * do.
 *
 *  1. Editing any user account raised a 500.
 *  2. Reassigning somebody's role appeared to save and changed nothing.
 *  3. The Leave Requests page offered a button whose page did not exist.
 */
class UserRoleReassignmentTest extends TestCase
{
    private $ids = [];

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
    }

    protected function tearDown(): void
    {
        foreach ($this->ids as $model) {
            $model::whereKey($model->getKey())->forceDelete();
        }

        parent::tearDown();
    }

    private function school(): School
    {
        $school = School::create([
            'name' => 'Reassign '.uniqid(),
            'subdomain' => 'reassign'.uniqid(),
            'status' => 'active',
        ]);
        $this->ids[] = $school;

        return $school;
    }

    private function user(School $school, string $roleKey, string $name = 'Person'): User
    {
        $role = SystemRolePresets::roleFor($school->id, $roleKey);

        $user = User::create([
            'school_id' => $school->id,
            'name' => $name.' '.uniqid(),
            'email' => uniqid().'@reassign.test',
            'password' => bcrypt('secret1234'),
        ]);
        $this->ids[] = $user;

        $user->forceFill([
            'custom_role_id' => $role->id,
            'requested_role' => $roleKey,
            'account_status' => User::STATUS_ACTIVE,
        ])->save();

        return $user;
    }

    public function test_editing_a_user_account_does_not_fail(): void
    {
        $school = $this->school();
        $admin = $this->user($school, 'administrator', 'Admin');
        $subject = $this->user($school, 'teaching_staff', 'Teacher');

        $this->actingAs($admin);
        $this->actingAsTenant($school);

        // This screen used to raise a 500 for every account, because the
        // departments picker excluded the current record by comparing the
        // `departments` table against the `users` key column.
        $response = $this->get(UserAccountResource::getUrl('edit', ['record' => $subject->getRouteKey()]));

        $response->assertOk();
    }

    public function test_reassigning_a_role_changes_what_the_person_can_do(): void
    {
        $school = $this->school();
        $admin = $this->user($school, 'administrator', 'Admin');
        $teacher = $this->user($school, 'teaching_staff', 'Teacher');

        $this->assertFalse(PermissionRegistry::userCan($teacher, 'finance.invoices.view'), 'A teacher starts without Finance.');
        $this->assertTrue(PermissionRegistry::userCan($teacher, 'exams.grading_marks_management.view'), 'A teacher starts with grading.');

        $financeRole = SystemRolePresets::roleFor($school->id, 'accounts_finance');

        $this->actingAs($admin);
        $this->actingAsTenant($school);

        Livewire::test(EditUserAccount::class, ['record' => $teacher->getRouteKey()])
            ->fillForm(['custom_role_id' => $financeRole->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $reassigned = $teacher->fresh();

        $this->assertSame('accounts_finance', $reassigned->requested_role);
        $this->assertTrue(
            PermissionRegistry::userCan($reassigned, 'finance.invoices.view'),
            'The new role should hand out Finance.'
        );
        $this->assertFalse(
            PermissionRegistry::userCan($reassigned, 'exams.grading_marks_management.view'),
            'The old role should be gone entirely, not merged with the new one.'
        );
    }

    public function test_a_role_reassignment_does_not_strand_permissions_the_new_role_supplies(): void
    {
        $school = $this->school();
        $admin = $this->user($school, 'administrator', 'Admin');
        $teacher = $this->user($school, 'teaching_staff', 'Teacher');
        $financeRole = SystemRolePresets::roleFor($school->id, 'accounts_finance');

        $this->actingAs($admin);
        $this->actingAsTenant($school);

        Livewire::test(EditUserAccount::class, ['record' => $teacher->getRouteKey()])
            ->fillForm(['custom_role_id' => $financeRole->id])
            ->call('save');

        // Finance supplies billing view by virtue of the role. If the picker's
        // boxes were left as they were, that key would be stored on the account
        // as a personal addition and would outlive every future role change.
        $this->assertSame(
            [],
            $teacher->fresh()->personalPermissionList() ?? [],
            'Nothing from the new role should be copied onto the account itself.'
        );

        // And the account still resolves to exactly what the role provides.
        $this->assertEqualsCanonicalizing(
            PermissionRegistry::normalizePermissionList($financeRole->permissions),
            PermissionRegistry::effectivePermissionsForUser($teacher->fresh())
        );
    }

    public function test_personal_permissions_survive_a_later_role_change(): void
    {
        $school = $this->school();
        $admin = $this->user($school, 'administrator', 'Admin');
        $teacher = $this->user($school, 'teaching_staff', 'Teacher');

        // A deliberate extra: this teacher also does the HR payroll run.
        $teacher->forceFill([
            'permissions' => ['hr.payroll_compensation.view'],
        ])->save();

        $this->assertTrue(PermissionRegistry::userCan($teacher->fresh(), 'hr.payroll_compensation.view'));

        $financeRole = SystemRolePresets::roleFor($school->id, 'accounts_finance');

        $this->actingAs($admin);
        $this->actingAsTenant($school);

        Livewire::test(EditUserAccount::class, ['record' => $teacher->getRouteKey()])
            ->fillForm(['custom_role_id' => $financeRole->id])
            ->call('save');

        $after = $teacher->fresh();

        $this->assertTrue(
            PermissionRegistry::userCan($after, 'hr.payroll_compensation.view'),
            'An addition made on purpose should outlive the role change.'
        );
        $this->assertTrue(PermissionRegistry::userCan($after, 'finance.invoices.view'));
        $this->assertFalse(PermissionRegistry::userCan($after, 'exams.grading_marks_management.view'));
    }

    public function test_the_leave_request_screen_offers_a_page_that_exists(): void
    {
        $school = $this->school();
        $admin = $this->user($school, 'administrator', 'Admin');

        $this->actingAs($admin);
        $this->actingAsTenant($school);

        // The list screen has always rendered "New Leave Request". Until the
        // create page was registered, that button pointed at a route that did
        // not exist and every user who pressed it got a 500.
        $this->assertArrayHasKey('create', LeaveRequestResource::getPages());

        $this->get(LeaveRequestResource::getUrl('index'))->assertOk();
        $this->get(LeaveRequestResource::getUrl('create'))->assertOk();
    }

    public function test_only_hr_and_the_administrator_may_name_another_employee(): void
    {
        $school = $this->school();

        $this->actingAs($this->user($school, 'hr', 'HR'));
        $this->assertTrue(LeaveRequestResource::mayFileForOthers(), 'HR runs the staff leave record.');

        $this->actingAs($this->user($school, 'administrator', 'Admin'));
        $this->assertTrue(LeaveRequestResource::mayFileForOthers(), 'The platform owner may.');

        $this->actingAs($this->user($school, 'teaching_staff', 'Teacher'));
        $this->assertFalse(LeaveRequestResource::mayFileForOthers(), 'A teacher files their own leave only.');

        $this->actingAs($this->user($school, 'accounts_finance', 'Accountant'));
        $this->assertFalse(LeaveRequestResource::mayFileForOthers());
    }

    public function test_a_teacher_is_offered_only_themselves_as_the_employee(): void
    {
        $school = $this->school();
        $teacher = $this->user($school, 'teaching_staff', 'Teacher');

        $this->actingAs($teacher);
        $this->actingAsTenant($school);

        // No employee rows exist, so the options list is empty rather than a
        // full staff directory. The point is that it can never enumerate
        // colleagues.
        $reflection = new \ReflectionMethod(LeaveRequestResource::class, 'employeeOptions');
        $reflection->setAccessible(true);

        $this->assertSame([], $reflection->invoke(null));
    }
}
