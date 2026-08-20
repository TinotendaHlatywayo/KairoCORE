<?php

namespace App\Services\Csv;

use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Term;
use Modules\Finance\Models\FeeCategory;
use Modules\Finance\Models\FeeStructure;

class FeeStructureCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'fee_category' => [
                'label' => __('Fee Category'),
                'required' => true,
                'guesses' => ['Fee Category', 'Category'],
                'example' => 'Tuition',
            ],
            'scope_type' => [
                'label' => __('Scope'),
                'required' => false,
                'guesses' => ['Scope', 'Scope Type'],
                'example' => 'single',
                'default' => 'single',
                'in' => ['all', 'form_1_4', 'form_5_6', 'grade_1_7', 'ecd', 'single'],
            ],
            'course' => [
                'label' => __('Course'),
                'required' => false,
                'guesses' => ['Course', 'Class Level'],
                'example' => 'Form 1',
            ],
            'academic_year' => [
                'label' => __('Academic Year'),
                'required' => true,
                'guesses' => ['Academic Year', 'Year'],
                'example' => '2026',
            ],
            'term' => [
                'label' => __('Term'),
                'required' => true,
                'guesses' => ['Term'],
                'example' => 'Term 1',
            ],
            'currency' => [
                'label' => __('Currency'),
                'required' => false,
                'guesses' => ['Currency'],
                'example' => 'USD',
                'default' => 'USD',
                'in' => ['USD', 'ZiG'],
            ],
            'amount' => [
                'label' => __('Amount'),
                'required' => true,
                'guesses' => ['Amount', 'Fee Amount'],
                'example' => '1200.00',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return ['Fee Category', 'Scope', 'Course', 'Academic Year', 'Term', 'Currency', 'Amount'];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = FeeStructure::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with(['feeCategory', 'course', 'academicYear', 'term'])
            ->orderBy('id');

        $lastId = 0;

        do {
            $structures = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($structures->isEmpty()) {
                break;
            }

            foreach ($structures as $structure) {
                yield [
                    $structure->feeCategory?->name,
                    $structure->scope_type,
                    $structure->course?->name,
                    $structure->academicYear?->name,
                    $structure->term?->name,
                    $structure->currency,
                    $structure->amount,
                ];
            }

            $lastId = $structures->last()->id;
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
        $feeCategories = FeeCategory::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($c): string => strtolower(trim($c->name)));

        $courses = Course::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($c): string => strtolower(trim($c->name)));

        $years = AcademicYear::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($y): string => strtolower(trim($y->name)));

        $termsByYear = [];
        foreach (Term::withoutTenantScope()->where('school_id', $schoolId)->get() as $term) {
            $termsByYear[$term->academic_year_id][strtolower(trim($term->name))] = $term;
        }

        $existingKeys = FeeStructure::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->map(fn (FeeStructure $structure): string => implode('|', [
                $structure->fee_category_id,
                $structure->scope_type,
                $structure->academic_year_id,
                $structure->term_id,
            ]))
            ->flip();

        return compact('feeCategories', 'courses', 'years', 'termsByYear', 'existingKeys');
    }

    protected static function validateAndNormalize(array &$data, array $lookups): array
    {
        $errors = [];

        $data['fee_category'] = trim($data['fee_category'] ?? '');
        if ($data['fee_category'] === '') {
            $errors[] = 'Fee Category is required (column empty or not mapped).';
        }

        $category = $lookups['feeCategories'][strtolower($data['fee_category'])] ?? null;
        if ($data['fee_category'] !== '' && ! $category) {
            $errors[] = 'Fee Category ['.$data['fee_category'].'] was not found in this school. Available categories: '.($lookups['feeCategories']->pluck('name')->implode(', ') ?: 'none').'.';
        } else {
            $data['_feeCategory'] = $category;
        }

        $data['academic_year'] = trim($data['academic_year'] ?? '');
        if ($data['academic_year'] === '') {
            $errors[] = 'Academic Year is required (column empty or not mapped).';
        }

        $year = $lookups['years'][strtolower($data['academic_year'])] ?? null;
        if ($data['academic_year'] !== '' && ! $year) {
            $errors[] = 'Academic Year ['.$data['academic_year'].'] was not found in this school. Available years: '.($lookups['years']->pluck('name')->implode(', ') ?: 'none').'.';
        } else {
            $data['_year'] = $year;
        }

        $data['scope_type'] = strtolower(trim($data['scope_type'] ?? ''));
        if ($data['scope_type'] === '') {
            $data['scope_type'] = 'single';
        }

        if (! in_array($data['scope_type'], ['all', 'form_1_4', 'form_5_6', 'grade_1_7', 'ecd', 'single'], true)) {
            $errors[] = 'Scope must be one of: all, form_1_4, form_5_6, grade_1_7, ecd, single.';
        }

        $data['term'] = trim($data['term'] ?? '');
        if ($data['term'] === '') {
            $errors[] = 'Term is required (column empty or not mapped).';
        }

        if ($data['term'] !== '' && $year) {
            $term = $lookups['termsByYear'][$year->id][strtolower($data['term'])] ?? null;

            if (! $term) {
                $available = collect($lookups['termsByYear'][$year->id] ?? [])->pluck('name')->implode(', ');
                $errors[] = 'Term ['.$data['term'].'] was not found for Academic Year ['.$year->name.']. Available terms: '.($available ?: 'none').'.';
            } else {
                $data['_term'] = $term;
            }
        }

        $data['course'] = trim($data['course'] ?? '');
        if ($data['scope_type'] === 'single' && $data['course'] === '') {
            $errors[] = 'Course is required when Scope is "single" (column empty or not mapped).';
        }

        if ($data['scope_type'] === 'single' && $data['course'] !== '') {
            $course = $lookups['courses'][strtolower($data['course'])] ?? null;

            if (! $course) {
                $errors[] = 'Course ['.$data['course'].'] was not found in this school. Available courses: '.($lookups['courses']->pluck('name')->implode(', ') ?: 'none').'.';
            } else {
                $data['_course'] = $course;
            }
        } else {
            $data['_course'] = null;
        }

        $data['currency'] = strtoupper(trim($data['currency'] ?? ''));
        if ($data['currency'] === '') {
            $data['currency'] = 'USD';
        }

        if (! in_array($data['currency'], ['USD', 'ZiG'], true)) {
            $errors[] = 'Currency must be one of: USD, ZiG.';
        }

        $data['amount'] = trim($data['amount'] ?? '');
        if ($data['amount'] === '') {
            $errors[] = 'Amount is required (column empty or not mapped).';
        } elseif (! is_numeric($data['amount']) || (float) $data['amount'] <= 0) {
            $errors[] = 'Amount ['.$data['amount'].'] must be a number greater than zero.';
        }

        if (empty($errors) && $data['_feeCategory'] && $data['_year'] && $data['_term']) {
            $key = implode('|', [$data['_feeCategory']->id, $data['scope_type'], $data['_year']->id, $data['_term']->id]);

            if (isset($lookups['existingKeys'][$key])) {
                $errors[] = 'Fee Structure already exists for this category, scope, academic year and term in this school.';
            } else {
                $data['_dedupeKey'] = $key;
            }
        }

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        FeeStructure::create([
            'school_id' => $schoolId,
            'fee_category_id' => $data['_feeCategory']->id,
            'scope_type' => $data['scope_type'],
            'course_id' => $data['_course']?->id,
            'academic_year_id' => $data['_year']->id,
            'term_id' => $data['_term']->id,
            'currency' => $data['currency'],
            'amount' => (float) $data['amount'],
        ]);

        if (isset($data['_dedupeKey'])) {
            $lookups['existingKeys'][$data['_dedupeKey']] = true;
        }
    }
}
