<?php

namespace App\Services\Csv;

use Modules\Academics\Models\Subject;

class SubjectCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'name' => [
                'label' => __('Subject Name'),
                'required' => true,
                'guesses' => ['Subject Name', 'Name'],
                'example' => 'Mathematics',
            ],
            'code' => [
                'label' => __('Subject Code'),
                'required' => true,
                'guesses' => ['Code', 'Subject Code'],
                'example' => 'MAT',
            ],
            'type' => [
                'label' => __('Type'),
                'required' => true,
                'guesses' => ['Type', 'Subject Type'],
                'example' => 'theory',
                'default' => 'theory',
                'in' => ['theory', 'practical', 'both'],
            ],
            'credit_weight' => [
                'label' => __('Credit Weight'),
                'required' => false,
                'guesses' => ['Credit Weight', 'Credit', 'Credits'],
                'example' => '1.00',
                'default' => '1.00',
            ],
            'is_elective' => [
                'label' => __('Is Elective'),
                'required' => false,
                'guesses' => ['Is Elective', 'Elective'],
                'example' => 'no',
                'default' => 'no',
                'in' => ['yes', 'no', 'true', 'false', '1', '0'],
            ],
            'workflow_status' => [
                'label' => __('Status'),
                'required' => false,
                'guesses' => ['Status'],
                'default' => 'pending',
                'in' => ['pending', 'in_progress', 'complete'],
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return ['Subject Name', 'Subject Code', 'Type', 'Credit Weight', 'Is Elective', 'Status'];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = Subject::withoutTenantScope()->where('school_id', $schoolId)->orderBy('id');

        $lastId = 0;

        do {
            $subjects = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($subjects->isEmpty()) {
                break;
            }

            foreach ($subjects as $subject) {
                yield [
                    $subject->name,
                    $subject->code,
                    $subject->type,
                    $subject->credit_weight,
                    $subject->is_elective ? 'yes' : 'no',
                    $subject->workflow_status,
                ];
            }

            $lastId = $subjects->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $lookups = [
            'existingCodes' => Subject::withoutTenantScope()->where('school_id', $schoolId)->pluck('code')
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
            $errors[] = 'Subject Code ['.$data['code'].'] already exists for another subject in this school.';
        }

        $data['type'] = strtolower(trim($data['type'] ?? ''));
        if (! in_array($data['type'], ['theory', 'practical', 'both'], true)) {
            $errors[] = 'Type must be one of: theory, practical, both.';
        }

        $raw = trim($data['credit_weight'] ?? '');
        if ($raw !== '' && ! is_numeric($raw)) {
            $errors[] = 'Credit Weight ['.$raw.'] must be a number.';
        }

        $data['workflow_status'] = strtolower(trim($data['workflow_status'] ?? ''));
        if ($data['workflow_status'] === '') {
            $data['workflow_status'] = 'pending';
        }

        if (! in_array($data['workflow_status'], ['pending', 'in_progress', 'complete'], true)) {
            $errors[] = 'Status must be one of: pending, in_progress, complete.';
        }

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        Subject::create([
            'school_id' => $schoolId,
            'name' => $data['name'],
            'code' => $data['code'],
            'type' => $data['type'],
            'credit_weight' => static::toDecimal($data['credit_weight'] ?? '', 1.00),
            'is_elective' => static::toBoolean($data['is_elective'] ?? ''),
            'workflow_status' => $data['workflow_status'],
        ]);

        $lookups['existingCodes'][strtolower(trim($data['code']))] = true;
    }
}
