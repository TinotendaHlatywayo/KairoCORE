<?php

namespace App\Services\Csv;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\HR\Models\Employee;
use Modules\HR\Models\SalaryGrade;

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
                'required' => false,
                'guesses' => ['Phone Number', 'Phone', 'Mobile', 'Cell'],
                'example' => '+263786366555',
            ],
            'national_id' => [
                'label' => __('National ID'),
                'required' => false,
                'guesses' => ['National ID', 'ID Number', 'Passport'],
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
            'department' => [
                'label' => __('Department'),
                'required' => true,
                'guesses' => ['Department', 'Dept'],
                'example' => 'Academics',
            ],
            'designation' => [
                'label' => __('Designation'),
                'required' => true,
                'guesses' => ['Designation', 'Job Title', 'Position'],
                'example' => 'Biology Teacher',
            ],
            'salary_grade' => [
                'label' => __('Salary Grade Name'),
                'required' => false,
                'guesses' => ['Salary Grade Name', 'Salary Grade', 'Grade'],
                'example' => 'Educator Scale B',
            ],
            'employment_type' => [
                'label' => __('Employment Type'),
                'required' => false,
                'guesses' => ['Employment Type', 'Contract Type'],
                'example' => 'Permanent',
                'default' => 'Permanent',
                'in' => ['Permanent', 'Contract', 'Part-time', 'Volunteer'],
            ],
            'date_joined' => [
                'label' => __('Date Joined'),
                'required' => false,
                'guesses' => ['Date Joined', 'Start Date', 'Hire Date'],
                'example' => '2021-05-01',
                'date' => true,
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

    public static function exportHeaders(): array
    {
        return [
            'Employee Number', 'First Name', 'Last Name', 'Email', 'Phone Number',
            'National ID', 'Gender', 'Date of Birth', 'Department', 'Designation',
            'Salary Grade', 'Employment Type', 'Date Joined', 'Status',
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
                    $employee->department,
                    $employee->designation,
                    $employee->currentGrade?->name,
                    $employee->employment_type,
                    optional($employee->date_joined)->format('Y-m-d'),
                    $employee->status,
                ];
            }

            $lastId = $employees->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $csvHeaders = static::readCsvHeaders($filePath);

        if (empty($csvHeaders)) {
            throw new \RuntimeException('The CSV file has no readable header row. Download the template and use its exact column names.');
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
            throw new \RuntimeException('Could not open the CSV file.');
        }

        fgets($handle); // skip header row

        $total = 0;
        while (fgetcsv($handle, 0, ',', escape: '\\') !== false) {
            $total++;
        }

        rewind($handle);
        fgets($handle); // skip header row again

        $grades = SalaryGrade::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn (SalaryGrade $g): string => strtolower(trim($g->name)));

        $existingEmails = User::withoutTenantScope()->where('school_id', $schoolId)->pluck('email')
            ->map(fn ($v): string => strtolower(trim((string) $v)))->flip();
        $existingNationalIds = Employee::withoutTenantScope()->where('school_id', $schoolId)->whereNotNull('national_id')->pluck('national_id')
            ->map(fn ($v): string => strtolower(trim((string) $v)))->flip();

        $success = 0;
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

            $errors = static::validateAndNormalize($data, $grades, $existingEmails, $existingNationalIds);

            if (! empty($errors)) {
                $failures[] = ['row' => $rowNumber, 'errors' => $errors, 'data' => $data];
                $onProgress !== null && $onProgress($processed, $total, true, $errors);

                continue;
            }

            try {
                DB::transaction(function () use (&$existingEmails, &$existingNationalIds, $data, $schoolId) {
                    $employee = Employee::create([
                        'school_id' => $schoolId,
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'email' => $data['email'],
                        'phone_number' => $data['phone_number'] !== '' ? $data['phone_number'] : null,
                        'national_id' => $data['national_id'] !== '' ? $data['national_id'] : null,
                        'gender' => $data['gender'] !== '' ? $data['gender'] : 'female',
                        'date_of_birth' => $data['date_of_birth'] !== '' ? $data['date_of_birth'] : '1990-01-01',
                        'department' => $data['department'],
                        'designation' => $data['designation'],
                        'current_grade_id' => $data['_grade']->id,
                        'employment_type' => $data['employment_type'] !== '' ? $data['employment_type'] : 'Permanent',
                        'date_joined' => $data['date_joined'] !== '' ? $data['date_joined'] : now()->toDateString(),
                        'status' => $data['status'] !== '' ? $data['status'] : 'active',
                        'role' => $data['designation'],
                        'marital_status' => 'single',
                        'physical_address' => 'Not Provided',
                        'emergency_contact_name' => 'Not Provided',
                        'emergency_contact_phone' => 'Not Provided',
                    ]);

                    if (filled($employee->email)) {
                        $existingEmails[strtolower(trim($employee->email))] = true;
                    }

                    if (filled($employee->national_id)) {
                        $existingNationalIds[strtolower(trim($employee->national_id))] = true;
                    }
                });

                $success++;
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

        return compact('success', 'total', 'failures');
    }

    protected static function validateAndNormalize(
        array &$data,
        Collection $grades,
        Collection $existingEmails,
        Collection $existingNationalIds,
    ): array {
        $errors = [];

        foreach (['first_name', 'last_name', 'email', 'department', 'designation'] as $required) {
            $data[$required] = trim($data[$required] ?? '');

            if ($data[$required] === '') {
                $errors[] = static::columns()[$required]['label'].' is required (column empty or not mapped).';
            }
        }

        if (filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'Email ['.($data['email'] ?? '').'] is not a valid email address.';
        }

        $email = strtolower(trim($data['email'] ?? ''));
        if ($email !== '' && isset($existingEmails[$email])) {
            $errors[] = 'Email ['.$data['email'].'] is already used by another employee or user account.';
        }

        $nationalId = strtolower(trim($data['national_id'] ?? ''));
        if ($nationalId !== '' && isset($existingNationalIds[$nationalId])) {
            $errors[] = 'National ID ['.$data['national_id'].'] is already assigned to another employee.';
        }

        $data['gender'] = strtolower(trim($data['gender'] ?? ''));
        if ($data['gender'] !== '' && ! in_array($data['gender'], ['male', 'female', 'other'], true)) {
            $errors[] = 'Gender must be one of: male, female, other.';
        }

        $data['status'] = strtolower(trim($data['status'] ?? ''));
        if ($data['status'] !== '' && ! in_array($data['status'], ['active', 'suspended', 'on_leave'], true)) {
            $errors[] = 'Status must be one of: active, suspended, on_leave.';
        }

        $data['employment_type'] = trim($data['employment_type'] ?? '');
        if ($data['employment_type'] !== '' && ! in_array($data['employment_type'], ['Permanent', 'Contract', 'Part-time', 'Volunteer'], true)) {
            $errors[] = 'Employment Type must be one of: Permanent, Contract, Part-time, Volunteer.';
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

        $gradeName = trim($data['salary_grade'] ?? '');

        if ($gradeName !== '') {
            $grade = $grades[strtolower($gradeName)] ?? null;

            if (! $grade) {
                $errors[] = 'Salary Grade ['.$gradeName.'] does not match any configured grade for this school. Available grades: '.($grades->pluck('name')->implode(', ') ?: 'none').'.';
            }
        } else {
            $grade = $grades->first();
        }

        if (empty($errors)) {
            $data['_grade'] = $grade;
        }

        return $errors;
    }
}
