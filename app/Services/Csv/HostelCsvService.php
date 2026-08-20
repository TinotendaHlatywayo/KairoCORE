<?php

namespace App\Services\Csv;

use Modules\Hostels\Models\Hostel;

class HostelCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'name' => [
                'label' => __('Hostel Name'),
                'required' => true,
                'guesses' => ['Hostel Name', 'Name'],
                'example' => 'Mbare Hostel',
            ],
            'type' => [
                'label' => __('Type'),
                'required' => true,
                'guesses' => ['Type'],
                'example' => 'boys',
                'in' => ['boys', 'girls', 'mixed'],
            ],
            'capacity' => [
                'label' => __('Capacity'),
                'required' => true,
                'guesses' => ['Capacity'],
                'example' => '120',
            ],
            'status' => [
                'label' => __('Status'),
                'required' => false,
                'guesses' => ['Status'],
                'example' => 'active',
                'default' => 'active',
                'in' => ['active', 'maintenance', 'inactive'],
            ],
            'description' => [
                'label' => __('Description'),
                'required' => false,
                'guesses' => ['Description'],
                'example' => 'Senior boys residence',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return [
            'Hostel Name', 'Type', 'Capacity', 'Status', 'Description',
        ];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = Hostel::withoutTenantScope()->where('school_id', $schoolId)->orderBy('id');

        $lastId = 0;

        do {
            $hostels = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($hostels->isEmpty()) {
                break;
            }

            foreach ($hostels as $hostel) {
                yield [
                    $hostel->name,
                    $hostel->type,
                    $hostel->capacity,
                    $hostel->status,
                    $hostel->description,
                ];
            }

            $lastId = $hostels->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $lookups = [
            'existingNames' => Hostel::withoutTenantScope()->where('school_id', $schoolId)->pluck('name')
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

        foreach (['name', 'type', 'capacity'] as $required) {
            $data[$required] = trim($data[$required] ?? '');

            if ($data[$required] === '') {
                $errors[] = static::columns()[$required]['label'].' is required (column empty or not mapped).';
            }
        }

        $name = strtolower($data['name'] ?? '');
        if ($name !== '' && isset($lookups['existingNames'][$name])) {
            $errors[] = 'Hostel ['.$data['name'].'] already exists in this school.';
        }

        $data['type'] = strtolower($data['type'] ?? '');
        if ($data['type'] !== '' && ! in_array($data['type'], ['boys', 'girls', 'mixed'], true)) {
            $errors[] = 'Type must be one of: boys, girls, mixed.';
        }

        $capacity = trim($data['capacity'] ?? '');
        if ($capacity !== '' && ! is_numeric($capacity)) {
            $errors[] = 'Capacity ['.$capacity.'] must be a number.';
        }

        $data['status'] = strtolower(trim($data['status'] ?? ''));
        if ($data['status'] !== '' && ! in_array($data['status'], ['active', 'maintenance', 'inactive'], true)) {
            $errors[] = 'Status must be one of: active, maintenance, inactive.';
        }

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        Hostel::create([
            'school_id' => $schoolId,
            'name' => $data['name'],
            'type' => $data['type'],
            'capacity' => (int) $data['capacity'],
            'status' => $data['status'] !== '' ? $data['status'] : 'active',
            'description' => $data['description'] !== '' ? $data['description'] : null,
        ]);

        $lookups['existingNames'][strtolower(trim($data['name']))] = true;
    }
}
