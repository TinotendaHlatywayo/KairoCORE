<?php

namespace Tests\Feature;

use App\Filament\App\Resources\CreateEmployee;
use App\Filament\App\Resources\ViewEmployee;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Models\SystemSetting;
use Modules\HR\Models\Employee;
use Tests\TestCase;

/**
 * Guards the staff registration wizard:
 *  - a duplicate national ID inside one school must come back as a form
 *    error, never as a raw HTTP 500 (the MySQL unique index used to win);
 *  - a failed create must not leave an orphaned `users` row behind;
 *  - blank repeater rows are never persisted;
 *  - custom fields appear in the View Employee infolist;
 *  - a failing activation mailer never breaks the create response.
 */
class EmployeeRegistrationTest extends TestCase
{
    private School $school;

    private ?string $hrModuleSaved = null;

    private ?string $hrPageSaved = null;

    private int $usersWatermark = 0;

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

        Config::set('mail.default', 'array');

        $this->school = School::where('subdomain', 'chiwariraprimary')->first()
            ?? School::firstOrFail();

        $this->actingAsTenant($this->school);

        $this->hrModuleSaved = SystemSetting::get('modules', 'hr', '1');
        $this->hrPageSaved = SystemSetting::get('modules', 'employees', '1');
        SystemSetting::set('modules', 'hr', '1');
        SystemSetting::set('modules', 'employees', '1');

        $this->usersWatermark = (int) DB::connection('mysql')->table('users')->max('id');

        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->actingAs($this->adminUser());
    }

    protected function tearDown(): void
    {
        if ($this->hrModuleSaved !== null) {
            SystemSetting::set('modules', 'hr', $this->hrModuleSaved);
        }
        if ($this->hrPageSaved !== null) {
            SystemSetting::set('modules', 'employees', $this->hrPageSaved);
        }

        $this->actingAsTenant($this->school);

        User::query()->where('school_id', $this->school->id)
            ->where('id', '>', $this->usersWatermark)
            ->delete();
        Employee::query()->where('school_id', $this->school->id)
            ->where('email', 'like', 'emp.%@test.local')
            ->delete();
        Employee::query()->where('email', 'like', 'cross.%@test.local')
            ->delete();
        School::query()->where('subdomain', 'like', 'natid-%')->delete();

        parent::tearDown();
    }

    public function test_duplicate_national_id_returns_form_error_not_500(): void
    {
        $tag = strtoupper(substr(uniqid(), -6));
        $existing = $this->makeEmployee($tag, '63-2541524C99');

        $countBefore = Employee::query()->where('school_id', $this->school->id)->count();
        // The fixture's own creating-hook provisions its portal user — the
        // watermark must only cover the rejected create under test.
        $usersAfterFixture = (int) DB::connection('mysql')->table('users')->max('id');

        try {
            $response = Livewire::test(CreateEmployee::class)
                ->fill($this->validFormData($tag, ['data.national_id' => $existing->national_id]))
                ->call('create');

            $response->assertHasErrors(['data.national_id']);
            $response->assertHasNoErrors([
                'data.first_name',
                'data.last_name',
                'data.email',
                'data.gender',
                'data.date_of_birth',
            ]);

            $this->assertSame(
                $countBefore,
                Employee::query()->where('school_id', $this->school->id)->count(),
                'A rejected create must not insert an employee row.',
            );
            $this->assertSame(
                $usersAfterFixture,
                (int) DB::connection('mysql')->table('users')->max('id'),
                'A rejected create must not leave an orphaned portal user.',
            );
        } finally {
            $existing->delete();
        }
    }

    public function test_same_national_id_is_allowed_in_a_different_school(): void
    {
        $tag = strtoupper(substr(uniqid(), -6));
        $other = School::create([
            'name' => 'NatId Cross School '.$tag,
            'subdomain' => 'natid-'.$tag,
            'status' => 'active',
        ]);

        try {
            Employee::create(array_merge(
                $this->employeeAttributes($tag),
                [
                    'school_id' => $other->id,
                    'national_id' => '63-2541524C99',
                    'email' => 'cross.'.$tag.'@test.local',
                ],
            ));

            $response = Livewire::test(CreateEmployee::class)
                ->fill($this->validFormData($tag, ['data.national_id' => '63-2541524C99']))
                ->call('create');

            $response->assertHasNoErrors(['data.national_id']);

            $created = Employee::where('school_id', $this->school->id)
                ->where('national_id', '63-2541524C99')
                ->latest('id')
                ->first();

            $this->assertNotNull($created, 'The same national ID must be registrable in another school.');
            $this->assertSame($tag, $created->last_name);
        } finally {
            Employee::query()->where('email', 'like', 'cross.%@test.local')->delete();
            $other->delete();
        }
    }

    public function test_successful_create_persists_custom_fields_and_skips_blank_repeater_rows(): void
    {
        $tag = strtoupper(substr(uniqid(), -6));

        $response = Livewire::test(CreateEmployee::class)
            ->fill($this->validFormData($tag, [
                'data.custom_fields' => [
                    ['key' => 'Nationality', 'value' => 'British'],
                ],
                'data.individual_allowances' => [
                    ['name' => '', 'amount' => ''],
                ],
            ]))
            ->call('create');

        $response->assertHasNoErrors();

        $employee = Employee::where('school_id', $this->school->id)
            ->where('email', 'emp.'.$tag.'@test.local')
            ->latest('id')
            ->first();

        $this->assertNotNull($employee, 'The employee must be created.');

        $custom = $employee->custom_fields ?? [];
        $this->assertCount(1, $custom, 'The custom field must be persisted.');
        $this->assertSame('Nationality', $custom[0]['key'] ?? null);

        $allowances = array_filter((array) ($employee->individual_allowances ?? []));
        $this->assertSame([], $allowances, 'Blank allowance rows must never persist.');
    }

    public function test_create_never_500s_when_activation_mail_fails(): void
    {
        $tag = strtoupper(substr(uniqid(), -8));

        // Point the mailer at a configuration that cannot be resolved: the
        // activation send inside provisioning explodes, and the page must
        // degrade to a warning instead of bubbling a 500 after the record
        // was already saved.
        Config::set('mail.default', 'exploding-mailer');

        $response = Livewire::test(CreateEmployee::class)
            ->fill($this->validFormData($tag))
            ->call('create');

        Config::set('mail.default', 'array');

        $response->assertHasNoErrors();

        $employee = Employee::where('school_id', $this->school->id)
            ->where('email', 'emp.'.$tag.'@test.local')
            ->latest('id')
            ->first();
        $this->assertNotNull($employee, 'The staff member must survive a failed activation mailer.');
    }

    public function test_view_page_renders_custom_fields(): void
    {
        $tag = strtoupper(substr(uniqid(), -6));
        $employee = $this->makeEmployee($tag, '63-2541524C'.$tag);
        $employee->forceFill([
            'custom_fields' => [
                ['key' => 'Office Room', 'value' => 'B-12'],
                ['key' => 'Certification', 'value' => 'DP-2026'],
            ],
        ])->save();

        try {
            $this->actingAsTenant($this->school);

            $component = Livewire::test(ViewEmployee::class, ['record' => $employee->getRouteKey()])
                ->assertSuccessful();

            $component->assertSee('Custom Fields');
            $component->assertSee('Office Room');
            $component->assertSee('B-12');
            $component->assertSee('Certification');
        } finally {
            $employee->delete();
        }
    }

    /**
     * Every required wizard field, so a test can vary exactly one dimension.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validFormData(string $tag, array $overrides = []): array
    {
        return array_merge([
            'data.national_id' => '63-2541524C'.$tag,
            'data.first_name' => 'Staff',
            'data.last_name' => $tag,
            'data.email' => 'emp.'.$tag.'@test.local',
            'data.gender' => 'male',
            'data.date_of_birth' => '1990-01-01',
            'data.marital_status' => 'single',
            'data.phone_number' => '+263771000009',
            'data.physical_address' => '1 Test Street',
            'data.emergency_contact_name' => 'Test Kin',
            'data.emergency_contact_phone' => '+263771000008',
            'data.designation' => 'Teacher',
            'data.role' => 'Teaching Staff',
            'data.employment_type' => 'permanent',
            'data.date_joined' => '2026-01-01',
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function employeeAttributes(string $tag): array
    {
        return [
            'first_name' => 'Cross',
            'last_name' => $tag,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'phone_number' => '+263771000007',
            'national_id' => '63-2541524C'.$tag,
            'physical_address' => '1 Test Street',
            'emergency_contact_name' => 'Test Kin',
            'emergency_contact_phone' => '+263771000006',
            'designation' => 'Teacher',
            'role' => 'Teaching Staff',
            'employment_type' => 'permanent',
            'date_joined' => '2026-01-01',
            'status' => 'active',
        ];
    }

    private function makeEmployee(string $tag, string $nationalId): Employee
    {
        return Employee::create(array_merge(
            $this->employeeAttributes($tag),
            [
                'school_id' => $this->school->id,
                'email' => 'emp.'.$tag.'@test.local',
                'national_id' => $nationalId,
            ],
        ));
    }

    private function adminUser(): User
    {
        $adminRoleId = CustomRole::where('school_id', $this->school->id)
            ->where('role_key', 'administrator')
            ->value('id');

        $admin = $adminRoleId
            ? User::where('school_id', $this->school->id)->where('custom_role_id', $adminRoleId)->first()
            : null;

        return $admin ?? User::where('school_id', $this->school->id)->where('account_status', 'active')->firstOrFail();
    }
}
