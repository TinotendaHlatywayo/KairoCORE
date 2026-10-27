<?php

namespace App\Services\Csv;

use App\Models\User;
use App\Security\RoleCatalogue;
use App\Services\RosterAccountProvisioningService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admin\Models\Department;
use Modules\HR\Models\Employee;
use Modules\HR\Models\SalaryGrade;

/**
 * Spreadsheet import/export for the staff roster.
 *
 * The column set mirrors the New Employee wizard exactly (every scalar field
 * of the six wizard steps, from Personal Information through Custom Fields —
 * only the four file-upload fields cannot live in a spreadsheet), so a file
 * created from this template carries everything registering an employee by
 * hand would capture.
 *
 * Rows are matched to existing staff by National ID: a row whose national ID
 * already exists in the school UPDATES that employee instead of failing.
 * That is what makes "change the email and re-upload" work — re-uploading a
 * template you already imported once never trips duplicate errors, and an
 * unchanged re-upload is a harmless no-op instead of a wall of rejections.
 */
class EmployeeCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'first_name' => [
                'label' => __('First Name'),
                'required' => true,
                'guesses' => ['First Name', 'Firstname', 'Given Name'],
                'example' => 'Matipa',
            ],
            'last_name' => [
                'label' => __('Last Name'),
                'required' => true,
                'guesses' => ['Last Name', 'Lastname', 'Surname'],
                'example' => 'Maphosa',
            ],
            'email' => [
                'label' => __('Email'),
                'required' => true,
                'guesses' => ['Email', 'Email Address'],
                'example' => 'matipa.maphosa@schoolcore.test',
            ],
            'phone_number' => [
                'label' => __('Phone Number'),
                'required' => true,
                'guesses' => ['Phone Number', 'Phone', 'Mobile', 'Cell'],
                'example' => '+263786366555',
            ],
            'national_id' => [
                'label' => __('National ID'),
                'required' => true,
                'guesses' => ['National ID', 'National Id', 'ID Number', 'Passport'],
                'example' => '42-987654-Y-18',
            ],
            'gender' => [
                'label' => __('Gender'),
                'required' => false,
                'guesses' => ['Gender', 'Sex'],
                'example' => 'female',
                'default' => 'female',
                'in' => ['male', 'female', 'other'],
            ],
            'date_of_birth' => [
                'label' => __('Date of Birth'),
                'required' => false,
                'guesses' => ['Date of Birth', 'DOB', 'Birth Date'],
                'example' => '1990-06-15',
                'date' => true,
            ],
            'marital_status' => [
                'label' => __('Marital Status'),
                'required' => false,
                'guesses' => ['Marital Status', 'Marital'],
                'example' => 'single',
                'default' => 'single',
                'in' => ['single', 'married', 'divorced'],
            ],
            'physical_address' => [
                'label' => __('Physical Address'),
                'required' => false,
                'guesses' => ['Physical Address', 'Address', 'Home Address'],
                'example' => '1646 Mabvazuva Street, Harare',
            ],
            'emergency_contact_name' => [
                'label' => __('Emergency Contact Name'),
                'required' => false,
                'guesses' => ['Emergency Contact Name', 'Emergency Contact', 'Next of Kin Name'],
                'example' => 'Marwi Kandepa',
            ],
            'emergency_contact_phone' => [
                'label' => __('Emergency Contact Phone'),
                'required' => false,
                'guesses' => ['Emergency Contact Phone', 'Emergency Phone', 'Kin Phone'],
                'example' => '+263773001001',
            ],
            'department' => [
                'label' => __('Department'),
                'required' => false,
                'guesses' => ['Department', 'Dept'],
                'example' => 'Academics',
            ],
            'designation' => [
                'label' => __('Designation'),
                'required' => true,
                'guesses' => ['Designation', 'Job Title', 'Position'],
                'example' => 'Biology Teacher',
            ],
            'role' => [
                'label' => __('Role'),
                'required' => false,
                'guesses' => ['Role', 'Job Role', 'System Role'],
                'example' => 'Teaching Staff',
                'default' => 'Teaching Staff',
            ],
            'employment_type' => [
                'label' => __('Employment Type'),
                'required' => false,
                'guesses' => ['Employment Type', 'Contract Type'],
                'example' => 'Permanent',
                'default' => 'Permanent',
                'in' => ['Permanent', 'Contract', 'Part-time', 'Volunteer'],
            ],
            'contract_end_date' => [
                'label' => __('Contract End Date'),
                'required' => false,
                'guesses' => ['Contract End Date', 'Contract Expiry', 'Contract Expires'],
                'example' => '2026-12-31',
                'date' => true,
            ],
            'date_joined' => [
                'label' => __('Date Joined'),
                'required' => false,
                'guesses' => ['Date Joined', 'Start Date', 'Hire Date'],
                'example' => '2021-05-01',
                'date' => true,
            ],
            'salary_grade' => [
                'label' => __('Salary Grade Name'),
                'required' => false,
                'guesses' => ['Salary Grade Name', 'Salary Grade', 'Grade'],
                'example' => 'Educator Scale B',
            ],
            'individual_allowances' => [
                'label' => __('Individual Allowances'),
                'required' => false,
                'guesses' => ['Individual Allowances', 'Allowances', 'Specific Allowances'],
                'example' => 'Teaching Allowance: 150; Transport: 80',
            ],
            'spouse_details' => [
                'label' => __('Spouse Details'),
                'required' => false,
                'guesses' => ['Spouse Details', 'Spouse'],
                'example' => 'Spouse: Rudo Maphosa; Occupation: Nurse',
            ],
            'dependents' => [
                'label' => __('Dependents'),
                'required' => false,
                'guesses' => ['Dependents', 'Children'],
                'example' => 'Name: Tapiwa Maphosa; Age: 12',
            ],
            'next_of_kin' => [
                'label' => __('Next of Kin'),
                'required' => false,
                'guesses' => ['Next of Kin', 'Next Of Kin Details'],
                'example' => 'Name: Kuda Maphosa; Phone: +263773001002',
            ],
            'medical_conditions' => [
                'label' => __('Medical Conditions'),
                'required' => false,
                'guesses' => ['Medical Conditions', 'Medical Conditions Log'],
                'example' => 'None',
            ],
            'allergies' => [
                'label' => __('Allergies'),
                'required' => false,
                'guesses' => ['Allergies', 'Allergy'],
                'example' => 'None',
            ],
            'emergency_medical_notes' => [
                'label' => __('Emergency Medical Notes'),
                'required' => false,
                'guesses' => ['Emergency Medical Notes', 'Medical Notes', 'Emergency Notes'],
                'example' => 'None',
            ],
            'custom_fields' => [
                'label' => __('Custom Fields'),
                'required' => false,
                'guesses' => ['Custom Fields', 'Extra Fields'],
                'example' => 'Office: B12; Staff Locker: L-104',
            ],
            'status' => [
                'label' => __('Status'),
                'required' => false,
                'guesses' => ['Status'],
                'example' => 'active',
                'default' => 'active',
                'in' => ['active', 'suspended', 'on_leave'],
            ],
        ];
    }

    public static function referenceFields(): array
    {
        return [
            'department' => [
                'label' => __('Department'),
                'model' => Department::class,
                'column' => 'name',
            ],
        ];
    }

    /**
     * Five example staff, one row each, written under the template header.
     *
     * Every row is a distinct person (name, email, national ID, phone) with
     * values that are valid straight out of the box: the salary grade is a
     * real grade from the tenant school (or blank when the school has none),
     * roles come from the system's own role catalogue, and the Contract row
     * carries the contract end date the wizard requires for contract staff.
     *
     * @return array<int, array<string, string>>
     */
    protected static function templateRows(): array
    {
        $grade = static::exampleSalaryGrade();

        $people = [
            ['Matipa', 'Maphosa', 'female', 'Teaching Staff', 'Permanent', 'single', '1990-06-15', '2021-05-01', '+263786366555', '42-987654-Y-18'],
            ['Rutendo', 'Ndlovu', 'male', 'Accounts / Finance', 'Contract', 'married', '1985-03-22', '2019-01-14', '+263772445811', '42-987654-Y-19'],
            ['Tariro', 'Sithole', 'female', 'HR', 'Permanent', 'married', '1992-11-02', '2022-08-01', '+263719304477', '42-987654-Y-20'],
            ['Farai', 'Moyo', 'male', 'Librarian', 'Part-time', 'divorced', '1988-07-30', '2020-04-13', '+263771220954', '42-987654-Y-21'],
            ['Chipo', 'Dube', 'female', 'Supporting Staff', 'Volunteer', 'single', '1995-01-19', '2023-02-06', '+263782991306', '42-987654-Y-22'],
        ];

        $rows = [];

        foreach ($people as [$first, $last, $gender, $role, $employment, $marital, $dob, $joined, $phone, $nationalId]) {
            $row = array_map(fn (array $column): string => (string) ($column['example'] ?? ''), static::columns());

            $row['first_name'] = $first;
            $row['last_name'] = $last;
            $row['email'] = strtolower($first.'.'.$last.'@schoolcore.test');
            $row['phone_number'] = $phone;
            $row['national_id'] = $nationalId;
            $row['gender'] = $gender;
            $row['date_of_birth'] = $dob;
            $row['marital_status'] = $marital;
            $row['role'] = $role;
            $row['employment_type'] = $employment;
            $row['contract_end_date'] = $employment === 'Contract' ? '2026-12-31' : '';
            $row['date_joined'] = $joined;
            $row['salary_grade'] = $grade;
            $row['status'] = 'active';

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * A salary grade name that really exists in the tenant school, so the
     * example row is never rejected with "does not match any configured grade".
     * Blank when the school has no grades yet — the importer then falls back
     * to the first configured grade, or imports without one.
     */
    protected static function exampleSalaryGrade(): string
    {
        $schoolId = current_tenant()?->id;

        if (! $schoolId) {
            return '';
        }

        return (string) SalaryGrade::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->orderBy('id')
            ->value('name');
    }

    public static function exportHeaders(): array
    {
        return [
            'Employee Number', 'First Name', 'Last Name', 'Email', 'Phone Number',
            'National ID', 'Gender', 'Date of Birth', 'Marital Status', 'Department',
            'Designation', 'Role', 'Physical Address', 'Emergency Contact Name',
            'Emergency Contact Phone', 'Salary Grade', 'Employment Type',
            'Contract End Date', 'Date Joined', 'Status',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = Employee::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with('currentGrade')
            ->orderBy('id');

        $lastId = 0;

        do {
            $employees = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($employees->isEmpty()) {
                break;
            }

            foreach ($employees as $employee) {
                yield [
                    $employee->employee_number,
                    $employee->first_name,
                    $employee->last_name,
                    $employee->email,
                    $employee->phone_number,
                    $employee->national_id,
                    $employee->gender,
                    optional($employee->date_of_birth)->format('Y-m-d'),
                    $employee->marital_status,
                    $employee->department,
                    $employee->designation,
                    $employee->role,
                    $employee->physical_address,
                    $employee->emergency_contact_name,
                    $employee->emergency_contact_phone,
                    $employee->currentGrade?->name,
                    $employee->employment_type,
                    optional($employee->contract_end_date)->format('Y-m-d'),
                    optional($employee->date_joined)->format('Y-m-d'),
                    $employee->status,
                ];
            }

            $lastId = $employees->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null, array $options = []): array
    {
        $csvHeaders = static::readCsvHeaders($filePath);

        if (empty($csvHeaders)) {
            throw new \RuntimeException('The uploaded file has no readable header row. Download the template and use its exact column names.');
        }

        $headerIndex = [];
        foreach ($csvHeaders as $i => $header) {
            $headerIndex[strtolower($header)] = $i;
        }

        $mappedIndexes = [];
        foreach ($columnMap as $key => $header) {
            if (blank($header)) {
                $mappedIndexes[$key] = null;

                continue;
            }
            $mappedIndexes[$key] = $headerIndex[strtolower($header)] ?? null;
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Could not open the uploaded file.');
        }

        fgets($handle); // skip header row

        $total = 0;
        while (fgetcsv($handle, 0, ',', escape: '\\') !== false) {
            $total++;
        }

        rewind($handle);
        fgets($handle); // skip header row again

        // Lookups loaded once for the whole file, all tenant-scoped.
        $grades = SalaryGrade::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn (SalaryGrade $g): string => strtolower(trim($g->name)));

        $existingEmails = User::withoutTenantScope()->where('school_id', $schoolId)->pluck('email')
            ->map(fn ($v): string => strtolower(trim((string) $v)))->flip()
            ->merge(
                Employee::withoutTenantScope()->where('school_id', $schoolId)->pluck('email')
                    ->map(fn ($v): string => strtolower(trim((string) $v)))->flip()
            );

        // National ID index drives the upsert: every employee the school
        // already has, keyed by national ID (lower-cased for safe matching).
        $existingByNationalId = [];
        foreach (Employee::withoutTenantScope()->where('school_id', $schoolId)->get(['id', 'national_id', 'email', 'user_id']) as $existing) {
            if (filled($existing->national_id)) {
                $existingByNationalId[strtolower(trim($existing->national_id))] = $existing;
            }
        }

        $userEmailsById = User::withoutTenantScope()->where('school_id', $schoolId)->pluck('email', 'id');

        $departments = Department::withoutTenantScope()->where('school_id', $schoolId)->get(['id', 'name']);
        $departmentIndex = $departments
            ->mapWithKeys(fn (Department $d): array => [strtolower(trim($d->name)) => $d->id]);
        $existingDepartmentCodes = Department::withoutTenantScope()->where('school_id', $schoolId)->pluck('code')
            ->map(fn ($v): string => (string) $v)->flip();

        // Honour the wizard's "create or skip" answers for departments that
        // are in the file but not in the system yet (default: create).
        $skipDepartments = [];
        $unknownDepartments = static::unknownReferenceValues($filePath, $schoolId, $columnMap, 'department');
        foreach ($unknownDepartments as $i => $value) {
            $choice = $options['missingRefs']['department'][$i] ?? 'create';

            if ($choice === 'skip') {
                $skipDepartments[strtolower($value)] = true;
            }
        }

        $lookups = [
            'grades' => $grades,
            'existingEmails' => $existingEmails,
            'existingByNationalId' => $existingByNationalId,
            'userEmailsById' => $userEmailsById,
            'departmentIndex' => $departmentIndex,
            'existingDepartmentCodes' => $existingDepartmentCodes,
            'skipDepartments' => $skipDepartments,
        ];

        $success = 0;
        $updated = 0;
        $failures = [];
        $processed = 0;
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, ',', escape: '\\')) !== false) {
            $rowNumber++;
            $processed++;
            $row = array_map('trim', $row);

            $data = array_fill_keys(array_keys(static::columns()), '');

            foreach ($mappedIndexes as $key => $index) {
                $data[$key] = ($index !== null && isset($row[$index])) ? $row[$index] : '';
            }

            if (implode('', $data) === '') {
                $onProgress !== null && $onProgress($processed, $total, false, []);

                continue;
            }

            $errors = static::validateAndNormalize($data, $lookups);

            if (! empty($errors)) {
                $failures[] = ['row' => $rowNumber, 'errors' => $errors, 'data' => $data];
                $onProgress !== null && $onProgress($processed, $total, true, $errors);

                continue;
            }

            try {
                $matched = $data['_matched'] ?? null;

                DB::transaction(function () use (&$lookups, $matched, $data, $schoolId) {
                    if ($matched !== null) {
                        $employee = Employee::withoutTenantScope()->findOrFail($matched->id);
                        $employee->update(static::employeeAttributes($data, $schoolId));
                    } else {
                        $employee = Employee::create(static::employeeAttributes($data, $schoolId));
                    }

                    // Keep the Department record itself in place so the value
                    // resolves in the form's department list (create-or-skip
                    // was already applied during validation).
                    if (filled($data['department'])) {
                        static::ensureDepartment($data['department'], $schoolId, $lookups);
                    }

                    // Keep the portal login in step with a changed email.
                    $user = filled($employee->user_id)
                        ? User::withoutTenantScope()->find($employee->user_id)
                        : null;

                    if ($user && strtolower(trim($user->email)) !== $data['email']) {
                        $user->email = $data['email'];
                        $user->save();
                        $lookups['userEmailsById'][$user->id] = $data['email'];
                    }

                    if (filled($data['email'])) {
                        $lookups['existingEmails'][strtolower(trim($data['email']))] = true;
                    }

                    if (filled($data['national_id'])) {
                        $lookups['existingByNationalId'][strtolower(trim($data['national_id']))] = (object) [
                            'id' => $employee->id,
                            'email' => $employee->email,
                            'user_id' => $employee->user_id,
                        ];
                    }

                    // Same treatment as staff added via the Create page: a
                    // locked PENDING portal account plus an activation email
                    // when the account is first provisioned.
                    app(RosterAccountProvisioningService::class)->provisionEmployee($employee);
                });

                $success++;
                $matched !== null && $updated++;
                $onProgress !== null && $onProgress($processed, $total, false, []);
            } catch (\Throwable $e) {
                $failures[] = [
                    'row' => $rowNumber,
                    'errors' => ['Unexpected database error while saving: '.$e->getMessage()],
                    'data' => $data,
                ];
                $onProgress !== null && $onProgress($processed, $total, true, ['Unexpected database error']);
            }
        }

        fclose($handle);

        return ['success' => $success, 'total' => $total, 'failures' => $failures, 'updated' => $updated];
    }

    /**
     * Column values shaped for Employee::create() / Employee::update().
     *
     * @return array<string, mixed>
     */
    protected static function employeeAttributes(array $data, int $schoolId): array
    {
        return [
            'school_id' => $schoolId,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'national_id' => $data['national_id'],
            'gender' => $data['gender'] !== '' ? $data['gender'] : 'female',
            'date_of_birth' => $data['date_of_birth'] !== '' ? $data['date_of_birth'] : '1990-01-01',
            'marital_status' => $data['marital_status'] !== '' ? $data['marital_status'] : 'single',
            'physical_address' => $data['physical_address'] !== '' ? $data['physical_address'] : 'Not Provided',
            'emergency_contact_name' => $data['emergency_contact_name'] !== '' ? $data['emergency_contact_name'] : 'Not Provided',
            'emergency_contact_phone' => $data['emergency_contact_phone'] !== '' ? $data['emergency_contact_phone'] : 'Not Provided',
            'department' => $data['department'] !== '' ? $data['department'] : null,
            'designation' => $data['designation'],
            'role' => $data['role'] !== '' ? $data['role'] : 'Teaching Staff',
            'employment_type' => $data['employment_type'] !== '' ? $data['employment_type'] : 'Permanent',
            'contract_end_date' => $data['contract_end_date'] !== '' ? $data['contract_end_date'] : null,
            'date_joined' => $data['date_joined'] !== '' ? $data['date_joined'] : now()->toDateString(),
            'current_grade_id' => $data['_grade']?->id,
            'individual_allowances' => $data['_individual_allowances'] ?? null,
            'spouse_details' => $data['_spouse_details'] ?? null,
            'dependents' => $data['_dependents'] ?? null,
            'next_of_kin' => $data['_next_of_kin'] ?? null,
            'medical_conditions' => $data['medical_conditions'] !== '' ? $data['medical_conditions'] : null,
            'allergies' => $data['allergies'] !== '' ? $data['allergies'] : null,
            'emergency_medical_notes' => $data['emergency_medical_notes'] !== '' ? $data['emergency_medical_notes'] : null,
            'custom_fields' => $data['_custom_fields'] ?? null,
            'status' => $data['status'] !== '' ? $data['status'] : 'active',
        ];
    }

    /**
     * @param  array<string, mixed>  $lookups
     */
    protected static function validateAndNormalize(array &$data, array &$lookups): array
    {
        $errors = [];

        foreach (['first_name', 'last_name', 'email', 'phone_number', 'national_id', 'designation'] as $required) {
            $data[$required] = trim($data[$required] ?? '');

            if ($data[$required] === '') {
                $errors[] = static::columns()[$required]['label'].' is required (column empty or not mapped).';
            }
        }

        if ($errors !== []) {
            return $errors;
        }

        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Email ['.$data['email'].'] is not a valid email address.';
        }

        $email = strtolower($data['email']);
        $nationalId = strtolower(trim($data['national_id']));

        // Upsert: a national ID the school already holds means "this is the
        // same person" — the row updates that employee instead of being
        // rejected. The email only has to be free of OTHER accounts; the
        // matched employee's own email (old or new) is always allowed.
        $matched = $lookups['existingByNationalId'][$nationalId] ?? null;
        $data['_matched'] = $matched;

        if (isset($lookups['existingEmails'][$email])) {
            $ownsEmail = $matched !== null && (
                strtolower(trim((string) $matched->email)) === $email
                || ($matched->user_id !== null
                    && strtolower(trim((string) ($lookups['userEmailsById'][$matched->user_id] ?? ''))) === $email)
            );

            if (! $ownsEmail) {
                $errors[] = 'Email ['.$data['email'].'] is already used by another employee or user account.';
            }
        }

        $data['gender'] = strtolower(trim($data['gender'] ?? ''));
        if ($data['gender'] === '') {
            $data['gender'] = 'female';
        } elseif (! in_array($data['gender'], ['male', 'female', 'other'], true)) {
            $errors[] = 'Gender must be one of: male, female, other.';
        }

        $data['marital_status'] = strtolower(trim($data['marital_status'] ?? ''));
        if ($data['marital_status'] === '') {
            $data['marital_status'] = 'single';
        } elseif (! in_array($data['marital_status'], ['single', 'married', 'divorced'], true)) {
            $errors[] = 'Marital Status must be one of: single, married, divorced.';
        }

        $data['status'] = strtolower(trim($data['status'] ?? ''));
        if ($data['status'] === '') {
            $data['status'] = 'active';
        } elseif (! in_array($data['status'], ['active', 'suspended', 'on_leave'], true)) {
            $errors[] = 'Status must be one of: active, suspended, on_leave.';
        }

        $data['employment_type'] = trim($data['employment_type'] ?? '');
        if ($data['employment_type'] === '') {
            $data['employment_type'] = 'Permanent';
        } elseif (! in_array($data['employment_type'], ['Permanent', 'Contract', 'Part-time', 'Volunteer'], true)) {
            $errors[] = 'Employment Type must be one of: Permanent, Contract, Part-time, Volunteer.';
        }

        // Roles come from the same catalogue the New Employee wizard offers,
        // matched case-insensitively so "teaching staff" still resolves.
        $data['role'] = trim($data['role'] ?? '');
        if ($data['role'] === '') {
            $data['role'] = 'Teaching Staff';
        } else {
            $catalogue = RoleCatalogue::employeeRoles();
            $roleNames = array_values($catalogue);
            $resolved = collect($roleNames)
                ->first(fn (string $role): bool => strcasecmp($role, $data['role']) === 0);

            if ($resolved === null) {
                $errors[] = 'Role ['.$data['role'].'] is not one of the system roles: '.implode(', ', $roleNames).'.';
            } else {
                $data['role'] = $resolved;
            }
        }

        foreach (['date_of_birth', 'date_joined'] as $dateField) {
            $raw = trim($data[$dateField] ?? '');

            if ($raw === '') {
                continue;
            }

            try {
                $data[$dateField] = Carbon::parse($raw)->toDateString();
            } catch (\Throwable) {
                $errors[] = static::columns()[$dateField]['label'].' ['.$raw.'] is not a valid date. Use YYYY-MM-DD.';
            }
        }

        $data['contract_end_date'] = trim($data['contract_end_date'] ?? '');
        if ($data['contract_end_date'] !== '') {
            try {
                $data['contract_end_date'] = Carbon::parse($data['contract_end_date'])->toDateString();
            } catch (\Throwable) {
                $errors[] = 'Contract End Date ['.$data['contract_end_date'].'] is not a valid date. Use YYYY-MM-DD.';
            }
        } elseif ($data['employment_type'] === 'Contract') {
            // The wizard forces a contract end date for contract staff, so
            // the spreadsheet must carry one too.
            $errors[] = 'Contract End Date is required when Employment Type is Contract.';
        }

        $data['department'] = trim($data['department'] ?? '');
        if ($data['department'] !== '' && isset($lookups['skipDepartments'][strtolower($data['department'])])) {
            $data['department'] = '';
        }

        $gradeName = trim($data['salary_grade'] ?? '');

        if ($gradeName !== '') {
            $grade = $lookups['grades'][strtolower($gradeName)] ?? null;

            if (! $grade) {
                $errors[] = 'Salary Grade ['.$gradeName.'] does not match any configured grade for this school. Available grades: '.($lookups['grades']->pluck('name')->implode(', ') ?: 'none').'.';
            }
        } else {
            $grade = $lookups['grades']->first();
        }

        if ($errors === []) {
            $data['_grade'] = $grade;
            $data['_individual_allowances'] = static::parseAllowances($data['individual_allowances'] ?? '');
            $data['_spouse_details'] = static::parsePairs($data['spouse_details'] ?? '');
            $data['_dependents'] = static::parsePairs($data['dependents'] ?? '');
            $data['_next_of_kin'] = static::parsePairs($data['next_of_kin'] ?? '');
            // Custom Fields are a key/value Repeater on the wizard, stored as
            // [{key, value}] rows (the infolist converts rows to pairs) — match
            // that shape exactly so wizard-entered and CSV-entered data behave
            // the same everywhere.
            $data['_custom_fields'] = static::parseKeyedRows($data['custom_fields'] ?? '');
        }

        return $errors;
    }

    /**
     * "Key: Value; Key: Value" -> ['Key' => 'Value']. Blank cells and
     * bare separators collapse to null so the JSON columns stay clean.
     *
     * @return array<string, string>|null
     */
    protected static function parsePairs(string $value): ?array
    {
        $pairs = [];

        foreach (preg_split('/[;\n]/', $value) ?: [] as $chunk) {
            $chunk = trim($chunk);

            if ($chunk === '' || ! str_contains($chunk, ':')) {
                continue;
            }

            [$key, $val] = explode(':', $chunk, 2);
            $key = trim($key);
            $val = trim($val);

            if ($key !== '') {
                $pairs[$key] = $val;
            }
        }

        return $pairs === [] ? null : $pairs;
    }

    /**
     * "Office: B12; Staff Locker: L-104" -> the key/value Repeater row shape
     * the New Employee wizard stores: [['key' => ..., 'value' => ...], ...].
     *
     * @return array<int, array{key: string, value: string}>|null
     */
    protected static function parseKeyedRows(string $value): ?array
    {
        $pairs = static::parsePairs($value);

        if ($pairs === null) {
            return null;
        }

        $rows = [];

        foreach ($pairs as $key => $item) {
            $rows[] = ['key' => $key, 'value' => $item];
        }

        return $rows;
    }

    /**
     * "Teaching Allowance: 150; Transport: 80" -> the repeater row shape the
     * New Employee wizard stores: [['name' => ..., 'amount' => ...], ...].
     *
     * @return array<int, array{name: string, amount: string}>|null
     */
    protected static function parseAllowances(string $value): ?array
    {
        $pairs = static::parsePairs($value);

        if ($pairs === null) {
            return null;
        }

        $rows = [];

        foreach ($pairs as $name => $amount) {
            $rows[] = ['name' => $name, 'amount' => $amount];
        }

        return $rows;
    }

    /**
     * Ordered list of distinct values found in a reference column that the
     * school does not have yet — mirrors the create-or-skip prompt built by
     * HasCsvBulkActions::missingReferenceSchema() so the indexes line up.
     *
     * @return array<int, string>
     */
    protected static function unknownReferenceValues(string $filePath, int $schoolId, array $columnMap, string $fieldKey): array
    {
        $candidates = static::readReferenceValues($filePath, $columnMap, $fieldKey);

        if ($candidates === []) {
            return [];
        }

        $existing = static::referenceFields()[$fieldKey]['model']::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->pluck(static::referenceFields()[$fieldKey]['column'])
            ->map(fn ($value): string => strtolower(trim((string) $value)))
            ->flip();

        $missing = [];
        foreach ($candidates as $value) {
            if (! $existing->has(strtolower($value))) {
                $missing[] = $value;
            }
        }

        return $missing;
    }

    /**
     * Ensure the department exists as a Department record (so the employee
     * shows up in the form's department list) — generating a unique code the
     * same way the procurement importer does.
     *
     * @param  array<string, mixed>  $lookups
     */
    protected static function ensureDepartment(string $name, int $schoolId, array &$lookups): ?int
    {
        $key = strtolower(trim($name));

        if (isset($lookups['departmentIndex'][$key])) {
            return $lookups['departmentIndex'][$key];
        }

        $base = strtoupper(Str::slug($name, '-')) ?: 'DEPT';
        $code = $base;
        $suffix = 1;

        while (isset($lookups['existingDepartmentCodes'][$code])) {
            $suffix++;
            $code = $base.'-'.$suffix;
        }

        $department = Department::withoutTenantScope()->create([
            'school_id' => $schoolId,
            'name' => trim($name),
            'code' => $code,
            'type' => 'administrative',
            'status' => 'active',
        ]);

        $lookups['departmentIndex'][$key] = $department->id;
        $lookups['existingDepartmentCodes'][$code] = $department->id;

        return $department->id;
    }
}
