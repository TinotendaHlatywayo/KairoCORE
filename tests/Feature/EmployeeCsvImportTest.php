<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Security\RoleCatalogue;
use App\Services\Csv\EmployeeCsvService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Modules\Admin\Models\Department;
use Modules\HR\Models\Employee;
use Modules\HR\Models\SalaryGrade;
use Tests\TestCase;

/**
 * The staff spreadsheet import must mirror the New Employee wizard exactly
 * (same fields, same stored shapes) and must survive the workflow
 * administrators actually use: download the template, change a few cells —
 * typically the email — and re-upload it. Rows matching an existing person
 * update them in place instead of bouncing off duplicate checks.
 */
class EmployeeCsvImportTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private SalaryGrade $grade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Employee CSV School',
            'subdomain' => 'employee-csv-'.substr(uniqid(), -6),
            'status' => 'active',
        ]);

        $this->actingAsTenant($this->school);

        $this->grade = SalaryGrade::create([
            'school_id' => $this->school->id,
            'name' => 'Educator Scale B',
        ]);
    }

    // ── Template ↔ wizard parity ────────────────────────────────────────────

    public function test_template_columns_cover_every_new_employee_wizard_field(): void
    {
        $source = File::get(app_path('Filament/App/Resources/EmployeeResource.php'));
        $start = strpos($source, 'FormWizard::make([');
        $this->assertNotFalse($start, 'EmployeeResource must declare the registration wizard.');

        $end = strpos($source, '])->columnSpanFull(),', $start);
        $this->assertNotFalse($end, 'Wizard block must be delimited in EmployeeResource.');
        $wizard = substr($source, $start, $end - $start);

        preg_match_all(
            '/^( +)Forms\\\\Components\\\\(\w+)::make\(\'([^\']+)\'/m',
            $wizard,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE,
        );
        $this->assertNotEmpty($matches, 'Wizard fields must be discoverable from the form source.');

        // Repeater children (name/amount/key/value) are sub-fields, not
        // columns; Section is transparent (its children are real fields).
        $fields = [];
        $repeaterDepths = [];
        foreach ($matches as $match) {
            $depth = strlen($match[1][0]);
            $type = $match[2][0];
            $name = $match[3][0];

            while ($repeaterDepths !== [] && end($repeaterDepths) >= $depth) {
                array_pop($repeaterDepths);
            }

            if ($repeaterDepths !== []) {
                continue;
            }

            // File uploads cannot live in a spreadsheet.
            if ($type === 'FileUpload') {
                continue;
            }

            if ($type === 'Section') {
                continue;
            }

            $fields[] = $name;

            if ($type === 'Repeater') {
                $repeaterDepths[] = $depth;
            }
        }

        // The wizard stores the grade as current_grade_id (an ID select);
        // the spreadsheet carries the grade name, resolved on import.
        $fields = array_map(
            fn (string $field): string => $field === 'current_grade_id' ? 'salary_grade' : $field,
            $fields,
        );

        $columns = array_keys(EmployeeCsvService::columns());

        $missing = array_values(array_diff($fields, $columns));
        $this->assertSame([], $missing, 'The template must carry every wizard field so a file replaces hand registration.');

        $extra = array_values(array_diff($columns, $fields));
        $this->assertSame(['status'], $extra, 'Only the Status column may sit outside the wizard.');

        // Required wizard columns must be flagged required so a headerless
        // upload dies with a clear preflight message, not a 500.
        foreach (['first_name', 'last_name', 'email', 'phone_number', 'national_id', 'designation'] as $required) {
            $this->assertTrue(EmployeeCsvService::columns()[$required]['required'], $required.' must be a required template column.');
        }
        $this->assertFalse(EmployeeCsvService::columns()['department']['required'], 'Department is nullable on the wizard, so it must be optional in the file.');
    }

    // ── The headline workflows ──────────────────────────────────────────────

    public function test_pristine_template_imports_cleanly_into_a_fresh_school(): void
    {
        $result = $this->importTemplate(EmployeeCsvService::templateCsv());

        $this->assertSame(5, $result['success']);
        $this->assertSame([], $result['failures']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(5, Employee::where('school_id', $this->school->id)->count());

        $employees = Employee::where('school_id', $this->school->id)->get();
        $this->assertSame(5, $employees->whereNotNull('user_id')->count(), 'Every imported row must be linked to a portal account.');
        $this->assertSame(
            [],
            $employees->pluck('role')->reject(fn (string $role): bool => in_array($role, array_values(RoleCatalogue::employeeRoles()), true))->all(),
            'Template roles must come from the system role catalogue.',
        );
        $this->assertSame([], $employees->pluck('status')->reject(fn (string $s): bool => $s === 'active')->all());

        // The wizard-required contract end date travels with Contract rows.
        $contract = $employees->firstWhere('employment_type', 'Contract');
        $this->assertNotNull($contract);
        $this->assertSame('2026-12-31', $contract->contract_end_date->format('Y-m-d'));

        // A department named in the file but not yet in the system is created
        // (the wizard's default create-or-skip choice) so it resolves in forms.
        $this->assertTrue(
            Department::where('school_id', $this->school->id)->where('name', 'Academics')->exists(),
            'Unknown department values are created by default so the import never writes orphan values.',
        );

        $this->assertSame(
            $this->grade->id,
            $employees->first()->current_grade_id,
            'The template\'s salary grade must resolve to the tenant\'s real grade.',
        );
    }

    public function test_changing_only_emails_and_reuploading_updates_in_place(): void
    {
        $first = $this->importTemplate(EmployeeCsvService::templateCsv());
        $this->assertSame(5, $first['success']);

        $tokensBefore = User::where('school_id', $this->school->id)->orderBy('id')
            ->pluck('activation_token', 'id')->all();

        // The exact workflow from the field: fresh template, only the email
        // column touched, uploaded again over last week's import.
        $changed = str_replace('@schoolcore.test', '@updated.test', EmployeeCsvService::templateCsv());
        $second = $this->importTemplate($changed);

        $this->assertSame([], $second['failures'], 'Re-uploading a template you already imported must never bounce on duplicates.');
        $this->assertSame(5, $second['success']);
        $this->assertSame(5, $second['updated'], 'Every matched row counts as an update.');

        $this->assertSame(5, Employee::where('school_id', $this->school->id)->count(), 'Updates must not create second employee records.');
        $this->assertSame(5, User::where('school_id', $this->school->id)->count(), 'Updates must not create second accounts.');

        $this->assertSame(
            [],
            Employee::where('school_id', $this->school->id)->pluck('email')
                ->reject(fn (string $email): bool => str_ends_with($email, '@updated.test'))->all(),
            'The changed emails must land on the matched employees.',
        );
        $this->assertSame(
            [],
            User::where('school_id', $this->school->id)->pluck('email')
                ->reject(fn (string $email): bool => str_ends_with($email, '@updated.test'))->all(),
            'Linked portal accounts must follow the email change so login keeps working.',
        );

        $this->assertSame(
            $tokensBefore,
            User::where('school_id', $this->school->id)->orderBy('id')->pluck('activation_token', 'id')->all(),
            'A re-upload never re-issues activation emails.',
        );

        $sample = Employee::where('school_id', $this->school->id)->orderBy('id')->first();
        $this->assertSame('Matipa', $sample->first_name, 'Non-email fields stay untouched by an email-only edit.');
        $this->assertSame('Biology Teacher', $sample->designation);
    }

    public function test_old_thirteen_column_files_still_import_with_documented_defaults(): void
    {
        $csv = "First Name,Last Name,Email,Phone Number,National ID,Gender,Date of Birth,Department,Designation,Salary Grade Name,Employment Type,Date Joined,Status\n".
            "Rutendo,Chikafu,rutendo.chikafu@example.com,+263771000111,66-112233-B-09,male,1988-04-04,Sciences,Maths Teacher,Educator Scale B,Permanent,2020-03-03,active\n";

        $result = $this->importTemplate($csv);

        $this->assertSame(1, $result['success']);
        $this->assertSame([], $result['failures']);

        $employee = Employee::where('school_id', $this->school->id)->firstOrFail();
        $this->assertSame('single', $employee->marital_status, 'Old files without a Marital Status column fall back to single.');
        $this->assertSame('Teaching Staff', $employee->role, 'Old files without a Role column fall back to the teaching default.');
        $this->assertSame('Not Provided', $employee->physical_address);
        $this->assertSame('Not Provided', $employee->emergency_contact_name);
        $this->assertNull($employee->contract_end_date);
        $this->assertNull($employee->spouse_details);
        $this->assertNull($employee->custom_fields);
        $this->assertSame($this->grade->id, $employee->current_grade_id);
        $this->assertSame('Sciences', $employee->department);
    }

    // ── Validation ──────────────────────────────────────────────────────────

    public function test_blank_required_cells_and_bad_values_yield_friendly_row_errors(): void
    {
        $base = $this->blankRow([
            'first_name' => 'Tendai',
            'last_name' => 'Zhuwao',
            'email' => 'tendai.zhuwao@example.com',
            'phone_number' => '+263772000222',
            'national_id' => '71-445566-C-31',
            'designation' => 'History Teacher',
        ]);

        $rows = [];
        $rows[] = array_merge($base, ['first_name' => '']);
        $rows[] = array_merge($base, ['email' => 'not-an-email']);
        $rows[] = array_merge($base, ['employment_type' => 'Contract', 'contract_end_date' => '']);
        $rows[] = array_merge($base, ['role' => 'Space Wizard']);
        $rows[] = array_merge($base, ['gender' => 'alien']);
        $rows[] = array_merge($base, ['date_of_birth' => 'not-a-date']);
        $rows[] = array_merge($base, ['phone_number' => '']);
        $rows[] = array_merge($base, ['national_id' => '']);
        $rows[] = array_merge($base, ['designation' => '']);

        $result = $this->importRows($rows);

        $this->assertSame(0, $result['success']);
        $this->assertCount(9, $result['failures']);

        $messages = implode(' | ', collect($result['failures'])->pluck('errors')->flatten()->all());

        foreach ([
            'First Name is required',
            'is not a valid email address',
            'Contract End Date is required when Employment Type is Contract',
            'is not one of the system roles',
            'Gender must be one of: male, female, other',
            'is not a valid date',
            'Phone Number is required',
            'National ID is required',
            'Designation is required',
        ] as $expected) {
            $this->assertStringContainsString($expected, $messages);
        }

        $this->assertSame(0, Employee::where('school_id', $this->school->id)->count());
    }

    public function test_an_email_owned_by_a_different_account_is_still_rejected(): void
    {
        Employee::create([
            'school_id' => $this->school->id,
            'first_name' => 'Alice',
            'last_name' => 'Origin',
            'email' => 'alice.origin@example.com',
            'phone_number' => '+263773000333',
            'national_id' => '80-111222-D-44',
            'gender' => 'female',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'physical_address' => 'Not Provided',
            'emergency_contact_name' => 'Not Provided',
            'emergency_contact_phone' => 'Not Provided',
            'department' => null,
            'designation' => 'Registrar',
            'role' => 'HR',
            'employment_type' => 'Permanent',
            'date_joined' => '2022-01-01',
            'status' => 'active',
        ]);

        // National ID of a person that does not exist yet, but wearing an
        // email that belongs to Alice — a mis-keyed row, not an update.
        $row = $this->blankRow([
            'first_name' => 'Bob',
            'last_name' => 'Imposter',
            'email' => 'alice.origin@example.com',
            'phone_number' => '+263774000444',
            'national_id' => '81-333444-E-55',
            'designation' => 'Janitor',
        ]);

        $result = $this->importRows([$row]);

        $this->assertSame(0, $result['success']);
        $this->assertStringContainsString(
            'already used by another employee',
            implode(' ', $result['failures'][0]['errors']),
        );
        $this->assertSame(1, Employee::where('school_id', $this->school->id)->count());
    }

    // ── Department create-or-skip ───────────────────────────────────────────

    public function test_department_create_or_skip_choices_are_honoured(): void
    {
        $row = $this->blankRow([
            'first_name' => 'Nokuthula',
            'last_name' => 'Moyo',
            'email' => 'nokuthula.moyo@example.com',
            'phone_number' => '+263775000555',
            'national_id' => '82-555666-F-66',
            'designation' => 'Robotics Coach',
            'department' => 'Quantum Studies',
        ]);

        $skipped = $this->importRows([$row], ['missingRefs' => ['department' => [0 => 'skip']]]);
        $this->assertSame(1, $skipped['success']);
        $this->assertNull(Employee::where('school_id', $this->school->id)->firstOrFail()->department, 'Skip leaves the field blank.');
        $this->assertFalse(Department::where('school_id', $this->school->id)->where('name', 'Quantum Studies')->exists());

        $created = $this->importRows([
            array_merge($row, ['first_name' => 'Bhekizizwe', 'email' => 'bheki.moyo@example.com', 'national_id' => '83-666777-G-77']),
        ]);
        $this->assertSame(1, $created['success']);
        $this->assertSame('Quantum Studies', Employee::where('school_id', $this->school->id)->orderByDesc('id')->first()->department);

        $department = Department::where('school_id', $this->school->id)->where('name', 'Quantum Studies')->firstOrFail();
        $this->assertNotEmpty($department->code, 'Created departments need a code (the column is NOT NULL + unique).');
    }

    // ── Structured columns ↔ wizard storage shapes ─────────────────────────

    public function test_structured_columns_parse_into_the_shapes_the_wizard_stores(): void
    {
        $row = $this->blankRow([
            'first_name' => 'Priya',
            'last_name' => 'Naidoo',
            'email' => 'priya.naidoo@example.com',
            'phone_number' => '+263776000666',
            'national_id' => '84-777888-H-88',
            'designation' => 'Counsellor',
            'individual_allowances' => 'Teaching Allowance: 150; Transport: 80',
            'spouse_details' => 'Spouse: Rudo Naidoo; Occupation: Nurse',
            'dependents' => 'Name: Tariro Naidoo; Age: 9',
            'next_of_kin' => 'Name: Kuda Naidoo; Phone: +263773001002',
            'custom_fields' => 'Office: B12; Staff Locker: L-104',
            'medical_conditions' => 'None',
            'allergies' => 'Peanuts',
            'emergency_medical_notes' => 'Call next of kin first',
        ]);

        $result = $this->importRows([$row]);
        $this->assertSame(1, $result['success'], implode('|', $result['failures'][0]['errors'] ?? []));

        $employee = Employee::where('school_id', $this->school->id)->firstOrFail();

        $this->assertSame([
            ['name' => 'Teaching Allowance', 'amount' => '150'],
            ['name' => 'Transport', 'amount' => '80'],
        ], $employee->individual_allowances, 'Allowances match the wizard repeater rows.');
        $this->assertSame([
            ['key' => 'Office', 'value' => 'B12'],
            ['key' => 'Staff Locker', 'value' => 'L-104'],
        ], $employee->custom_fields, 'Custom fields match the wizard repeater rows.');
        // MySQL's JSON column may reorder object keys, so the key/value maps
        // are compared order-insensitively (the wizard stores them as maps).
        $this->assertEquals(['Spouse' => 'Rudo Naidoo', 'Occupation' => 'Nurse'], $employee->spouse_details);
        $this->assertEquals(['Name' => 'Tariro Naidoo', 'Age' => '9'], $employee->dependents);
        $this->assertEquals(['Name' => 'Kuda Naidoo', 'Phone' => '+263773001002'], $employee->next_of_kin);
        $this->assertSame('Peanuts', $employee->allergies);
        $this->assertSame('Call next of kin first', $employee->emergency_medical_notes);
    }

    // ── Export parity ───────────────────────────────────────────────────────

    public function test_export_carries_the_new_scalar_columns(): void
    {
        Employee::create([
            'school_id' => $this->school->id,
            'first_name' => 'Export',
            'last_name' => 'Subject',
            'email' => 'export.subject@example.com',
            'phone_number' => '+263777000777',
            'national_id' => '85-888999-J-99',
            'gender' => 'male',
            'date_of_birth' => '1985-05-05',
            'marital_status' => 'divorced',
            'physical_address' => '12 Mt Pleasant, Harare',
            'emergency_contact_name' => 'Kin Subject',
            'emergency_contact_phone' => '+263773001999',
            'department' => 'Academics',
            'designation' => 'Physics Teacher',
            'role' => 'HR',
            'employment_type' => 'Contract',
            'contract_end_date' => '2026-05-05',
            'date_joined' => '2021-09-09',
            'status' => 'on_leave',
            'current_grade_id' => $this->grade->id,
        ]);

        $headers = EmployeeCsvService::exportHeaders();
        $rows = collect(EmployeeCsvService::exportRows($this->school->id));

        $this->assertCount(1, $rows);
        $this->assertCount(count($headers), $rows[0], 'Every export row must align with the header row.');

        foreach (['Marital Status', 'Role', 'Physical Address', 'Emergency Contact Name', 'Emergency Contact Phone', 'Contract End Date'] as $header) {
            $this->assertContains($header, $headers, 'Export must carry '.$header.' so an export/import round trip keeps the data.');
        }

        $cell = fn (string $header) => $rows[0][array_search($header, $headers, true)];

        $this->assertSame('divorced', $cell('Marital Status'));
        $this->assertSame('HR', $cell('Role'));
        $this->assertSame('12 Mt Pleasant, Harare', $cell('Physical Address'));
        $this->assertSame('Kin Subject', $cell('Emergency Contact Name'));
        $this->assertSame('2026-05-05', $cell('Contract End Date'));
        $this->assertSame('on_leave', $cell('Status'));
        $this->assertSame('Educator Scale B', $cell('Salary Grade'));
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function importTemplate(string $csv, array $options = []): array
    {
        $path = tempnam(sys_get_temp_dir(), 'employee-template');
        file_put_contents($path, "\xEF\xBB\xBF".$csv);

        return EmployeeCsvService::import(
            $path,
            $this->school->id,
            EmployeeCsvService::guessMapping(EmployeeCsvService::readCsvHeaders($path)),
            null,
            $options,
        );
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     */
    private function importRows(array $rows, array $options = []): array
    {
        $path = tempnam(sys_get_temp_dir(), 'employee-rows');
        $handle = fopen($path, 'w');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, EmployeeCsvService::templateHeaders(), escape: '\\');

        foreach ($rows as $row) {
            fputcsv($handle, array_values($row), escape: '\\');
        }

        fclose($handle);

        return EmployeeCsvService::import(
            $path,
            $this->school->id,
            EmployeeCsvService::guessMapping(EmployeeCsvService::readCsvHeaders($path)),
            null,
            $options,
        );
    }

    /**
     * A full-template-width row with the identity columns filled in.
     *
     * @return array<string, string>
     */
    private function blankRow(array $overrides = []): array
    {
        $row = array_fill_keys(array_keys(EmployeeCsvService::columns()), '');

        return array_merge($row, [
            'gender' => 'female',
            'status' => 'active',
            'department' => 'Academics',
        ], $overrides);
    }
}
