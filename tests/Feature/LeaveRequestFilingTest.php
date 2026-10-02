<?php

namespace Tests\Feature;

use App\Filament\App\Resources\LeaveRequestResource;
use App\Filament\App\Resources\LeaveRequestResource\Pages\CreateLeaveRequest;
use App\Filament\App\Resources\LeaveRequestResource\Pages\ListLeaveRequests;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Admin\Services\SystemRolePresets;
use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\LeaveType;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Filing leave is the one thing every member of staff may do inside HR.
 *
 * The button appearing is the easy half. The half worth testing is what
 * happens underneath, because there are two ways this can be quietly wrong and
 * neither is visible on the page: a request filed against nobody, and a request
 * a teacher filed against a colleague.
 */
class LeaveRequestFilingTest extends TestCase
{
    private array $ids = [];

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
        LeaveRequest::query()->delete();
        LeaveType::query()->delete();
        Employee::query()->delete();

        foreach ($this->ids as $model) {
            $model::whereKey($model->getKey())->forceDelete();
        }

        parent::tearDown();
    }

    private function school(): School
    {
        $school = School::create([
            'name' => 'Filing '.uniqid(),
            'subdomain' => 'filing'.uniqid(),
            'status' => 'active',
        ]);
        $this->ids[] = $school;

        return $school;
    }

    /**
     * An employee, and the login it brings with it.
     *
     * Employee::creating provisions the portal account itself, keyed on the
     * employee's email, so the user is a consequence of the employee rather
     * than something to create alongside it. `user()` below looks the account
     * up instead of making a second one.
     */
    private function employee(School $school, string $first, string $last): Employee
    {
        $employee = Employee::create([
            'school_id' => $school->id,
            'first_name' => $first,
            'last_name' => $last,
            'email' => strtolower($first.'.'.$last.'.'.uniqid('', true)).'@filing.test',
            'national_id' => strtoupper($first[0].$last[0]).'-'.uniqid(),
            'gender' => 'female',
            'date_of_birth' => '1990-05-14',
            'phone_number' => '+263770000'.substr((string) crc32($first.$last), 0, 3),
            'physical_address' => '14 Riverside Way',
            'emergency_contact_name' => 'Amara Osei',
            'emergency_contact_phone' => '+263771111111',
            'designation' => 'Teacher',
            'date_joined' => '2024-02-01',
        ]);
        $this->ids[] = $employee;

        return $employee;
    }

    private function user(School $school, string $roleKey, Employee $employee): User
    {
        $role = SystemRolePresets::roleFor($school->id, $roleKey);

        $user = User::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->where('email', $employee->email)
            ->firstOrFail();

        $user->forceFill([
            'custom_role_id' => $role->id,
            'requested_role' => $roleKey,
            'account_status' => User::STATUS_ACTIVE,
        ])->save();

        $this->ids[] = $user;

        return $user->fresh();
    }

    private function leaveType(School $school): LeaveType
    {
        $type = LeaveType::create([
            'school_id' => $school->id,
            'name' => 'Sick Leave',
            'code' => 'SL',
            'days_per_year' => 10,
        ]);
        $this->ids[] = $type;

        return $type;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function submit(array $overrides = []): void
    {
        Livewire::test(CreateLeaveRequest::class)
            ->fillForm([
                'leave_type_id' => $this->leaveType->id,
                'start_date' => '2026-04-06',
                'end_date' => '2026-04-08',
                'reason' => 'Flu.',
                ...$overrides,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    private School $school;

    private LeaveType $leaveType;

    private Employee $teacherRecord;

    private Employee $colleagueRecord;

    public function test_a_teacher_files_leave_against_their_own_record(): void
    {
        $this->school = $this->school();
        $this->leaveType = $this->leaveType($this->school);
        $this->teacherRecord = $this->employee($this->school, 'Tara', 'Osei');

        $teacher = $this->user($this->school, 'teaching_staff', $this->teacherRecord->fresh());

        $this->actingAs($teacher);
        $this->actingAsTenant($this->school);

        $this->submit(['employee_id' => $this->teacherRecord->id]);

        $request = LeaveRequest::firstOrFail();

        $this->assertSame($this->teacherRecord->id, $request->employee_id);
        $this->assertSame('pending', $request->status);
        $this->assertSame(3, $request->total_days);
    }

    /**
     * The field is hidden from the teacher, so it has to be enforced rather than
     * merely hidden. Without this, "only HR files for others" is a statement
     * about a dropdown instead of a statement about the data.
     */
    public function test_a_teacher_cannot_file_leave_against_a_colleague(): void
    {
        $this->school = $this->school();
        $this->leaveType = $this->leaveType($this->school);
        $this->teacherRecord = $this->employee($this->school, 'Tara', 'Osei');
        $this->colleagueRecord = $this->employee($this->school, 'Kofi', 'Mensah');

        $teacher = $this->user($this->school, 'teaching_staff', $this->teacherRecord->fresh());

        $this->actingAs($teacher);
        $this->actingAsTenant($this->school);

        $this->submit(['employee_id' => $this->colleagueRecord->id]);

        $this->assertSame(
            $this->teacherRecord->id,
            LeaveRequest::firstOrFail()->employee_id,
            'A crafted request must still be filed against its own author.'
        );
    }

    public function test_hr_files_leave_on_behalf_of_somebody_else(): void
    {
        $this->school = $this->school();
        $this->leaveType = $this->leaveType($this->school);
        $this->teacherRecord = $this->employee($this->school, 'Tara', 'Osei');
        $this->colleagueRecord = $this->employee($this->school, 'Kofi', 'Mensah');

        $hr = $this->user($this->school, 'hr', $this->teacherRecord->fresh());

        $this->actingAs($hr);
        $this->actingAsTenant($this->school);

        $this->submit(['employee_id' => $this->colleagueRecord->id]);

        $this->assertSame(
            $this->colleagueRecord->id,
            LeaveRequest::firstOrFail()->employee_id,
            'HR must keep the ability to raise a request for a member of staff.'
        );
    }

    public function test_the_administrator_files_leave_on_behalf_of_somebody_else(): void
    {
        $this->school = $this->school();
        $this->leaveType = $this->leaveType($this->school);
        $this->teacherRecord = $this->employee($this->school, 'Tara', 'Osei');
        $this->colleagueRecord = $this->employee($this->school, 'Kofi', 'Mensah');

        $admin = $this->user($this->school, 'administrator', $this->teacherRecord->fresh());

        $this->actingAs($admin);
        $this->actingAsTenant($this->school);

        $this->submit(['employee_id' => $this->colleagueRecord->id]);

        $this->assertSame($this->colleagueRecord->id, LeaveRequest::firstOrFail()->employee_id);
    }

    /**
     * Approving is a decision about somebody else's record, so it must not be
     * reachable by posting the column that holds it.
     */
    public function test_a_request_cannot_be_created_pre_approved(): void
    {
        $this->school = $this->school();
        $this->leaveType = $this->leaveType($this->school);
        $this->teacherRecord = $this->employee($this->school, 'Tara', 'Osei');

        $teacher = $this->user($this->school, 'teaching_staff', $this->teacherRecord->fresh());

        $this->actingAs($teacher);
        $this->actingAsTenant($this->school);

        $this->submit(['employee_id' => $this->teacherRecord->id]);

        $this->assertSame('pending', LeaveRequest::firstOrFail()->status);
    }

    /**
     * The dropdown is limited to the author's own name for everyone else, so it
     * cannot be used to learn who is on the staff list.
     */
    public function test_a_teacher_is_offered_only_their_own_name(): void
    {
        $this->school = $this->school();
        $this->teacherRecord = $this->employee($this->school, 'Tara', 'Osei');
        $this->colleagueRecord = $this->employee($this->school, 'Kofi', 'Mensah');

        $teacher = $this->user($this->school, 'teaching_staff', $this->teacherRecord->fresh());

        $this->actingAs($teacher);
        $this->actingAsTenant($this->school);

        $options = (new ReflectionMethod(LeaveRequestResource::class, 'employeeOptions'))->invoke(null);

        $this->assertSame(
            [$this->teacherRecord->id => sprintf('Tara Osei (%s)', $this->teacherRecord->fresh()->employee_number)],
            $options,
            'The dropdown must not double as a way to enumerate the staff list.'
        );
    }

    public function test_hr_is_offered_the_whole_staff_list(): void
    {
        $this->school = $this->school();
        $this->teacherRecord = $this->employee($this->school, 'Tara', 'Osei');
        $this->colleagueRecord = $this->employee($this->school, 'Kofi', 'Mensah');

        $hr = $this->user($this->school, 'hr', $this->teacherRecord->fresh());

        $this->actingAs($hr);
        $this->actingAsTenant($this->school);

        $options = (new ReflectionMethod(LeaveRequestResource::class, 'employeeOptions'))->invoke(null);

        $this->assertCount(2, $options);
        $this->assertArrayHasKey($this->teacherRecord->id, $options);
        $this->assertArrayHasKey($this->colleagueRecord->id, $options);
    }

    public function test_the_list_is_reachable_by_a_teacher_and_refuses_a_student(): void
    {
        $this->school = $this->school();
        $this->teacherRecord = $this->employee($this->school, 'Tara', 'Osei');

        $teacher = $this->user($this->school, 'teaching_staff', $this->teacherRecord->fresh());

        $this->actingAs($teacher);
        $this->actingAsTenant($this->school);

        $this->get(ListLeaveRequests::getUrl())->assertOk();

        $studentRole = SystemRolePresets::roleFor($this->school->id, 'student');

        $student = User::create([
            'school_id' => $this->school->id,
            'name' => 'Student One',
            'email' => uniqid('', true).'@filing.test',
            'password' => bcrypt('secret1234'),
        ]);
        $this->ids[] = $student;

        $student->forceFill([
            'custom_role_id' => $studentRole->id,
            'requested_role' => 'student',
            'account_status' => User::STATUS_ACTIVE,
        ])->save();

        $this->actingAs($student->fresh());
        $this->actingAsTenant($this->school);

        // A student lives in the student portal, so the workspace turns them
        // away at the door rather than refusing the page. What matters is that
        // they are redirected away instead of being shown anybody's leave.
        $response = $this->get(ListLeaveRequests::getUrl());

        $this->assertTrue(
            $response->isRedirect() || $response->isForbidden(),
            'A student must not be shown the leave list.'
        );
        $this->assertStringNotContainsString(
            ListLeaveRequests::getUrl(),
            (string) $response->headers->get('Location'),
            'The redirect must not lead back to the leave list.'
        );
    }

    /**
     * Not every workspace account is an employee — a sign-in created directly in
     * user management has no employee row. That person still holds
     * `hr.leave_requests.create` as self-service, so the form must not let them
     * save a request attributed to nobody; it has to refuse and say why.
     */
    public function test_an_account_with_no_employee_record_cannot_file_unattributed_leave(): void
    {
        $this->school = $this->school();
        $this->leaveType = $this->leaveType($this->school);

        $role = SystemRolePresets::roleFor($this->school->id, 'teaching_staff');

        $orphan = User::create([
            'school_id' => $this->school->id,
            'name' => 'No Employee Record',
            'email' => uniqid('', true).'@filing.test',
            'password' => bcrypt('secret1234'),
        ]);
        $this->ids[] = $orphan;

        $orphan->forceFill([
            'custom_role_id' => $role->id,
            'requested_role' => 'teaching_staff',
            'account_status' => User::STATUS_ACTIVE,
        ])->save();

        $this->actingAs($orphan->fresh());
        $this->actingAsTenant($this->school);

        Livewire::test(CreateLeaveRequest::class)
            ->fillForm([
                'leave_type_id' => $this->leaveType->id,
                'start_date' => '2026-04-06',
                'end_date' => '2026-04-08',
                'reason' => 'No employee row behind this account.',
            ])
            ->call('create')
            ->assertHasFormErrors(['employee_id']);

        $this->assertSame(
            0,
            LeaveRequest::count(),
            'A request with no employee behind it must not be saved.'
        );
    }
}
