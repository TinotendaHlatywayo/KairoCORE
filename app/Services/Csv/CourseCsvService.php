<?php

namespace App\Services\Csv;

use App\Models\User;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;

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
            'section' => [
                'label' => __('Class / Stream'),
                'required' => false,
                'guesses' => ['Class', 'Class / Stream', 'Section', 'Stream', 'Class Name', 'Stream / Class'],
                'example' => 'North',
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
        return ['Course Name', 'Course Code', 'Class / Stream', 'Teacher', 'Status'];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = Course::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with(['teacher', 'sections'])
            ->orderBy('id');

        $lastId = 0;

        do {
            $courses = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($courses->isEmpty()) {
                break;
            }

            foreach ($courses as $course) {
                if ($course->sections->isEmpty()) {
                    yield [
                        $course->name,
                        $course->code,
                        '',
                        $course->teacher?->name,
                        $course->workflow_status,
                    ];

                    continue;
                }

                foreach ($course->sections as $section) {
                    yield [
                        $course->name,
                        $course->code,
                        $section->name,
                        $course->teacher?->name,
                        $course->workflow_status,
                    ];
                }
            }

            $lastId = $courses->last()->id;
        } while (true);
    }

    /**
     * Example rows written under the template header when the school has no
     * course data yet. For primary schools the whole school is pre-filled as
     * ECD A/B (Blue, Red) and Grade 1-7 (North, South); for secondary/high it
     * is Form 1-4 (North, South) and Form 5 & 6 (Arts, Commercials, Sciences).
     */
    protected static function templateRows(): array
    {
        $isPrimary = static::schoolType() === 'primary';
        $combos = [];

        if ($isPrimary) {
            foreach (['ECD A', 'ECD B'] as $index => $level) {
                $code = 'PR-ECD-'.chr(65 + $index);

                foreach (['Blue', 'Red'] as $class) {
                    $combos[] = [$level, 'ECD'.chr(65 + $index), $class, 'pending'];
                }
            }

            for ($grade = 1; $grade <= 7; $grade++) {
                foreach (['North', 'South'] as $class) {
                    $combos[] = ['Grade '.$grade, 'G'.$grade, $class, 'pending'];
                }
            }
        } else {
            for ($form = 1; $form <= 4; $form++) {
                foreach (['North', 'South'] as $class) {
                    $combos[] = ['Form '.$form, 'F'.$form, $class, 'pending'];
                }
            }

            foreach ([5, 6] as $form) {
                foreach (['Arts', 'Commercials', 'Sciences'] as $class) {
                    $combos[] = ['Form '.$form, 'F'.$form, $class, 'pending'];
                }
            }
        }

        return array_map(
            fn (array $combo): array => [
                'name' => $combo[0],
                'code' => $combo[1],
                'section' => $combo[2],
                'teacher' => '',
                'workflow_status' => $combo[3],
            ],
            $combos,
        );
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null): array
    {
        $courses = Course::withoutTenantScope()->where('school_id', $schoolId)->get();
        $sections = Course::withoutTenantScope()->where('school_id', $schoolId)->with('sections')->get();

        $existingSections = [];
        foreach ($sections as $course) {
            foreach ($course->sections as $section) {
                $existingSections[strtolower(trim($course->name)).'|'.strtolower(trim($section->name))] = true;
            }
        }

        $lookups = [
            'users' => User::withoutTenantScope()->where('school_id', $schoolId)->get()
                ->keyBy(fn ($u): string => strtolower(trim($u->name))),
            'courses' => $courses->keyBy(fn (Course $c): string => strtolower(trim((string) $c->name))),
            'existingSections' => $existingSections,
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

        $data['section'] = trim($data['section'] ?? '');
        $nameKey = strtolower($data['name'] ?? '');

        if ($nameKey !== '') {
            if ($data['section'] === '') {
                if (isset($lookups['courses'][$nameKey])) {
                    $errors[] = 'Course ['.$data['name'].'] already exists in this school.';
                }
            } elseif (isset($lookups['existingSections'][$nameKey.'|'.strtolower($data['section'])])) {
                $errors[] = 'Class ['.$data['section'].'] already exists under course ['.$data['name'].'].';
            }
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
        $nameKey = strtolower(trim($data['name']));
        $section = trim($data['section'] ?? '');
        $course = $lookups['courses'][$nameKey] ?? null;

        if (! $course) {
            $course = Course::withoutTenantScope()->create([
                'school_id' => $schoolId,
                'name' => $data['name'],
                'code' => $data['code'],
                'teacher_id' => $data['_teacher']?->id,
                'workflow_status' => $data['workflow_status'],
            ]);

            $lookups['courses'][$nameKey] = $course;
        }

        if ($section !== '') {
            $sectionKey = $nameKey.'|'.strtolower($section);

            if (! isset($lookups['existingSections'][$sectionKey])) {
                Section::withoutTenantScope()->create([
                    'school_id' => $schoolId,
                    'course_id' => $course->id,
                    'name' => $section,
                ]);

                $lookups['existingSections'][$sectionKey] = true;
            }
        }
    }
}
