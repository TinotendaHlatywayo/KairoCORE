<?php

namespace App\Services\Csv;

use Modules\Admin\Models\Department;
use Modules\HR\Models\Employee;

class DepartmentCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'name' => [
                'label' => __('Department Name'),
                'required' => true,
                'guesses' => ['Name', 'Department Name'],
                'example' => 'Academics',
            ],
            'code' => [
                'label' => __('Department Code'),
                'required' => true,
                'guesses' => ['Code', 'Department Code'],
                'example' => 'ACA',
            ],
            'type' => [
                'label' => __('Type'),
                'required' => true,
                'guesses' => ['Type', 'Department Type'],
                'example' => 'academic',
                'default' => 'academic',
                'in' => ['academic', 'administrative', 'support'],
            ],
            'head' => [
                'label' => __('Head (Staff)'),
                'required' => false,
                'guesses' => ['Head', 'Head of Department', 'Head Name'],
                'example' => 'Tendai Mutasa',
            ],
            'budget_code' => [
                'label' => __('Budget Code'),
                'required' => false,
                'guesses' => ['Budget Code', 'Cost Centre'],
                'example' => 'ACA-01',
            ],
            'status' => [
                'label' => __('Status'),
                'required' => false,
                'guesses' => ['Status'],
                'example' => 'active',
                'default' => 'active',
                'in' => ['active', 'suspended'],
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return ['Department Name', 'Department Code', 'Type', 'Head', 'Budget Code', 'Status'];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = Department::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with('head')
            ->orderBy('id');

        $lastId = 0;

        do {
            $departments = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($departments->isEmpty()) {
                break;
            }

            foreach ($departments as $department) {
                yield [
                    $department->name,
                    $department->code,
                    $department->type,
                    $department->head ? $department->head->first_name.' '.$department->head->last_name : null,
                    $department->budget_code,
                    $department->status,
                ];
            }

            $lastId = $departments->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $lookups = [
            'employees' => Employee::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn ($e): string => strtolower(trim($e->first_name.' '.$e->last_name))),
            'existingCodes' => Department::withoutTenantScope()->where('school_id', $schoolId)->pluck('code')
                ->map(fn ($v): string => strtolower(trim((string) $v)))->flip(),
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

        foreach (['name', 'code', 'type'] as $required) {
            $data[$required] = trim($data[$required] ?? '');

            if ($data[$required] === '') {
                $errors[] = static::columns()[$required]['label'].' is required (column empty or not mapped).';
            }
        }

        $code = strtolower(trim($data['code'] ?? ''));
        if ($code !== '' && isset($lookups['existingCodes'][$code])) {
            $errors[] = 'Department Code ['.$data['code'].'] already exists for another department in this school.';
        }

        $data['type'] = strtolower(trim($data['type'] ?? ''));
        if (! in_array($data['type'], ['academic', 'administrative', 'support'], true)) {
            $errors[] = 'Type must be one of: academic, administrative, support.';
        }

        $data['status'] = strtolower(trim($data['status'] ?? ''));
        if ($data['status'] === '') {
            $data['status'] = 'active';
        }

        if (! in_array($data['status'], ['active', 'suspended'], true)) {
            $errors[] = 'Status must be one of: active, suspended.';
        }

        $data['head'] = trim($data['head'] ?? '');
        $data['_head'] = null;

        if ($data['head'] !== '') {
            $employee = $lookups['employees'][strtolower($data['head'])] ?? null;

            if (! $employee) {
                $errors[] = 'Head ['.$data['head'].'] was not found as an employee in this school.';
            } else {
                $data['_head'] = $employee;
            }
        }

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        Department::create([
            'school_id' => $schoolId,
            'name' => $data['name'],
            'code' => $data['code'],
            'type' => $data['type'],
            'head_user_id' => $data['_head']?->id,
            'budget_code' => $data['budget_code'] !== '' ? $data['budget_code'] : null,
            'status' => $data['status'],
        ]);

        $lookups['existingCodes'][strtolower(trim($data['code']))] = true;
    }
}
