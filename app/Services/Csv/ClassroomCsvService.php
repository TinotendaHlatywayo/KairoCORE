<?php

namespace App\Services\Csv;

use Modules\Academics\Models\Classroom;

class ClassroomCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'name' => [
                'label' => __('Classroom Name'),
                'required' => true,
                'guesses' => ['Name', 'Classroom Name', 'Room Name'],
                'example' => 'Room 12',
            ],
            'capacity' => [
                'label' => __('Capacity'),
                'required' => false,
                'guesses' => ['Capacity'],
                'example' => '40',
            ],
            'location' => [
                'label' => __('Location'),
                'required' => false,
                'guesses' => ['Location', 'Building'],
                'example' => 'Science Block',
            ],
            'description' => [
                'label' => __('Description'),
                'required' => false,
                'guesses' => ['Description', 'Notes'],
            ],
            'features' => [
                'label' => __('Features'),
                'required' => false,
                'guesses' => ['Features'],
                'example' => 'projector, whiteboard',
                'help' => 'comma-separated from projector/whiteboard/computers/science_lab/wifi/ac',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return ['Classroom Name', 'Capacity', 'Location', 'Description', 'Features'];
    }

    /**
     * Example classroom rows written under the template header, one per level
     * and class across the whole school:
     *
     * - Primary: ECD A (Blue, Red), ECD B (Blue, Red), Grade 1-7 (North, South)
     * - Secondary: Form 1-4 (North, South), Form 5 & 6 (Arts, Commercials, Sciences)
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function templateRows(): array
    {
        $isPrimary = static::schoolType() === 'primary';

        $names = [];

        if ($isPrimary) {
            foreach (['ECD A', 'ECD B'] as $level) {
                foreach (['Blue', 'Red'] as $class) {
                    $names[] = $level.' '.$class;
                }
            }

            for ($grade = 1; $grade <= 7; $grade++) {
                foreach (['North', 'South'] as $class) {
                    $names[] = 'Grade '.$grade.' '.$class;
                }
            }
        } else {
            for ($form = 1; $form <= 4; $form++) {
                foreach (['North', 'South'] as $class) {
                    $names[] = 'Form '.$form.' '.$class;
                }
            }

            foreach ([5, 6] as $form) {
                foreach (['Arts', 'Commercials', 'Sciences'] as $class) {
                    $names[] = 'Form '.$form.' '.$class;
                }
            }
        }

        return array_map(
            fn (string $name): array => [
                'name' => $name,
                'capacity' => '40',
                'location' => 'Main Block',
                'description' => '',
                'features' => '',
            ],
            $names,
        );
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = Classroom::withoutTenantScope()->where('school_id', $schoolId)->orderBy('id');

        $lastId = 0;

        do {
            $classrooms = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($classrooms->isEmpty()) {
                break;
            }

            foreach ($classrooms as $classroom) {
                yield [
                    $classroom->name,
                    $classroom->capacity,
                    $classroom->location,
                    $classroom->description,
                    is_array($classroom->features) ? implode(', ', $classroom->features) : null,
                ];
            }

            $lastId = $classrooms->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $lookups = [
            'existingNames' => Classroom::withoutTenantScope()->where('school_id', $schoolId)->pluck('name')
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
            $errors[] = 'Classroom Name is required (column empty or not mapped).';
        }

        $name = strtolower(trim($data['name']));
        if ($name !== '' && isset($lookups['existingNames'][$name])) {
            $errors[] = 'Classroom ['.$data['name'].'] already exists in this school.';
        }

        $capacity = trim($data['capacity'] ?? '');
        if ($capacity !== '' && ! is_numeric($capacity)) {
            $errors[] = 'Capacity ['.$capacity.'] must be a number.';
        }

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        Classroom::create([
            'school_id' => $schoolId,
            'name' => $data['name'],
            'capacity' => $data['capacity'] !== '' ? (int) $data['capacity'] : null,
            'location' => $data['location'] !== '' ? $data['location'] : null,
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'features' => array_filter(array_map('trim', explode(',', $data['features']))),
        ]);

        $lookups['existingNames'][strtolower(trim($data['name']))] = true;
    }
}
