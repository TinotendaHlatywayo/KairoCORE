<?php

namespace App\Services\Csv;

use Modules\HR\Models\Employee;
use Modules\HR\Models\StaffLoan;

class StaffLoanCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'employee' => [
                'label' => __('Employee'),
                'required' => true,
                'guesses' => ['Employee', 'Employee Name', 'Staff'],
                'example' => 'Matipa Maphosa (EMP-001)',
            ],
            'loan_type' => [
                'label' => __('Loan Type'),
                'required' => true,
                'guesses' => ['Loan Type', 'Type'],
                'example' => 'Salary Advance',
                'in' => ['salary_advance', 'emergency', 'device_loan'],
            ],
            'principal_amount' => [
                'label' => __('Principal Amount'),
                'required' => true,
                'guesses' => ['Principal', 'Principal Amount'],
                'example' => '500.00',
            ],
            'balance_remaining' => [
                'label' => __('Balance Remaining'),
                'required' => false,
                'guesses' => ['Balance Remaining', 'Balance'],
                'example' => '500.00',
                'default' => '0',
            ],
            'monthly_deduction' => [
                'label' => __('Monthly Deduction'),
                'required' => false,
                'guesses' => ['Monthly Deduction'],
                'example' => '50.00',
                'default' => '0',
            ],
            'status' => [
                'label' => __('Status'),
                'required' => false,
                'guesses' => ['Status'],
                'example' => 'pending',
                'default' => 'pending',
                'in' => ['pending', 'active', 'settled'],
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return [
            'Employee', 'Loan Type', 'Principal Amount', 'Balance Remaining',
            'Monthly Deduction', 'Status',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = StaffLoan::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with('employee')
            ->orderBy('id');

        $lastId = 0;

        do {
            $loans = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($loans->isEmpty()) {
                break;
            }

            foreach ($loans as $loan) {
                yield [
                    $loan->employee
                        ? $loan->employee->first_name.' '.$loan->employee->last_name.' ('.$loan->employee->employee_number.')'
                        : '',
                    $loan->loan_type,
                    $loan->principal_amount,
                    $loan->balance_remaining,
                    $loan->monthly_deduction,
                    $loan->status,
                ];
            }

            $lastId = $loans->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $employees = Employee::withoutTenantScope()->where('school_id', $schoolId)->get();

        $lookups = [
            'employeesByNumber' => $employees->keyBy(fn (Employee $e): string => strtolower(trim($e->employee_number))),
            'employeesByName' => $employees->keyBy(fn (Employee $e): string => strtolower(trim($e->first_name.' '.$e->last_name))),
        ];

        return static::runImport(
            $filePath,
            $schoolId,
            $columnMap,
            $onProgress,
            $lookups,
            fn (array &$data, array $lookups) => static::validateAndNormalize($data, $lookups),
            fn (array $data, int $schoolId, array &$lookups) => static::createRow($data, $schoolId, $lookups),
        );
    }

    protected static function validateAndNormalize(array &$data, array $lookups): array
    {
        $errors = [];

        $data['employee'] = trim($data['employee'] ?? '');
        if ($data['employee'] === '') {
            $errors[] = 'Employee is required (column empty or not mapped).';
        }

        $data['loan_type'] = static::normalizeLoanType($data['loan_type'] ?? '');
        if ($data['loan_type'] === '') {
            $errors[] = 'Loan Type is required (column empty or not mapped).';
        } elseif (! in_array($data['loan_type'], ['salary_advance', 'emergency', 'device_loan'], true)) {
            $errors[] = 'Loan Type must be one of: salary_advance, emergency, device_loan.';
        }

        $data['principal_amount'] = trim($data['principal_amount'] ?? '');
        if ($data['principal_amount'] === '') {
            $errors[] = 'Principal Amount is required (column empty or not mapped).';
        } elseif (! is_numeric($data['principal_amount'])) {
            $errors[] = 'Principal Amount ['.$data['principal_amount'].'] must be a number.';
        }

        foreach (['balance_remaining', 'monthly_deduction'] as $numericField) {
            $raw = trim($data[$numericField] ?? '');

            if ($raw === '') {
                continue;
            }

            if (! is_numeric($raw)) {
                $errors[] = static::columns()[$numericField]['label'].' ['.$raw.'] must be a number.';
            }
        }

        if (trim($data['balance_remaining'] ?? '') === '') {
            $data['balance_remaining'] = $data['principal_amount'];
        }

        $data['status'] = strtolower(trim($data['status'] ?? ''));
        if ($data['status'] === '') {
            $data['status'] = 'pending';
        }
        if (! in_array($data['status'], ['pending', 'active', 'settled'], true)) {
            $errors[] = 'Status must be one of: pending, active, settled.';
        }

        if (empty($errors) && $data['employee'] !== '') {
            $employee = static::resolveEmployee($data['employee'], $lookups);

            if (! $employee) {
                $errors[] = 'Employee ['.$data['employee'].'] was not found in this school.';
            } else {
                $data['_employee'] = $employee;
            }
        }

        return $errors;
    }

    protected static function normalizeLoanType(string $value): string
    {
        $key = strtolower(trim($value));

        $labels = [
            'salary_advance' => 'salary_advance',
            'salary advance' => 'salary_advance',
            'emergency' => 'emergency',
            'emergency loan' => 'emergency',
            'device_loan' => 'device_loan',
            'device loan' => 'device_loan',
        ];

        return $labels[$key] ?? $key;
    }

    protected static function resolveEmployee(string $value, array $lookups): ?Employee
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $key = strtolower($value);

        if (preg_match('/^.*?\(([^)]+)\)$/', $value, $matches)) {
            $numberKey = strtolower(trim($matches[1]));

            if (isset($lookups['employeesByNumber'][$numberKey])) {
                return $lookups['employeesByNumber'][$numberKey];
            }
        }

        return $lookups['employeesByNumber'][$key]
            ?? $lookups['employeesByName'][$key]
            ?? null;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        StaffLoan::create([
            'school_id' => $schoolId,
            'employee_id' => $data['_employee']->id,
            'loan_type' => $data['loan_type'],
            'principal_amount' => static::toDecimal($data['principal_amount']),
            'balance_remaining' => static::toDecimal($data['balance_remaining']),
            'monthly_deduction' => static::toDecimal($data['monthly_deduction']),
            'status' => $data['status'],
        ]);
    }
}
