<?php

namespace App\Services\Csv;

use App\Models\User;
use Modules\Academics\Models\Course;

class CourseCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'name' => [
                'label' => __('Course Name'),
                'required' => true,
                'guesses' => ['Course Name', 'Name', 'Class Level'],
                'example' => 'Form 1',
            ],
            'code' => [
                'label' => __('Course Code'),
                'required' => true,
                'guesses' => ['Code', 'Course Code'],
                'example' => 'F1',
            ],
            'teacher' => [
                'label' => __('Teacher Name'),
                'required' => false,
                'guesses' => ['Teacher', 'Teacher Name'],
                'example' => 'Tendai Mutasa',
            ],
            'workflow_status' => [
                'label' => __('Status'),
                'required' => false,
                'guesses' => ['Status', 'Workflow Status'],
                'default' => 'pending',
                'in' => ['pending', 'in_progress', 'complete'],
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return ['Course Name', 'Course Code', 'Teacher', 'Status'];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = Course::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with('teacher')
            ->orderBy('id');

        $lastId = 0;

        do {
            $courses = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($courses->isEmpty()) {
                break;
            }

            foreach ($courses as $course) {
                yield [
                    $course->name,
                    $course->code,
                    $course->teacher?->name,
                    $course->workflow_status,
                ];
            }

            $lastId = $courses->last()->id;
        } while (true);
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $lookups = [
            'users' => User::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn ($u): string => strtolower(trim($u->name))),
            'existingNames' => Course::withoutTenantScope()->where('school_id', $schoolId)->pluck('name')
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

        foreach (['name', 'code'] as $required) {
            $data[$required] = trim($data[$required] ?? '');

            if ($data[$required] === '') {
                $errors[] = static::columns()[$required]['label'].' is required (column empty or not mapped).';
            }
        }

        $name = strtolower(trim($data['name'] ?? ''));
        if ($name !== '' && isset($lookups['existingNames'][$name])) {
            $errors[] = 'Course ['.$data['name'].'] already exists in this school.';
        }

        $data['workflow_status'] = strtolower(trim($data['workflow_status'] ?? ''));
        if ($data['workflow_status'] === '') {
            $data['workflow_status'] = 'pending';
        }

        if (! in_array($data['workflow_status'], ['pending', 'in_progress', 'complete'], true)) {
            $errors[] = 'Status must be one of: pending, in_progress, complete.';
        }

        $data['teacher'] = trim($data['teacher'] ?? '');
        $data['_teacher'] = null;

        if ($data['teacher'] !== '') {
            $teacher = $lookups['users'][strtolower($data['teacher'])] ?? null;

            if (! $teacher) {
                $errors[] = 'Teacher ['.$data['teacher'].'] was not found in this school.';
            } else {
                $data['_teacher'] = $teacher;
            }
        }

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        Course::create([
            'school_id' => $schoolId,
            'name' => $data['name'],
            'code' => $data['code'],
            'teacher_id' => $data['_teacher']?->id,
            'workflow_status' => $data['workflow_status'],
        ]);

        $lookups['existingNames'][strtolower(trim($data['name']))] = true;
    }
}
