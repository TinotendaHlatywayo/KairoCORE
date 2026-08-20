<?php

namespace App\Filament\Imports;

use App\Models\User;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\HR\Models\Employee;
use Modules\HR\Models\SalaryGrade;

class EmployeeImporter extends Importer
{
    protected static ?string $model = Employee::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('first_name')
                ->label('First Name')
                ->requiredMapping()
                ->guess(['First Name', 'Firstname', 'Given Name'])
                ->rules(['required', 'string', 'max:255'])
                ->example('Matipa'),

            ImportColumn::make('last_name')
                ->label('Last Name')
                ->requiredMapping()
                ->guess(['Last Name', 'Lastname', 'Surname'])
                ->rules(['required', 'string', 'max:255'])
                ->example('Maphosa'),

            ImportColumn::make('email')
                ->label('Email')
                ->requiredMapping()
                ->guess(['Email', 'Email Address'])
                ->rules(['required', 'email:filter', 'max:255'])
                ->example('matipa.maphosa@schoolcore.test'),

            ImportColumn::make('phone_number')
                ->label('Phone Number')
                ->requiredMapping()
                ->guess(['Phone Number', 'Phone', 'Mobile', 'Cell'])
                ->rules(['required', 'string', 'max:30', 'regex:/^\+?[0-9\s\-]{7,20}$/'])
                ->example('+263786366555'),

            ImportColumn::make('national_id')
                ->label('National ID')
                ->requiredMapping()
                ->guess(['National ID', 'National Id', 'ID Number', 'Passport'])
                ->rules(['required', 'string', 'max:50'])
                ->example('42-987654-Y-18'),

            ImportColumn::make('department')
                ->label('Department')
                ->requiredMapping()
                ->guess(['Department', 'Dept'])
                ->rules(['required', 'string', 'max:255'])
                ->example('Academics'),

            ImportColumn::make('designation')
                ->label('Designation')
                ->requiredMapping()
                ->guess(['Designation', 'Job Title', 'Position'])
                ->rules(['required', 'string', 'max:255'])
                ->example('Biology Teacher'),

            ImportColumn::make('salary_grade')
                ->label('Salary Grade Name')
                ->guess(['Salary Grade Name', 'Salary Grade', 'Grade'])
                ->rules(['nullable', 'string', 'max:255'])
                ->helperText("Must exactly match an existing Salary Grade name. Leave blank to use the school's default grade.")
                ->example('Educator Scale B'),

            ImportColumn::make('employment_type')
                ->label('Employment Type')
                ->guess(['Employment Type', 'Contract Type'])
                ->rules(['nullable', 'in:Permanent,Contract,Part-time,Volunteer'])
                ->helperText('One of: Permanent, Contract, Part-time, Volunteer. Defaults to Permanent if left blank.')
                ->fillRecordUsing(function (Employee $record, ?string $state): void {
                    $record->employment_type = filled($state) ? $state : 'Permanent';
                })
                ->example('Permanent'),

            ImportColumn::make('date_joined')
                ->label('Date Joined')
                ->guess(['Date Joined', 'Start Date', 'Hire Date'])
                ->rules(['nullable', 'date'])
                ->helperText('Format YYYY-MM-DD. Defaults to today if left blank.')
                ->fillRecordUsing(function (Employee $record, ?string $state): void {
                    $record->date_joined = filled($state) ? $state : now()->toDateString();
                })
                ->example('2021-05-01'),
        ];
    }

    public function resolveRecord(): ?Employee
    {
        $schoolId = $this->options['school_id'];

        $emailTaken = Employee::withTrashed()
            ->where('school_id', $schoolId)
            ->where('email', $this->data['email'])
            ->exists()
            || User::where('school_id', $schoolId)
                ->where('email', $this->data['email'])
                ->exists();

        if ($emailTaken) {
            throw new RowImportFailedException(
                "The email [{$this->data['email']}] is already used by another employee or user account."
            );
        }

        $nationalIdTaken = Employee::withTrashed()
            ->where('school_id', $schoolId)
            ->where('national_id', $this->data['national_id'])
            ->exists();

        if ($nationalIdTaken) {
            throw new RowImportFailedException(
                "The national ID [{$this->data['national_id']}] is already assigned to another employee."
            );
        }

        $gradeName = trim((string) ($this->data['salary_grade'] ?? ''));

        if ($gradeName !== '') {
            $grade = SalaryGrade::where('school_id', $schoolId)
                ->where('name', $gradeName)
                ->first();

            if (! $grade) {
                throw new RowImportFailedException(
                    "Salary Grade [{$gradeName}] does not match any configured grade for this school. Check the exact spelling against Salary Grades."
                );
            }
        } else {
            $grade = SalaryGrade::where('school_id', $schoolId)->first();

            if (! $grade) {
                throw new RowImportFailedException(
                    'No salary grade is configured for this school yet, so this row cannot be assigned one. Create a Salary Grade first.'
                );
            }
        }

        $user = User::create([
            'school_id' => $schoolId,
            'name' => trim($this->data['first_name'].' '.$this->data['last_name']),
            'email' => $this->data['email'],
            'password' => Hash::make(Str::random(12)),
        ]);

        return new Employee([
            'school_id' => $schoolId,
            'user_id' => $user->id,
            'current_grade_id' => $grade->id,
            'gender' => 'male',
            'date_of_birth' => '1990-01-01',
            'marital_status' => 'single',
            'physical_address' => 'Not Provided',
            'emergency_contact_name' => 'Not Provided',
            'emergency_contact_phone' => 'Not Provided',
            'role' => 'Teacher',
            'status' => 'active',
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your employee import has completed and '.number_format($import->successful_rows).' '.
            str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.
                str('row')->plural($failedRowsCount).' failed — download the failed rows report to see the exact row, column and reason for each.';
        }

        return $body;
    }

    public function getJobBatchName(): ?string
    {
        return 'employee-import';
    }
}
