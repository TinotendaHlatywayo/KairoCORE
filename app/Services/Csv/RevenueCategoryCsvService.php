<?php

namespace App\Services\Csv;

use Modules\Finance\Models\Account;
use Modules\Finance\Models\RevenueCategory;

class RevenueCategoryCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'name' => [
                'label' => __('Category Name'),
                'required' => true,
                'guesses' => ['Category Name', 'Name'],
                'example' => 'School Fees',
            ],
            'account' => [
                'label' => __('GL Account'),
                'required' => false,
                'guesses' => ['GL Account', 'Account'],
                'example' => 'School Fees Revenue',
            ],
            'is_active' => [
                'label' => __('Is Active'),
                'required' => false,
                'guesses' => ['Is Active', 'Active'],
                'example' => 'yes',
                'default' => 'yes',
                'in' => ['yes', 'no', 'true', 'false', '1', '0'],
            ],
            'description' => [
                'label' => __('Description'),
                'required' => false,
                'guesses' => ['Description'],
                'example' => 'Income collected from enrolled students',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return ['Category Name', 'GL Account', 'Is Active', 'Description'];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = RevenueCategory::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with('account')
            ->orderBy('id');

        $lastId = 0;

        do {
            $categories = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($categories->isEmpty()) {
                break;
            }

            foreach ($categories as $category) {
                yield [
                    $category->name,
                    $category->account?->name,
                    $category->is_active ? 'yes' : 'no',
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
        $existingNames = RevenueCategory::withoutTenantScope()->where('school_id', $schoolId)->pluck('name')
            ->map(fn ($v): string => strtolower(trim((string) $v)))->flip();

        $accounts = Account::withoutTenantScope()->where('school_id', $schoolId)->where('type', 'revenue')->get()
            ->keyBy(fn ($a): string => strtolower(trim($a->name)));

        return compact('existingNames', 'accounts');
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
            $errors[] = 'Revenue Category ['.$data['name'].'] already exists in this school.';
        }

        $data['account'] = trim($data['account'] ?? '');
        if ($data['account'] !== '') {
            $account = $lookups['accounts'][strtolower($data['account'])] ?? null;

            if (! $account) {
                $errors[] = 'GL Account ['.$data['account'].'] was not found in this school. Available revenue accounts: '.($lookups['accounts']->pluck('name')->implode(', ') ?: 'none').'.';
            } else {
                $data['_account'] = $account;
            }
        } else {
            $data['_account'] = null;
        }

        $active = strtolower(trim($data['is_active'] ?? ''));
        if ($active === '') {
            $data['is_active'] = true;
        } elseif (in_array($active, ['yes', 'no', 'true', 'false', '1', '0'], true)) {
            $data['is_active'] = static::toBoolean($active);
        } else {
            $data['is_active'] = true;
            $errors[] = 'Is Active must be one of: yes, no, true, false, 1, 0.';
        }

        $data['description'] = trim($data['description'] ?? '');

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        RevenueCategory::create([
            'school_id' => $schoolId,
            'name' => $data['name'],
            'account_id' => $data['_account']?->id,
            'is_active' => $data['is_active'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
        ]);

        $lookups['existingNames'][strtolower(trim($data['name']))] = true;
    }
}
