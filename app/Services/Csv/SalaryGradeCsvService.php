<?php

namespace App\Services\Csv;

use Modules\HR\Models\SalaryGrade;

class SalaryGradeCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'name' => [
                'label' => __('Grade Name'),
                'required' => true,
                'guesses' => ['Grade Name', 'Name'],
                'example' => 'Educator Scale B',
            ],
            'base_salary' => [
                'label' => __('Base Salary'),
                'required' => false,
                'guesses' => ['Base Salary', 'Salary'],
                'example' => '800.00',
                'default' => '0',
            ],
            'hourly_rate' => [
                'label' => __('Hourly Rate'),
                'required' => false,
                'guesses' => ['Hourly Rate'],
                'example' => '0',
                'default' => '0',
            ],
            'housing_allowance' => [
                'label' => __('Housing Allowance'),
                'required' => false,
                'guesses' => ['Housing Allowance'],
                'example' => '0',
                'default' => '0',
            ],
            'transport_allowance' => [
                'label' => __('Transport Allowance'),
                'required' => false,
                'guesses' => ['Transport Allowance'],
                'example' => '0',
                'default' => '0',
            ],
            'duty_allowance' => [
                'label' => __('Duty Allowance'),
                'required' => false,
                'guesses' => ['Duty Allowance'],
                'example' => '0',
                'default' => '0',
            ],
            'overtime_eligible' => [
                'label' => __('Overtime Eligible'),
                'required' => false,
                'guesses' => ['Overtime Eligible'],
                'example' => 'no',
                'default' => 'no',
                'in' => ['yes', 'no', 'true', 'false', '1', '0'],
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return [
            'Grade Name', 'Base Salary', 'Hourly Rate', 'Housing Allowance',
            'Transport Allowance', 'Duty Allowance', 'Overtime Eligible',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = SalaryGrade::withoutTenantScope()->where('school_id', $schoolId)->orderBy('id');

        $lastId = 0;

        do {
            $grades = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($grades->isEmpty()) {
                break;
            }

            foreach ($grades as $grade) {
                yield [
                    $grade->name,
                    $grade->base_salary,
                    $grade->hourly_rate,
                    $grade->housing_allowance,
                    $grade->transport_allowance,
                    $grade->duty_allowance,
                    $grade->overtime_eligible ? 'yes' : 'no',
                ];
            }

            $lastId = $grades->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $lookups = [
            'existingNames' => SalaryGrade::withoutTenantScope()->where('school_id', $schoolId)->pluck('name')
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

        $data['name'] = trim($data['name'] ?? '');
        if ($data['name'] === '') {
            $errors[] = 'Grade Name is required (column empty or not mapped).';
        }

        $name = strtolower($data['name']);
        if ($name !== '' && isset($lookups['existingNames'][$name])) {
            $errors[] = 'Salary Grade ['.$data['name'].'] already exists in this school.';
        }

        foreach (['base_salary', 'hourly_rate', 'housing_allowance', 'transport_allowance', 'duty_allowance'] as $numericField) {
            $raw = trim($data[$numericField] ?? '');

            if ($raw === '') {
                continue;
            }

            if (! is_numeric($raw)) {
                $errors[] = static::columns()[$numericField]['label'].' ['.$raw.'] must be a number.';
            }
        }

        $data['overtime_eligible'] = static::toBoolean($data['overtime_eligible'] ?? '');

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        $name = strtolower(trim($data['name']));

        if (isset($lookups['existingNames'][$name])) {
            throw new \RuntimeException('Salary Grade ['.$data['name'].'] already exists in this school.');
        }

        SalaryGrade::create([
            'school_id' => $schoolId,
            'name' => $data['name'],
            'base_salary' => static::toDecimal($data['base_salary']),
            'hourly_rate' => static::toDecimal($data['hourly_rate']),
            'housing_allowance' => static::toDecimal($data['housing_allowance']),
            'transport_allowance' => static::toDecimal($data['transport_allowance']),
            'duty_allowance' => static::toDecimal($data['duty_allowance']),
            'overtime_eligible' => static::toBoolean($data['overtime_eligible']),
        ]);

        $lookups['existingNames'][$name] = true;
    }
}
