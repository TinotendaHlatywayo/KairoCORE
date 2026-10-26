<?php

namespace Modules\Finance\Services;

use App\Services\Csv\CsvBulkService;
use Modules\Finance\Models\FinancialStatementEntry;
use Modules\Finance\Models\SchoolBankAccount;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Bulk import for historic financial-statement data.
 *
 * Schools often start using Kairo CORE part-way through their life, while the
 * prior three years of income and expenditure live in old registers or
 * spreadsheets. These entries are stored separately from student invoices and
 * payments so importing a historical summary never invents invoices, students
 * or receipts. The analytics engine folds them into the official cash-flow
 * statement alongside live transactions.
 */
class FinancialStatementCsvService extends CsvBulkService
{
    public static function columns(): array
    {
        return [
            'type' => [
                'label' => __('Transaction Type'),
                'required' => true,
                'guesses' => ['Transaction Type', 'Record Type', 'Type', 'Entry Type'],
                'example' => FinancialStatementEntry::TYPE_FEE_INCOME,
                'in' => array_keys(FinancialStatementEntry::typeLabels()),
            ],
            'date' => [
                'label' => __('Date'),
                'required' => true,
                'guesses' => ['Date', 'Transaction Date', 'Entry Date'],
                'example' => '2024-02-15',
                'date' => true,
            ],
            'description' => [
                'label' => __('Description'),
                'required' => true,
                'guesses' => ['Description', 'Details', 'Narration'],
                'example' => 'Term 1 Tuition Fees',
            ],
            'category' => [
                'label' => __('Category'),
                'required' => false,
                'guesses' => ['Category', 'Income Category', 'Expense Category'],
                'example' => 'Tuition Fees',
            ],
            'amount' => [
                'label' => __('Amount (USD)'),
                'required' => true,
                'guesses' => ['Amount', 'Amount USD', 'Total', 'Value', 'Amount ($)'],
                'example' => '1200.00',
            ],
            'bank_account' => [
                'label' => __('Bank Account'),
                'required' => false,
                'guesses' => ['Bank Account', 'Account', 'Bank'],
                'example' => '',
            ],
            'reference' => [
                'label' => __('Reference'),
                'required' => false,
                'guesses' => ['Reference', 'Reference Number', 'Reference No'],
                'example' => '',
            ],
            'notes' => [
                'label' => __('Notes'),
                'required' => false,
                'guesses' => ['Notes', 'Memo', 'Comment'],
                'example' => '',
            ],
        ];
    }

    /**
     * Example records covering the last three calendar years. The years move
     * with the clock so the template never asks a school to import data that
     * looks stale.
     *
     * @return array<int, array<int, string>>
     */
    protected static function templateRows(): array
    {
        $bank = static::exampleSchoolValue(
            SchoolBankAccount::class,
            fn (SchoolBankAccount $account): string => (string) $account->bank_name
        );

        $currentYear = (int) now()->year;
        $rows = [];

        for ($year = $currentYear - 2; $year <= $currentYear; $year++) {
            $offset = $year - ($currentYear - 2);
            $fee = number_format(1200 + ($offset * 100), 2, '.', '');
            $salary = number_format(800 + ($offset * 50), 2, '.', '');
            $other = number_format(250 + ($offset * 25), 2, '.', '');
            $refund = number_format(40 + ($offset * 5), 2, '.', '');
            $utilities = number_format(180 + ($offset * 15), 2, '.', '');

            $rows[] = [FinancialStatementEntry::TYPE_FEE_INCOME, $year.'-02-15', 'Term 1 Tuition Fees', 'Tuition Fees', $fee, $bank, 'HIST-'.$year.'-FEE-1', 'Historic fee collection'];
            $rows[] = [FinancialStatementEntry::TYPE_EXPENSE, $year.'-03-28', 'Term 1 Staff Salaries', 'Payroll & Compensation', $salary, $bank, 'HIST-'.$year.'-EXP-1', 'Historic payroll expense'];
            $rows[] = [FinancialStatementEntry::TYPE_OTHER_INCOME, $year.'-06-10', 'Bus Hire and Donations', 'Other Income', $other, $bank, 'HIST-'.$year.'-INC-1', 'Historic non-fee income'];
            $rows[] = [FinancialStatementEntry::TYPE_REFUND, $year.'-07-20', 'Fee Refund', 'Refunds', $refund, $bank, 'HIST-'.$year.'-REF-1', 'Historic refund issued'];
            $rows[] = [FinancialStatementEntry::TYPE_FEE_INCOME, $year.'-09-15', 'Term 3 Tuition Fees', 'Tuition Fees', $fee, $bank, 'HIST-'.$year.'-FEE-2', 'Historic fee collection'];
            $rows[] = [FinancialStatementEntry::TYPE_EXPENSE, $year.'-10-05', 'Utilities', 'Utilities', $utilities, $bank, 'HIST-'.$year.'-EXP-2', 'Historic utility expense'];
        }

        return $rows;
    }

    protected static function appendTemplateSheets(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Template Guide');

        $guide = [
            ['Financial Statement Import Guide'],
            [''],
            ['Transaction Type', 'Use it for'],
            [FinancialStatementEntry::TYPE_FEE_INCOME, 'Historic school-fee collections. Shows under Total Fees Collected.'],
            [FinancialStatementEntry::TYPE_OTHER_INCOME, 'Donations, bus hire, rentals and other non-fee income.'],
            [FinancialStatementEntry::TYPE_REFUND, 'Money refunded to parents in the historic period.'],
            [FinancialStatementEntry::TYPE_EXPENSE, 'Salaries, utilities and every other historic expense.'],
            [''],
            ['Tip', 'Amounts are always positive; the system knows income from expenses and refunds.'],
            ['Tip', 'Leave Bank Account blank when the old record cannot be tied to one account.'],
            ['Tip', 'Delete the example rows before importing your own records.'],
        ];

        $sheet->fromArray($guide, null, 'A1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3:B3')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(24);
        $sheet->getColumnDimension('B')->setWidth(95);
        $sheet->getStyle('B1:B9')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
    }

    public static function exportHeaders(): array
    {
        return array_column(static::columns(), 'label');
    }

    public static function exportRows(int $schoolId): iterable
    {
        $query = FinancialStatementEntry::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with('bankAccount')
            ->orderBy('entry_date')
            ->orderBy('id');

        foreach ($query->cursor() as $entry) {
            yield [
                $entry->type,
                $entry->entry_date?->format('Y-m-d'),
                $entry->description,
                $entry->category,
                $entry->amount,
                $entry->bankAccount?->bank_name,
                $entry->reference,
                $entry->notes,
            ];
        }
    }

    public static function import(string $filePath, int $schoolId, array $columnMap, ?callable $onProgress = null, array $context = []): array
    {
        $lookups = static::buildLookups($schoolId);
        $lookups['requester_id'] = $context['requester_id'] ?? auth()->id();

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
        $bankAccounts = [];

        SchoolBankAccount::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get()
            ->each(function (SchoolBankAccount $account) use (&$bankAccounts): void {
                $keys = array_filter([
                    $account->bank_name,
                    $account->account_name,
                    trim(implode(' — ', array_filter([$account->bank_name, $account->account_name]))),
                    trim(implode(' - ', array_filter([$account->bank_name, $account->account_name]))),
                ]);

                foreach ($keys as $key) {
                    $bankAccounts[static::normaliseLookup($key)] = $account;
                }
            });

        return compact('bankAccounts');
    }

    protected static function validateAndNormalize(array &$data, array $lookups): array
    {
        $errors = [];

        $rawType = trim((string) ($data['type'] ?? ''));
        $type = static::normaliseLookup($rawType);
        $type = match ($type) {
            'fee', 'fees', 'school_fee', 'school_fees', 'tuition', 'tuition_fees', 'fee_income' => FinancialStatementEntry::TYPE_FEE_INCOME,
            'income', 'other', 'other_income', 'non_fee_income' => FinancialStatementEntry::TYPE_OTHER_INCOME,
            'refund', 'refunds', 'refund_issued' => FinancialStatementEntry::TYPE_REFUND,
            'expense', 'expenses', 'expenditure', 'cost', 'costs' => FinancialStatementEntry::TYPE_EXPENSE,
            default => null,
        };

        if ($type === null) {
            $errors[] = 'Transaction Type ['.$rawType.'] is not valid. Use fee_income, other_income, refund or expense.';
        } else {
            $data['type'] = $type;
        }

        $date = static::toDate($data['date'] ?? null);
        if ($date === null) {
            $errors[] = 'Date is required and must be a valid date (for example 2025-03-15).';
        } else {
            $data['date'] = $date;
        }

        $data['description'] = trim((string) ($data['description'] ?? ''));
        if ($data['description'] === '') {
            $errors[] = 'Description is required.';
        }

        $data['category'] = trim((string) ($data['category'] ?? '')) ?: null;

        $amount = trim((string) ($data['amount'] ?? ''));
        $amount = str_replace([',', '$', ' '], '', $amount);

        if ($amount === '' || ! is_numeric($amount) || (float) $amount <= 0) {
            $errors[] = 'Amount ['.($data['amount'] ?? '').'] must be a positive number.';
        } else {
            $data['amount'] = (float) $amount;
        }

        $bankAccount = trim((string) ($data['bank_account'] ?? ''));
        if ($bankAccount !== '') {
            $account = $lookups['bankAccounts'][static::normaliseLookup($bankAccount)] ?? null;

            if (! $account) {
                $available = collect($lookups['bankAccounts'])->pluck('bank_name')->unique()->implode(', ');

                $errors[] = 'Bank Account ['.$bankAccount.'] was not found in this school. Available accounts: '.($available ?: 'none').'.';
            } else {
                $data['_bankAccountId'] = $account->id;
            }
        } else {
            $data['_bankAccountId'] = null;
        }

        $data['reference'] = trim((string) ($data['reference'] ?? '')) ?: null;
        $data['notes'] = trim((string) ($data['notes'] ?? '')) ?: null;

        return $errors;
    }

    protected static function createRow(array $data, int $schoolId, array &$lookups): void
    {
        FinancialStatementEntry::create([
            'school_id' => $schoolId,
            'bank_account_id' => $data['_bankAccountId'] ?? null,
            'entry_date' => $data['date'],
            'type' => $data['type'],
            'category' => $data['category'] ?? null,
            'description' => $data['description'],
            'amount' => $data['amount'],
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'source' => 'import',
            'created_by' => $lookups['requester_id'] ?? auth()->id(),
        ]);
    }

    protected static function normaliseLookup(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower($value)), '_');
    }
}
