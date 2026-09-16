<?php

namespace Tests\Feature;

use App\Filament\App\Resources\ApplicationResource;
use App\Http\Controllers\Auth\ActivationController;
use App\Models\School;
use App\Models\User;
use App\Services\Csv\EmployeeCsvService;
use App\Services\RosterAccountProvisioningService;
use App\Services\StudentCsvService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Admissions\Models\Application;
use Modules\HR\Models\Employee;
use Modules\HR\Models\SalaryGrade;
use Modules\Students\Models\Student;
use Tests\TestCase;

class TempRosterRegistrationSmokeTest extends TestCase
{
    private School $school;

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
            'name' => 'Temp Roster Smoke School',
            'subdomain' => 'temp-roster-'.substr(uniqid(), -6),
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        User::query()->where('school_id', $this->school->id)->delete();
        Employee::query()->where('school_id', $this->school->id)->delete();
        Student::query()->where('school_id', $this->school->id)->delete();
        Application::query()->where('school_id', $this->school->id)->delete();
        SalaryGrade::query()->where('school_id', $this->school->id)->delete();
        $this->school->delete();
        parent::tearDown();
    }

    public function test_csv_template_marks_required_columns_and_auto_matches_marked_headers(): void
    {
        $csv = StudentCsvService::templateCsv();
        $this->assertStringContainsString('First Name', $csv);
        $this->assertStringContainsString('Last Name', $csv);
        $this->assertStringContainsString('Form / Grade', $csv);
        $this->assertStringContainsString('Stream / Class', $csv);
        $this->assertStringContainsString('Email Address', $csv);
        $this->assertStringContainsString('Phone Number', $csv);
        $this->assertStringContainsString('Physical Address', $csv);
        $this->assertStringNotContainsString(' *', $csv);

        $mapping = StudentCsvService::guessMapping(['First Name *', 'Last Name *', 'Email *', 'Phone']);
        $this->assertSame('First Name *', $mapping['first_name']);
        $this->assertSame('Last Name *', $mapping['last_name']);
        $this->assertSame('Email *', $mapping['email']);
        $this->assertSame('Phone', $mapping['phone']);
    }

    public function test_csv_import_with_marked_template_stores_registration_fields(): void
    {
        $path = $this->writeCsv(
            "First Name *,Last Name *,Gender *,Date of Birth *,Form / Grade *,Stream / Class *,Email Address,Phone Number,Physical Address\n".
            "Tendai,Moyo,female,2013-05-14,Grade 1,Grade 1A,tendai.moyo@example.com,+263771234567,14 Links Lane Harare\n"
        );

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => '2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => true,
        ]);
        $course = Course::create([
            'school_id' => $this->school->id,
            'name' => 'Grade 1',
        ]);
        $section = Section::create([
            'school_id' => $this->school->id,
            'course_id' => $course->id,
            'name' => 'Grade 1A',
        ]);

        $headers = StudentCsvService::readCsvHeaders($path);
        $map = StudentCsvService::guessMapping($headers);
        $this->assertNotNull($map['email'], 'Email column must auto-match a marked header.');
        $this->assertNotNull($map['phone']);

        $result = StudentCsvService::import($path, $this->school->id, $map);

        $this->assertSame(1, $result['success']);
        $this->assertSame([], array_values(array_filter($result['failures'])), 'Import must succeed cleanly.');

        $student = Student::query()->where('school_id', $this->school->id)->first();
        $this->assertNotNull($student);
        $this->assertSame('tendai.moyo@example.com', $student->getRawOriginal('email'));
        $this->assertSame('+263771234567', $student->getRawOriginal('phone'));
        $this->assertSame('14 Links Lane Harare', $student->getRawOriginal('physical_address'));
    }

    public function test_provision_student_creates_locked_account_with_activation_token_and_links_roster(): void
    {
        $student = Student::create([
            'school_id' => $this->school->id,
            'first_name' => 'Rudo',
            'last_name' => 'Chirwa',
            'gender' => 'female',
            'date_of_birth' => '2012-02-02',
            'admission_date' => now(),
            'status' => 'active',
            'email' => 'rudo.chirwa@example.com',
        ]);

        $user = app(RosterAccountProvisioningService::class)->provisionStudent($student);

        $this->assertNotNull($user);
        $this->assertSame('rudo.chirwa@example.com', $user->email);
        $this->assertSame('Rudo Chirwa', $user->username);
        $this->assertSame(User::STATUS_PENDING, $user->account_status);
        $this->assertNotNull($user->activation_token, 'First creation must issue an activation token.');
        $this->assertSame($user->id, $student->fresh()->user_id, 'Roster record must be linked to the account.');

        $tokenBefore = $user->activation_token;

        // Re-provisioning (as an edit flow would) must NOT issue a second email.
        app(RosterAccountProvisioningService::class)->provisionStudent($student->fresh());
        $this->assertSame($tokenBefore, $user->fresh()->activation_token, 'Activation must only be sent on first creation.');
    }

    public function test_resolve_or_create_student_user_uses_the_real_application_email(): void
    {
        $course = Course::create([
            'school_id' => $this->school->id,
            'name' => 'Grade 1',
        ]);

        $application = Application::create([
            'school_id' => $this->school->id,
            'first_name' => 'Farai',
            'last_name' => 'Ncube',
            'email' => 'farai.ncube@example.com',
            'parent_name' => 'Nozipho Ncube',
            'parent_email' => 'parent.ncube@example.com',
            'parent_phone' => '+263772222333',
            'gender' => 'male',
            'date_of_birth' => '2014-03-03',
            'status' => 'pending',
            'course_id' => $course->id,
            'application_number' => 'APP-'.substr(uniqid(), -6),
        ]);

        $user = $this->reflectResolve('resolveOrCreateStudentUser', $application);

        $this->assertNotNull($user);
        $this->assertSame('farai.ncube@example.com', $user->email);
        $this->assertStringNotContainsString('schoolcore.test', $user->email, 'No generated placeholder emails allowed.');
        $this->assertSame('Farai Ncube', $user->username);
        $this->assertSame('student', $user->requested_role);
    }

    public function test_csv_header_row_detection_skips_leading_blank_lines(): void
    {
        $path = $this->writeCsv("First Name *,Last Name *,Gender *,Date of Birth *\n");

        // Prepend a blank row (Excel/Sheets sometimes save a leading empty row).
        file_put_contents($path, "\n".file_get_contents($path));

        $headers = StudentCsvService::readCsvHeaders($path);
        $this->assertSame(
            ['First Name *', 'Last Name *', 'Gender *', 'Date of Birth *'],
            array_slice($headers, 0, 4),
            'The header row must be detected even when the file starts with a blank line.'
        );
    }

    public function test_employee_create_via_model_creates_locked_pending_account_not_active(): void
    {
        $grade = SalaryGrade::create([
            'school_id' => $this->school->id,
            'name' => 'Educator Scale B',
        ]);

        $employee = Employee::create([
            'school_id' => $this->school->id,
            'first_name' => 'Tichaona',
            'last_name' => 'Mudimu',
            'email' => 'tichaona.mudimu@example.com',
            'phone_number' => '+263773333444',
            'national_id' => '55-111222-K-15',
            'gender' => 'male',
            'date_of_birth' => '1988-03-03',
            'date_joined' => now(),
            'department' => 'Academics',
            'designation' => 'Mathematics Teacher',
            'current_grade_id' => $grade->id,
            'status' => 'active',
            'role' => 'Mathematics Teacher',
            'marital_status' => 'single',
            'physical_address' => 'Not Provided',
            'emergency_contact_name' => 'Not Provided',
            'emergency_contact_phone' => 'Not Provided',
        ]);

        $this->assertNotNull($employee->user_id, 'The silent auto-create must link a user.');
        $user = User::withoutGlobalScopes()->find($employee->user_id);
        $this->assertNotNull($user);
        $this->assertSame(User::STATUS_PENDING, $user->account_status, 'Silent auto-create must never produce an active account.');
    }

    public function test_csv_employee_import_provisions_locked_account_with_activation_token(): void
    {
        $grade = SalaryGrade::create([
            'school_id' => $this->school->id,
            'name' => 'Educator Scale B',
        ]);

        $path = $this->writeCsv(
            "First Name,Last Name,Email,Phone Number,National ID,Gender,Date of Birth,Department,Designation,Salary Grade Name,Employment Type,Date Joined,Status\n".
            "Tapiva,Moyo,tapiva.moyo@example.com,+263774455666,66-223344-A-12,female,1992-07-19,Academics,English Teacher,Educator Scale B,Permanent,2021-02-01,active\n"
        );

        $headers = EmployeeCsvService::readCsvHeaders($path);
        $map = EmployeeCsvService::guessMapping($headers);
        $result = EmployeeCsvService::import($path, $this->school->id, $map);

        $this->assertSame(1, $result['success']);
        $this->assertSame([], array_values(array_filter($result['failures'])), 'Import must succeed cleanly.');

        $employee = Employee::query()->where('school_id', $this->school->id)->first();
        $this->assertNotNull($employee);
        $this->assertNotNull($employee->user_id, 'Imported employee must be linked to a portal account.');

        $user = User::withoutGlobalScopes()->find($employee->user_id);
        $this->assertNotNull($user);
        $this->assertSame(User::STATUS_PENDING, $user->account_status, 'Imported staff accounts must be locked until activated.');
        $this->assertNotNull($user->activation_token, 'First-time import must issue an activation email.');

        // Re-import same email: the email is already in use, so the row is
        // rejected as a duplicate. No second account may be created and the
        // existing account must keep its original activation token.
        $tokenBefore = $user->activation_token;
        $result2 = EmployeeCsvService::import($path, $this->school->id, $map);
        $this->assertSame(0, $result2['success']);
        $this->assertNotEmpty($result2['failures'], 'A duplicate email row must be reported as a validation failure.');
        $users = User::withoutGlobalScopes()->where('school_id', $this->school->id)->get();
        $this->assertSame(1, $users->count(), 'Re-import must not create a second account.');
        $this->assertSame($tokenBefore, $users->first()->activation_token, 'Activation email must only be sent on first creation.');
    }

    public function test_resend_allowed_for_pending_roster_account_with_expired_token(): void
    {
        $employee = Employee::create([
            'school_id' => $this->school->id,
            'first_name' => 'Chipo',
            'last_name' => 'Dube',
            'email' => 'chipo.dube@example.com',
            'phone_number' => '+263775566778',
            'national_id' => '77-334455-E-20',
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
            'date_joined' => now(),
            'department' => 'Academics',
            'designation' => 'Science Teacher',
            'status' => 'active',
            'role' => 'Science Teacher',
            'marital_status' => 'single',
            'physical_address' => 'Not Provided',
            'emergency_contact_name' => 'Not Provided',
            'emergency_contact_phone' => 'Not Provided',
        ]);

        $user = app(RosterAccountProvisioningService::class)->provisionEmployee($employee);
        $this->assertNotNull($user);
        $this->assertNull($user->approved_at, 'Roster accounts never carry a school-registration approval.');

        // Simulate an expired activation link.
        $user->forceFill(['activation_token_expires_at' => now()->subHour()])->save();

        $controller = new ActivationController;
        $request = \Illuminate\Http\Request::create('/activate/request', 'POST', ['email' => 'chipo.dube@example.com']);
        $response = $controller->resend($request);

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertNotNull($user->fresh()->activation_token, 'A fresh link must be issued for roster accounts with expired links.');
    }

    private function reflectResolve(string $method, Application $record): ?User
    {
        $ref = new \ReflectionMethod(ApplicationResource::class, $method);
        $ref->setAccessible(true);

        return $ref->invoke(null, $record);
    }

    private function writeCsv(string $content): string
    {
        $base = storage_path('app/private/tmp-csv-tests');
        @mkdir($base, 0777, true);
        $path = $base.'/import-'.Str::random(6).'.csv';
        file_put_contents($path, "\xEF\xBB\xBF".$content);

        return $path;
    }
}
