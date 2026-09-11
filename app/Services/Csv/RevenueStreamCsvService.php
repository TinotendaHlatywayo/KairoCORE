<?php

namespace App\Services\Csv;

use Modules\Finance\Models\RevenueCategory;
use Modules\Finance\Models\RevenueStream;
use Modules\Finance\Models\SchoolBankAccount;

class RevenueStreamCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'revenue_category' => [
                'label' => __('Revenue Category'),
                'required' => true,
                'guesses' => ['Revenue Category', 'Category'],
                'example' => 'Tuition',
            ],
            'name' => [
                'label' => __('Stream Name'),
                'required' => true,
                'guesses' => ['Stream Name', 'Name'],
                'example' => 'Term 1 Tuition',
            ],
            'default_amount' => [
                'label' => __('Default Amount'),
                'required' => false,
                'guesses' => ['Default Amount', 'Amount'],
                'example' => '420.00',
                'default' => '0',
            ],
            'account' => [
                'label' => __('Bank Account'),
                'required' => false,
                'guesses' => ['Bank Account', 'Account', 'GL Account'],
                'example' => 'CBZ Main Account',
            ],
            'notes' => [
                'label' => __('Notes'),
                'required' => false,
                'guesses' => ['Notes', 'Remarks'],
                'example' => 'Pay via EcoCash to school number 0772 000 000.',
            ],
            'is_active' => [
                'label' => __('Is Active'),
                'required' => false,
                'guesses' => ['Is Active', 'Active'],
                'example' => 'yes',
                'default' => 'yes',
                'in' => ['yes', 'no', 'true', 'false', '1', '0'],
            ],
        ];
    }

    public static function exportHeaders(): array
    {
        return ['Revenue Category', 'Stream Name', 'Default Amount', 'Bank Account', 'Notes', 'Is Active'];
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = RevenueStream::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with(['category', 'account'])
            ->orderBy('id');

        $lastId = 0;

        do {
            $streams = (clone $query)->where('id', '>', $lastId)->orderBy('id')->limit(500)->get();

            if ($streams->isEmpty()) {
                break;
            }

            foreach ($streams as $stream) {
                yield [
                    $stream->category?->name,
                    $stream->name,
                    $stream->default_amount,
                    $stream->account?->bank_name,
                    $stream->notes,
                    $stream->is_active ? 'yes' : 'no',
                ];
            }

            $lastId = $streams->last()->id;
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
        $revenueCategories = RevenueCategory::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($c): string => strtolower(trim($c->name)));

        $existingNames = RevenueStream::withoutTenantScope()->where('school_id', $schoolId)->pluck('name')
            ->map(fn ($v): string => strtolower(trim((string) $v)))->flip();

        $bankAccounts = SchoolBankAccount::withoutTenantScope()->where('school_id', $schoolId)->get()
            ->keyBy(fn ($a): string => strtolower(trim($a->bank_name)));

        return compact('revenueCategories', 'existingNames', 'bankAccounts');
    }

    protected static function validateAndNormalize(array &$data, array $lookups): array
    {
        $errors = [];

        $data['revenue_category'] = trim($data['revenue_category'] ?? '');
        if ($data['revenue_category'] === '') {
            $errors[] = 'Revenue Category is required (column empty or not mapped).';
        }

        $category = $lookups['revenueCategories'][strtolower($data['revenue_category'])] ?? null;
        if ($data['revenue_category'] !== '' && ! $category) {
            $errors[] = 'Revenue Category ['.$data['revenue_category'].'] was not found in this school. Available categories: '.($lookups['revenueCategories']->pluck('name')->implode(', ') ?: 'none').'.';
        } else {
            $data['_revenueCategory'] = $category;
        }

        $data['name'] = trim($data['name'] ?? '');
        if ($data['name'] === '') {
            $errors[] = 'Stream Name is required (column empty or not mapped).';
        }

        $name = strtolower($data['name']);
        if ($name !== '' && isset($lookups['existingNames'][$name])) {
            $errors[] = 'Revenue Stream ['.$data['name'].'] already exists in this school.';
        }

        $data['default_amount'] = trim($data['default_amount'] ?? '');
        if ($data['default_amount'] === '') {
            $data['default_amount'] = '0';
        }

        if (! is_numeric($data['default_amount'])) {
            $errors[] = 'Default Amount ['.$data['default_amount'].'] must be a number.';
        }

        $data['account'] = trim($data['account'] ?? '');
        if ($data['account'] !== '') {
            $account = $lookups['bankAccounts'][strtolower($data['account'])] ?? null;

            if (! $account) {
                $errors[] = 'Bank Account ['.$data['account'].'] was not found in this school. Available bank accounts: '.($lookups['bankAccounts']->pluck('bank_name')->implode(', ') ?: 'none').'.';
            } else {
                $data['_account'] = $account;
            }
        } else {
            $data['_account'] = null;
        }

        $data['notes'] = trim($data['notes'] ?? '') ?: null;

        $active = strtolower(trim($data['is_active'] ?? ''));
        if ($active === '') {
            $data['is_active'] = true;
        } elseif (in_array($active, ['yes', 'no', 'true', 'false', '1', '0'], true)) {
            $data['is_active'] = static::toBoolean($active);
        } else {
            $data['is_active'] = true;
            $errors[] = 'Is Active must be one of: yes, no, true, false, 1, 0.';
        }

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        RevenueStream::create([
            'school_id' => $schoolId,
            'revenue_category_id' => $data['_revenueCategory']->id,
            'name' => $data['name'],
            'default_amount' => (float) $data['default_amount'],
            'account_id' => $data['_account']?->id,
            'notes' => $data['notes'],
            'is_active' => $data['is_active'],
        ]);

        $lookups['existingNames'][strtolower(trim($data['name']))] = true;
    }
}