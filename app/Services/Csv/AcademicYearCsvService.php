<?php

namespace App\Services\Csv;

use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Term;

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
            'term_1_start_date' => [
                'label' => __('Term 1 Start Date'),
                'required' => false,
                'guesses' => ['Term 1 Start Date', 'Term 1 Start', 'Term 1 Starts On'],
                'example' => '2026-01-05',
                'date' => true,
            ],
            'term_1_end_date' => [
                'label' => __('Term 1 End Date'),
                'required' => false,
                'guesses' => ['Term 1 End Date', 'Term 1 End', 'Term 1 Ends On'],
                'example' => '2026-03-28',
                'date' => true,
            ],
            'term_2_start_date' => [
                'label' => __('Term 2 Start Date'),
                'required' => false,
                'guesses' => ['Term 2 Start Date', 'Term 2 Start', 'Term 2 Starts On'],
                'example' => '2026-05-09',
                'date' => true,
            ],
            'term_2_end_date' => [
                'label' => __('Term 2 End Date'),
                'required' => false,
                'guesses' => ['Term 2 End Date', 'Term 2 End', 'Term 2 Ends On'],
                'example' => '2026-08-01',
                'date' => true,
            ],
            'term_3_start_date' => [
                'label' => __('Term 3 Start Date'),
                'required' => false,
                'guesses' => ['Term 3 Start Date', 'Term 3 Start', 'Term 3 Starts On'],
                'example' => '2026-09-07',
                'date' => true,
            ],
            'term_3_end_date' => [
                'label' => __('Term 3 End Date'),
                'required' => false,
                'guesses' => ['Term 3 End Date', 'Term 3 End', 'Term 3 Ends On'],
                'example' => '2026-12-03',
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
        return ['Year Name', 'Start Date', 'End Date', 'Term 1 Start Date', 'Term 1 End Date', 'Term 2 Start Date', 'Term 2 End Date', 'Term 3 Start Date', 'Term 3 End Date', 'Is Active'];
    }

    /**
     * Example rows written under the template header: the next ten academic
     * years with their correct dates, only the current year (2026) marked as
     * the active year. Each term's dates follow the shared calendar used by
     * the demo seeder (Term 1 Jan-Mar, Term 2 May-Aug, Term 3 Sep-Dec).
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function templateRows(): array
    {
        $rows = [];

        for ($year = 2026; $year <= 2035; $year++) {
            $rows[] = [
                'name' => (string) $year,
                'start_date' => $year.'-01-05',
                'end_date' => $year.'-12-11',
                'term_1_start_date' => $year.'-01-05',
                'term_1_end_date' => $year.'-03-28',
                'term_2_start_date' => $year.'-05-09',
                'term_2_end_date' => $year.'-08-01',
                'term_3_start_date' => $year.'-09-07',
                'term_3_end_date' => $year.'-12-03',
                'is_active' => $year === 2026 ? 'yes' : 'no',
            ];
        }

        return $rows;
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = AcademicYear::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with('terms')
            ->orderBy('id');

        $lastId = 0;

        do {
            $years = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($years->isEmpty()) {
                break;
            }

            foreach ($years as $year) {
                $termDates = [];
                foreach ($year->terms as $term) {
                    if (preg_match('/\b([123])\b/', (string) $term->name, $m)) {
                        $termDates[(int) $m[1]] = [
                            optional($term->start_date)->format('Y-m-d'),
                            optional($term->end_date)->format('Y-m-d'),
                        ];
                    }
                }

                yield [
                    $year->name,
                    optional($year->start_date)->format('Y-m-d'),
                    optional($year->end_date)->format('Y-m-d'),
                    $termDates[1][0] ?? '',
                    $termDates[1][1] ?? '',
                    $termDates[2][0] ?? '',
                    $termDates[2][1] ?? '',
                    $termDates[3][0] ?? '',
                    $termDates[3][1] ?? '',
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

        // Optional per-term dates; a term only materialises when BOTH its start
        // and end date are supplied, so either half left blank is an error.
        foreach ([1, 2, 3] as $termNumber) {
            $startKey = 'term_'.$termNumber.'_start_date';
            $endKey = 'term_'.$termNumber.'_end_date';

            $rawStart = trim($data[$startKey] ?? '');
            $rawEnd = trim($data[$endKey] ?? '');

            if ($rawStart === '' && $rawEnd === '') {
                $data[$startKey] = '';
                $data[$endKey] = '';

                continue;
            }

            if ($rawStart === '' || $rawEnd === '') {
                $errors[] = 'Term '.$termNumber.' needs both a start and an end date.';
            }

            foreach ([$startKey => $rawStart, $endKey => $rawEnd] as $key => $raw) {
                if ($raw === '') {
                    continue;
                }

                $parsed = static::toDate($raw);

                if ($parsed === null) {
                    $errors[] = static::columns()[$key]['label'].' ['.$raw.'] is not a valid date. Use YYYY-MM-DD.';
                } else {
                    $data[$key] = $parsed;
                }
            }

            if (
                $data[$startKey] !== ''
                && $data[$endKey] !== ''
                && strtotime($data[$endKey]) < strtotime($data[$startKey])
            ) {
                $errors[] = 'Term '.$termNumber.' End Date must be on or after its Start Date.';
            }
        }

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        $year = AcademicYear::withoutTenantScope()->firstOrCreate(
            ['school_id' => $schoolId, 'name' => $data['name']],
            [
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_active' => static::toBoolean($data['is_active'] ?? ''),
            ],
        );

        foreach ([1, 2, 3] as $termNumber) {
            $startKey = 'term_'.$termNumber.'_start_date';
            $endKey = 'term_'.$termNumber.'_end_date';

            if (($data[$startKey] ?? '') === '' || ($data[$endKey] ?? '') === '') {
                continue;
            }

            Term::withoutTenantScope()->firstOrCreate(
                [
                    'school_id' => $schoolId,
                    'academic_year_id' => $year->id,
                    'name' => 'Term '.$termNumber,
                ],
                [
                    'start_date' => $data[$startKey],
                    'end_date' => $data[$endKey],
                ],
            );
        }
    }
}