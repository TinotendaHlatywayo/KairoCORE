<?php

namespace App\Services\Csv;

use Carbon\Carbon;
use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\LeaveType;

class LeaveRequestCsvService extends CsvBulkService
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
            'leave_type' => [
                'label' => __('Leave Type'),
                'required' => true,
                'guesses' => ['Leave Type', 'Type'],
                'example' => 'Annual Leave',
            ],
            'start_date' => [
                'label' => __('Start Date'),
                'required' => true,
                'guesses' => ['Start Date', 'From'],
                'example' => '2026-08-10',
                'date' => true,
            ],
            'end_date' => [
                'label' => __('End Date'),
                'required' => true,
                'guesses' => ['End Date', 'To'],
                'example' => '2026-08-14',
                'date' => true,
            ],
            'reason' => [
                'label' => __('Reason'),
                'required' => true,
                'guesses' => ['Reason', 'Notes'],
                'example' => 'Family wedding',
            ],
            'status' => [
                'label' => __('Status'),
                'required' => false,
                'guesses' => ['Status'],
                'example' => 'pending',
                'default' => 'pending',
                'in' => ['pending', 'approved', 'rejected'],
            ],
            'hr_remarks' => [
                'label' => __('HR Remarks'),
                'required' => false,
                'guesses' => ['HR Remarks', 'Remarks'],
                'example' => '',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return [
            'Employee', 'Leave Type', 'Start Date', 'End Date', 'Total Days',
            'Reason', 'Status', 'HR Remarks',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = LeaveRequest::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with(['employee', 'leaveType'])
            ->orderBy('id');

        $lastId = 0;

        do {
            $requests = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($requests->isEmpty()) {
                break;
            }

            foreach ($requests as $request) {
                yield [
                    $request->employee
                        ? $request->employee->first_name.' '.$request->employee->last_name.' ('.$request->employee->employee_number.')'
                        : '',
                    $request->leaveType?->name,
                    optional($request->start_date)->format('Y-m-d'),
                    optional($request->end_date)->format('Y-m-d'),
                    $request->total_days,
                    $request->reason,
                    $request->status,
                    $request->hr_remarks,
                ];
            }

            $lastId = $requests->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $employees = Employee::withoutTenantScope()->where('school_id', $schoolId)->get();
        $leaveTypes = LeaveType::withoutTenantScope()->where('school_id', $schoolId)->get();

        $lookups = [
            'employeesByNumber' => $employees->keyBy(fn (Employee $e): string => strtolower(trim($e->employee_number))),
            'employeesByName' => $employees->keyBy(fn (Employee $e): string => strtolower(trim($e->first_name.' '.$e->last_name))),
            'leaveTypes' => $leaveTypes->mapWithKeys(fn (LeaveType $t): array => [
                strtolower(trim($t->name)) => $t,
                strtolower(trim($t->code)) => $t,
            ]),
            'leaveTypeList' => $leaveTypes,
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

        $data['leave_type'] = trim($data['leave_type'] ?? '');
        if ($data['leave_type'] === '') {
            $errors[] = 'Leave Type is required (column empty or not mapped).';
        }

        $data['reason'] = trim($data['reason'] ?? '');
        if ($data['reason'] === '') {
            $errors[] = 'Reason is required (column empty or not mapped).';
        }

        foreach (['start_date', 'end_date'] as $dateField) {
            $raw = trim($data[$dateField] ?? '');

            if ($raw === '') {
                $data[$dateField] = '';
                $errors[] = static::columns()[$dateField]['label'].' is required (column empty or not mapped).';

                continue;
            }

            try {
                $data[$dateField] = Carbon::parse($raw)->toDateString();
            } catch (\Throwable) {
                $data[$dateField] = '';
                $errors[] = static::columns()[$dateField]['label'].' ['.$raw.'] is not a valid date. Use YYYY-MM-DD.';
            }
        }

        if ($data['start_date'] !== '' && $data['end_date'] !== '' && $data['end_date'] < $data['start_date']) {
            $errors[] = 'End Date must be on or after the Start Date.';
        }

        $data['status'] = strtolower(trim($data['status'] ?? ''));
        if ($data['status'] === '') {
            $data['status'] = 'pending';
        }
        if (! in_array($data['status'], ['pending', 'approved', 'rejected'], true)) {
            $errors[] = 'Status must be one of: pending, approved, rejected.';
        }

        if (empty($errors)) {
            $employee = static::resolveEmployee($data['employee'], $lookups);

            if (! $employee) {
                $errors[] = 'Employee ['.$data['employee'].'] was not found in this school.';
            } else {
                $data['_employee'] = $employee;
            }

            $leaveType = static::resolveLeaveType($data['leave_type'], $lookups);

            if (! $leaveType) {
                $errors[] = 'Leave Type ['.$data['leave_type'].'] was not found in this school. Available leave types: '.($lookups['leaveTypeList']->pluck('name')->implode(', ') ?: 'none').'.';
            } else {
                $data['_leaveType'] = $leaveType;
            }
        }

        return $errors;
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

    protected static function resolveLeaveType(string $value, array $lookups): ?LeaveType
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return $lookups['leaveTypes'][strtolower($value)] ?? null;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        LeaveRequest::create([
            'school_id' => $schoolId,
            'employee_id' => $data['_employee']->id,
            'leave_type_id' => $data['_leaveType']->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'],
            'status' => $data['status'],
            'hr_remarks' => $data['hr_remarks'] !== '' ? $data['hr_remarks'] : null,
        ]);
    }
}
