<?php

namespace App\Services\Csv;

use Modules\Finance\Models\Account;
use Modules\Finance\Models\ExpenseCategory;
use Modules\Finance\Models\ExpenseType;

class ExpenseTypeCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'expense_category' => [
                'label' => __('Expense Category'),
                'required' => true,
                'guesses' => ['Expense Category', 'Category'],
                'example' => 'Utilities',
            ],
            'name' => [
                'label' => __('Expense Type Name'),
                'required' => true,
                'guesses' => ['Expense Type Name', 'Name', 'Type Name'],
                'example' => 'Electricity Bill',
            ],
            'account' => [
                'label' => __('GL Account'),
                'required' => false,
                'guesses' => ['GL Account', 'Account'],
                'example' => 'Utilities Expense',
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return ['Expense Category', 'Expense Type Name', 'GL Account'];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = ExpenseType::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with(['category', 'account'])
            ->orderBy('id');

        $lastId = 0;

        do {
            $types = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($types->isEmpty()) {
                break;
            }

            foreach ($types as $type) {
                yield [
                    $type->category?->name,
                    $type->name,
                    $type->account?->name,
                ];
            }

            $lastId = $types->last()->id;
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
        $expenseCategories = ExpenseCategory::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($c): string => strtolower(trim($c->name)));

        $existingNames = ExpenseType::withoutTenantScope()->where('school_id', $schoolId)->pluck('name')
            ->map(fn ($v): string => strtolower(trim((string) $v)))->flip();

        $accounts = Account::withoutTenantScope()->where('school_id', $schoolId)->where('type', 'expense')->get()
            ->keyBy(fn ($a): string => strtolower(trim($a->name)));

        return compact('expenseCategories', 'existingNames', 'accounts');
    }

    protected static function validateAndNormalize(array &$data, array $lookups): array
    {
        $errors = [];

        $data['expense_category'] = trim($data['expense_category'] ?? '');
        if ($data['expense_category'] === '') {
            $errors[] = 'Expense Category is required (column empty or not mapped).';
        }

        $category = $lookups['expenseCategories'][strtolower($data['expense_category'])] ?? null;
        if ($data['expense_category'] !== '' && ! $category) {
            $errors[] = 'Expense Category ['.$data['expense_category'].'] was not found in this school. Available categories: '.($lookups['expenseCategories']->pluck('name')->implode(', ') ?: 'none').'.';
        } else {
            $data['_expenseCategory'] = $category;
        }

        $data['name'] = trim($data['name'] ?? '');
        if ($data['name'] === '') {
            $errors[] = 'Expense Type Name is required (column empty or not mapped).';
        }

        $name = strtolower($data['name']);
        if ($name !== '' && isset($lookups['existingNames'][$name])) {
            $errors[] = 'Expense Type ['.$data['name'].'] already exists in this school.';
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

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        ExpenseType::create([
            'school_id' => $schoolId,
            'expense_category_id' => $data['_expenseCategory']->id,
            'name' => $data['name'],
            'account_id' => $data['_account']?->id,
        ]);

        $lookups['existingNames'][strtolower(trim($data['name']))] = true;
    }
}
