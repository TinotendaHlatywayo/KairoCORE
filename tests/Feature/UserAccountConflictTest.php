<?php

namespace Tests\Feature;

use App\Exceptions\RegistrationConflictException;
use App\Filament\App\Resources\UserAccountResource\Pages\CreateUserAccount;
use App\Models\School;
use App\Models\User;
use App\Services\UserRegistrationService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\Admin\Models\CustomRole;
use Modules\HR\Models\Employee;
use Tests\TestCase;

class UserAccountConflictTest extends TestCase
{
    use InteractsWithDatabase;

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

    private function tenant(): array
    {
        $school = School::where('subdomain', 'rujeko')->first() ?? School::first();
        $this->assertNotNull($school, 'A school record is required.');

        App::instance('current_tenant', $school);
        URL::defaults(['tenant' => $school->subdomain]);

        $admin = $this->adminUser($school);
        $this->actingAs($admin);
        Filament::setTenant($school);

        return [$school, $admin];
    }

    private function adminUser(School $school): User
    {
        $role = CustomRole::where('school_id', $school->id)->where('name', 'Administrator')->first();

        if ($role) {
            $existing = User::where('school_id', $school->id)->where('custom_role_id', $role->id)->first();
            if ($existing) {
                return $existing;
            }
        }

        $user = User::create([
            'school_id' => $school->id,
            'name' => 'Conflict Admin',
            'email' => 'conflict-admin-'.uniqid().'@test.local',
            'password' => 'Password123!',
            'account_status' => 'active',
        ]);

        if (! $role) {
            $role = CustomRole::create([
                'school_id' => $school->id,
                'name' => 'Administrator',
                'description' => 'Test fixture administrator role.',
                'permissions' => [],
                'is_system' => true,
            ]);
        }

        $user->forceFill(['custom_role_id' => $role->id])->save();

        return $user;
    }

    private function purgeUser(User $user): void
    {
        User::withTrashed()->where('id', $user->id)->forceDelete();
    }

    private function makeExisting(School $school, string $email, string $status = 'active', bool $trashed = false): User
    {
        $user = User::create([
            'school_id' => $school->id,
            'name' => 'Existing Person',
            'email' => $email,
            'password' => 'Password123!',
            'account_status' => $status,
        ]);

        if ($trashed) {
            $user->delete();
        }

        return $user;
    }

    public function test_find_conflicting_includes_trashed_accounts(): void
    {
        [$school, $admin] = $this->tenant();
        $email = 'find-conflict-'.uniqid().'@test.local';

        $active = $this->makeExisting($school, $email);
        $this->assertSame($active->id, app(UserRegistrationService::class)->findConflicting($school->id, $email)->id);

        $active->delete();
        $this->assertNotNull(app(UserRegistrationService::class)->findConflicting($school->id, $email), 'Trashed account still occupies the email.');

        $this->purgeUser($active);
    }

    public function test_register_throws_conflict_without_mode(): void
    {
        [$school, $admin] = $this->tenant();
        $email = 'throw-conflict-'.uniqid().'@test.local';

        $existing = $this->makeExisting($school, $email, 'rejected');

        try {
            app(UserRegistrationService::class)->register($school, [
                'name' => 'New Person',
                'email' => $email,
                'password' => 'Password123!',
                'requested_role' => 'student',
            ]);

            $this->fail('Expected RegistrationConflictException.');
        } catch (RegistrationConflictException $e) {
            $this->assertSame($existing->id, $e->conflictingUser->id);
        } finally {
            $this->purgeUser($existing);
        }

        $this->assertSame(0, User::withTrashed()->where('school_id', $school->id)->where('email', $email)->count());
    }

    public function test_register_replace_permanently_deletes_old_account(): void
    {
        [$school, $admin] = $this->tenant();
        $email = 'replace-'.uniqid().'@test.local';

        $existing = $this->makeExisting($school, $email, 'rejected', trashed: true);

        $new = app(UserRegistrationService::class)->register($school, [
            'name' => 'New Person',
            'email' => $email,
            'password' => 'Password123!',
            'requested_role' => 'student',
        ], 'replace');

        $this->assertNotSame($existing->id, $new->id);
        $this->assertNull(User::withTrashed()->find($existing->id), 'Old account must be permanently gone.');
        $this->assertSame('New Person', $new->name);
        $this->assertSame('pending', $new->account_status);
        $this->assertSame(1, User::withTrashed()->where('school_id', $school->id)->where('email', $email)->count());

        $this->purgeUser($new);
    }

    public function test_register_merge_restores_trashed_and_requeues(): void
    {
        [$school, $admin] = $this->tenant();
        $email = 'merge-'.uniqid().'@test.local';

        $existing = $this->makeExisting($school, $email, 'active', trashed: true);

        $merged = app(UserRegistrationService::class)->register($school, [
            'name' => 'Merged Name',
            'email' => $email,
            'password' => 'Password123!',
            'requested_role' => 'teaching_staff',
        ], 'merge');

        $this->assertSame($existing->id, $merged->id);
        $this->assertNull($merged->deleted_at, 'Merged account must be restored.');
        $this->assertSame('Merged Name', $merged->name);
        $this->assertSame('teaching_staff', $merged->requested_role);
        $this->assertSame('pending', $merged->account_status);
        $this->assertSame(1, User::withTrashed()->where('school_id', $school->id)->where('email', $email)->count());

        $this->purgeUser($merged);
    }

    public function test_page_replace_resolves_conflict(): void
    {
        [$school, $admin] = $this->tenant();
        $this->actingAs($admin);

        $email = 'page-replace-'.uniqid().'@test.local';
        $old = $this->makeExisting($school, $email, 'rejected', trashed: true);

        $component = Livewire::test(CreateUserAccount::class)
            ->fillForm([
                'name' => 'New Person',
                'email' => $email,
                'password' => 'Password123!',
                'requested_role' => 'student',
                'account_status' => 'pending',
            ])
            ->call('create');

        $component->assertSet('conflictingUserId', $old->id)
            ->assertSee('An account with this email already exists')
            ->fillForm(['conflict_mode' => 'replace'])
            ->call('create');

        $this->assertNull(User::withTrashed()->find($old->id), 'Old account must be permanently gone.');

        $new = User::where('school_id', $school->id)->where('email', $email)->first();
        $this->assertNotNull($new);
        $this->assertSame('New Person', $new->name);
        $this->assertSame('pending', $new->account_status);

        $this->purgeUser($new);
    }

    public function test_page_merge_resolves_conflict(): void
    {
        [$school, $admin] = $this->tenant();
        $this->actingAs($admin);

        $email = 'page-merge-'.uniqid().'@test.local';
        $old = $this->makeExisting($school, $email, 'active');

        $component = Livewire::test(CreateUserAccount::class)
            ->fillForm([
                'name' => 'Merged Name',
                'email' => $email,
                'password' => 'Password123!',
                'requested_role' => 'teaching_staff',
            ])
            ->call('create');

        $component->assertSet('conflictingUserId', $old->id)
            ->fillForm(['conflict_mode' => 'merge'])
            ->call('create');

        $merged = User::where('school_id', $school->id)->where('email', $email)->first();
        $this->assertSame($old->id, $merged->id);
        $this->assertSame('Merged Name', $merged->name);
        $this->assertSame('teaching_staff', $merged->requested_role);
        $this->assertSame('pending', $merged->account_status);
        $this->assertSame(1, User::withTrashed()->where('school_id', $school->id)->where('email', $email)->count());

        $this->purgeUser($merged);
    }

    public function test_page_creates_normally_without_conflict(): void
    {
        [$school, $admin] = $this->tenant();
        $this->actingAs($admin);

        $email = 'fresh-'.uniqid().'@test.local';

        Livewire::test(CreateUserAccount::class)
            ->fillForm([
                'name' => 'Fresh Person',
                'email' => $email,
                'password' => 'Password123!',
                'requested_role' => 'student',
                'account_status' => 'pending',
            ])
            ->call('create');

        $user = User::where('school_id', $school->id)->where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertSame('Fresh Person', $user->name);

        $this->purgeUser($user);
    }

    public function test_employee_creation_restores_trashed_user(): void
    {
        [$school, $admin] = $this->tenant();
        $this->actingAs($admin);

        $email = 'staff-reuse-'.uniqid().'@test.local';
        $old = $this->makeExisting($school, $email, 'active', trashed: true);

        $employee = Employee::create([
            'school_id' => $school->id,
            'first_name' => 'Reuse',
            'last_name' => 'Staff',
            'national_id' => '99-7654321A00',
            'gender' => 'female',
            'date_of_birth' => '1992-02-02',
            'marital_status' => 'single',
            'phone_number' => '+263770000003',
            'email' => $email,
            'physical_address' => 'Test Address',
            'emergency_contact_name' => 'Test Contact',
            'emergency_contact_phone' => '+263770000004',
            'department' => 'Academics',
            'designation' => 'English Teacher',
            'role' => 'Teacher',
            'employment_type' => 'Permanent',
            'date_joined' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->assertSame($old->id, $employee->user_id, 'The soft-deleted account must be restored and reused.');
        $this->assertNull(User::withTrashed()->find($old->id)->deleted_at, 'The restored user must not be trashed.');
        $this->assertSame(1, User::withTrashed()->where('school_id', $school->id)->where('email', $email)->count());

        $employee->forceDelete();
        $this->purgeUser($old);
    }
}
