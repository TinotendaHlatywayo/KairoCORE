<?php

namespace App\Services\Csv;

use Modules\Finance\Models\Account;
use Modules\Finance\Models\ExpenseCategory;

class ExpenseCategoryCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'name' => [
                'label' => __('Category Name'),
                'required' => true,
                'guesses' => ['Category Name', 'Name'],
                'example' => 'Utilities',
            ],
            'account' => [
                'label' => __('GL Account'),
                'required' => false,
                'guesses' => ['GL Account', 'Account'],
                'example' => 'Utilities Expense',
            ],
            'description' => [
                'label' => __('Description'),
                'required' => false,
                'guesses' => ['Description'],
                'example' => 'Water, electricity and waste services',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return ['Category Name', 'GL Account', 'Description'];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = ExpenseCategory::withoutTenantScope()
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
        $existingNames = ExpenseCategory::withoutTenantScope()->where('school_id', $schoolId)->pluck('name')
            ->map(fn ($v): string => strtolower(trim((string) $v)))->flip();

        $accounts = Account::withoutTenantScope()->where('school_id', $schoolId)->where('type', 'expense')->get()
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
            $errors[] = 'Expense Category ['.$data['name'].'] already exists in this school.';
        }

        $data['account'] = trim($data['account'] ?? '');
        if ($data['account'] !== '') {
            $account = $lookups['accounts'][strtolower($data['account'])] ?? null;

            if (! $account) {
                $errors[] = 'GL Account ['.$data['account'].'] was not found in this school. Available expense accounts: '.($lookups['accounts']->pluck('name')->implode(', ') ?: 'none').'.';
            } else {
                $data['_account'] = $account;
            }
        } else {
            $data['_account'] = null;
        }

        $data['description'] = trim($data['description'] ?? '');

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        ExpenseCategory::create([
            'school_id' => $schoolId,
            'name' => $data['name'],
            'account_id' => $data['_account']?->id,
            'description' => $data['description'] !== '' ? $data['description'] : null,
        ]);

        $lookups['existingNames'][strtolower(trim($data['name']))] = true;
    }
}
