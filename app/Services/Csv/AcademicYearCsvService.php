<?php

namespace App\Services\Csv;

use Modules\Academics\Models\AcademicYear;

class AcademicYearCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'name' => [
                'label' => __('Year Name'),
                'required' => true,
                'guesses' => ['Name', 'Year Name', 'Academic Year'],
                'example' => '2026',
            ],
            'start_date' => [
                'label' => __('Start Date'),
                'required' => true,
                'guesses' => ['Start Date', 'Starts On'],
                'example' => '2026-01-05',
                'date' => true,
            ],
            'end_date' => [
                'label' => __('End Date'),
                'required' => true,
                'guesses' => ['End Date', 'Ends On'],
                'example' => '2026-12-11',
                'date' => true,
            ],
            'is_active' => [
                'label' => __('Is Active'),
                'required' => false,
                'guesses' => ['Is Active', 'Active'],
                'example' => 'no',
                'default' => 'no',
                'in' => ['yes', 'no', 'true', 'false', '1', '0'],
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return ['Year Name', 'Start Date', 'End Date', 'Is Active'];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = AcademicYear::withoutTenantScope()->where('school_id', $schoolId)->orderBy('id');

        $lastId = 0;

        do {
            $years = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($years->isEmpty()) {
                break;
            }

            foreach ($years as $year) {
                yield [
                    $year->name,
                    optional($year->start_date)->format('Y-m-d'),
                    optional($year->end_date)->format('Y-m-d'),
                    $year->is_active ? 'yes' : 'no',
                ];
            }

            $lastId = $years->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $lookups = [];

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

        $data['name'] = trim($data['name'] ?? '');
        if ($data['name'] === '') {
            $errors[] = 'Year Name is required (column empty or not mapped).';
        }

        foreach (['start_date', 'end_date'] as $dateField) {
            $raw = trim($data[$dateField] ?? '');

            if ($raw === '') {
                $errors[] = static::columns()[$dateField]['label'].' is required (column empty or not mapped).';

                continue;
            }

            $parsed = static::toDate($raw);

            if ($parsed === null) {
                $errors[] = static::columns()[$dateField]['label'].' ['.$raw.'] is not a valid date. Use YYYY-MM-DD.';
            } else {
                $data[$dateField] = $parsed;
            }
        }

        if (
            $data['start_date'] !== ''
            && $data['end_date'] !== ''
            && strtotime($data['end_date']) < strtotime($data['start_date'])
        ) {
            $errors[] = 'End Date must be on or after Start Date.';
        }

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        AcademicYear::create([
            'school_id' => $schoolId,
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'is_active' => static::toBoolean($data['is_active'] ?? ''),
        ]);
    }
}
