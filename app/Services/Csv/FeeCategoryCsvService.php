<?php

namespace App\Services\Csv;

use Modules\Finance\Models\FeeCategory;

class FeeCategoryCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'name' => [
                'label' => __('Category Name'),
                'required' => true,
                'guesses' => ['Category Name', 'Name'],
                'example' => 'Tuition',
            ],
            'description' => [
                'label' => __('Description'),
                'required' => false,
                'guesses' => ['Description', 'Notes'],
                'example' => 'Regular tuition fees',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return ['Category Name', 'Description'];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = FeeCategory::withoutTenantScope()->where('school_id', $schoolId)->orderBy('id');

        $lastId = 0;

        do {
            $categories = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($categories->isEmpty()) {
                break;
            }

            foreach ($categories as $category) {
                yield [
                    $category->name,
                    $category->description,
                ];
            }

            $lastId = $categories->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $lookups = static::buildLookups($schoolId);

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

    protected static function buildLookups(int $schoolId): array
    {
        $existingNames = FeeCategory::withoutTenantScope()->where('school_id', $schoolId)->pluck('name')
            ->map(fn ($v): string => strtolower(trim((string) $v)))->flip();

        return compact('existingNames');
    }

    protected static function validateAndNormalize(array &$data, array $lookups): array
    {
        $errors = [];

        $data['name'] = trim($data['name'] ?? '');
        if ($data['name'] === '') {
            $errors[] = 'Category Name is required (column empty or not mapped).';
        }

        $name = strtolower($data['name']);
        if ($name !== '' && isset($lookups['existingNames'][$name])) {
            $errors[] = 'Fee Category ['.$data['name'].'] already exists in this school.';
        }

        $data['description'] = trim($data['description'] ?? '');

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        FeeCategory::create([
            'school_id' => $schoolId,
            'name' => $data['name'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
        ]);

        $lookups['existingNames'][strtolower(trim($data['name']))] = true;
    }
}
