<?php

namespace Tests\Feature;

use App\Filament\App\Pages\Auth\Login;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Modules\HR\Models\Employee;
use Tests\TestCase;

/**
 * A member of staff who has been suspended or terminated must not be able to
 * sign into the workspace:
 *
 *  - the employment status drives the linked portal account's account_status
 *    (so the EnsureUserActive middleware refuses them too);
 *  - the login screen refuses them directly, with an explanation instead of a
 *    silent bounce back to an empty form;
 *  - an unrelated employee edit never unlocks an account an administrator
 *    suspended on purpose.
 */
class EmployeeAccountStatusTest extends TestCase
{
    private School $school;

    private array $cleanup = [];

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

        Config::set('mail.default', 'array');

        $this->school = School::where('subdomain', 'chiwariraprimary')->first()
            ?? School::firstOrFail();

        $this->actingAsTenant($this->school);
    }

    protected function tearDown(): void
    {
        $this->actingAsTenant($this->school);

        foreach (array_reverse($this->cleanup) as $cleanup) {
            $cleanup();
        }

        parent::tearDown();
    }

    /**
     * Build a staff member whose portal account is provisioned (pending) and
     * then marked active, exactly as it would be once the person activated.
     */
    private function makeStaff(string $tag, string $password = 'StaffPassword!1'): array
    {
        $email = 'lock.'.$tag.'@test.local';

        $employee = Employee::create([
            'school_id' => $this->school->id,
            'first_name' => 'Locked',
            'last_name' => $tag,
            'email' => $email,
            'phone_number' => '+263771000000',
            'national_id' => '63-2541524'.$tag,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'physical_address' => '1 Test Street',
            'emergency_contact_name' => 'Test Kin',
            'emergency_contact_phone' => '+263771000006',
            'date_joined' => now()->toDateString(),
            'designation' => 'Teacher',
            'role' => 'Teaching Staff',
            'employment_type' => 'permanent',
            'status' => 'active',
        ]);

        $user = User::withoutTenantScope()->findOrFail($employee->user_id);
        $user->forceFill([
            'account_status' => User::STATUS_ACTIVE,
            'password' => Hash::make($password),
        ])->saveQuietly();

        $this->cleanup[] = function () use ($employee) {
            // Deleting the employee also removes the portal account it
            // provisioned (see the cascade-delete observer on the model).
            Employee::query()->whereKey($employee->id)->delete();
        };

        return [$employee->fresh(), $user, $password];
    }

    private function uniqueTag(): string
    {
        return strtoupper(substr(uniqid(), -6));
    }

    private function bindTenant(): void
    {
        app()->instance('current_tenant', $this->school);

        // authenticate() rate-limits per email+IP; clear it so repeated runs
        // never trip the lockout.
        Cache::flush();
    }

    public function test_suspending_an_employee_suspends_the_linked_portal_account(): void
    {
        [$employee, $user] = $this->makeStaff($this->uniqueTag());

        $employee->update(['status' => 'suspended']);

        $this->assertSame(
            User::STATUS_SUSPENDED,
            $user->fresh()->account_status,
            'Suspending the employee must suspend the linked portal account.',
        );
    }

    public function test_terminating_an_employee_suspends_the_linked_portal_account(): void
    {
        [$employee, $user] = $this->makeStaff($this->uniqueTag());

        $employee->update(['status' => 'terminated']);

        $this->assertSame(
            User::STATUS_SUSPENDED,
            $user->fresh()->account_status,
            'A terminated employee must be locked out of the portal.',
        );
    }

    public function test_reactivating_an_employee_lifts_the_lock(): void
    {
        [$employee, $user] = $this->makeStaff($this->uniqueTag());

        $employee->update(['status' => 'suspended']);
        $employee->update(['status' => 'active']);

        $this->assertSame(
            User::STATUS_ACTIVE,
            $user->fresh()->account_status,
            'Setting the employee active again must let them back in.',
        );
    }

    public function test_an_unrelated_employee_edit_never_unlocks_a_suspended_account(): void
    {
        [$employee, $user] = $this->makeStaff($this->uniqueTag());

        // The account was suspended directly from the user accounts screen,
        // while the employment record still says active.
        $user->forceFill(['account_status' => User::STATUS_SUSPENDED])->saveQuietly();

        $employee->update(['first_name' => 'Renamed']);

        $this->assertSame(
            User::STATUS_SUSPENDED,
            $user->fresh()->account_status,
            'Editing an unrelated employee field must not unlock the account.',
        );
    }

    public function test_a_pending_account_is_left_alone_by_a_status_change(): void
    {
        [$employee, $user] = $this->makeStaff($this->uniqueTag());

        $user->forceFill(['account_status' => User::STATUS_PENDING])->saveQuietly();

        $employee->update(['status' => 'active']);

        $this->assertSame(
            User::STATUS_PENDING,
            $user->fresh()->account_status,
            'Activation is owned by the provisioning flow — a status change must not activate it.',
        );
    }

    public function test_suspended_staff_cannot_sign_in(): void
    {
        $tag = $this->uniqueTag();
        [$employee, , $password] = $this->makeStaff($tag, 'SuspendMe!1');
        $employee->update(['status' => 'suspended']);

        $this->bindTenant();

        Livewire::test(Login::class)
            ->set('data', [
                'email' => 'lock.'.$tag.'@test.local',
                'password' => $password,
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertStatus(200)
            ->assertHasErrors(['data.email']);

        $this->assertGuest();
    }

    public function test_terminated_staff_cannot_sign_in(): void
    {
        $tag = $this->uniqueTag();
        [$employee, , $password] = $this->makeStaff($tag, 'TerminateMe!1');
        $employee->update(['status' => 'terminated']);

        $this->bindTenant();

        Livewire::test(Login::class)
            ->set('data', [
                'email' => 'lock.'.$tag.'@test.local',
                'password' => $password,
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertStatus(200)
            ->assertHasErrors(['data.email']);

        $this->assertGuest();
    }

    public function test_active_staff_can_sign_in(): void
    {
        $tag = $this->uniqueTag();
        [, , $password] = $this->makeStaff($tag, 'Welcome!123');

        $this->bindTenant();

        Livewire::test(Login::class)
            ->set('data', [
                'email' => 'lock.'.$tag.'@test.local',
                'password' => $password,
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertStatus(200)
            ->assertHasNoErrors();

        $this->assertAuthenticated();
    }
}
