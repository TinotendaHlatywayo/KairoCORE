<?php

namespace App\Filament\App\Pages\Finance;

use Filament\Pages\Page;
use Modules\Academics\Models\Term;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\HR\Services\PayrollCalculationService;

class FinancialStatementPage extends Page
{
    protected static string $view = 'filament.app.pages.finance.financial-statement';

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Financial Statements';

    public static function getNavigationLabel(): string
    {
        return __('Financial Statements');
    }

    public string $range = 'month'; // day, week, month, year

    public string $bankAccountId = ''; // '' => all accounts combined

    public function mount(): void
    {
        $this->bankAccountId = (string) (request()->query('bank_account') ?? session('finance_bank_account', ''));
    }

    public function updatedBankAccountId(): void
    {
        session(['finance_bank_account' => $this->bankAccountId ?: null]);
    }

    public function getTitle(): string
    {
        return __('School Financial Statement & Cash Flow');
    }

    protected function getViewData(): array
    {
        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id ?? 1;
        $defaultBank = SchoolBankAccount::where('school_id', $schoolId)->where('is_default', true)->first()
            ?? SchoolBankAccount::where('school_id', $schoolId)->first();

        if (! $defaultBank) {
            $defaultBank = SchoolBankAccount::create([
                'school_id' => $schoolId,
                'bank_name' => 'Stanbic Bank Zimbabwe',
                'account_name' => 'School Operating Account',
                'account_number' => '9140001234567',
                'branch_code' => '02',
                'balance' => 0.00,
                'is_active' => true,
                'is_default' => true,
            ]);
        }

        $bankAccounts = SchoolBankAccount::where('school_id', $schoolId)->orderByDesc('is_default')->get();
        $selectedBank = $this->bankAccountId
            ? $bankAccounts->firstWhere('id', (int) $this->bankAccountId)
            : null;

        $startDate = match ($this->range) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };

        $totalRevenue = Payment::where('school_id', $schoolId)
            ->when($this->bankAccountId, SchoolBankAccount::filterClosure((int) $this->bankAccountId, $schoolId))
            ->where('is_refund', false)
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        // Refund rows are stored as negative amounts, so normalise to a positive
        // "amount refunded" figure. Callers render this as a deduction (-$X).
        $totalRefunds = abs((float) Payment::where('school_id', $schoolId)
            ->when($this->bankAccountId, SchoolBankAccount::filterClosure((int) $this->bankAccountId, $schoolId))
            ->where('is_refund', true)
            ->where('created_at', '>=', $startDate)
            ->sum('amount'));

        $totalExpenses = Expense::where('school_id', $schoolId)
            ->when($this->bankAccountId, SchoolBankAccount::filterClosure((int) $this->bankAccountId, $schoolId))
            ->where('expense_date', '>=', $startDate->toDateString())
            ->sum('amount');

        $salariesExpense = app(PayrollCalculationService::class)
            ->payrollExpenseTotal(
                $schoolId,
                $startDate->toDateString(),
                now()->toDateString(),
                $this->bankAccountId ? (int) $this->bankAccountId : null
            );

        $netCashFlow = $totalRevenue - $totalRefunds - $totalExpenses;

        // Opening Bank Balance must reflect what the account(s) held at the
        // START of the reporting period - NOT the live balance. An account
        // created during the period (e.g. a brand-new school) did not exist
        // at period start, so it opened with $0 by definition. For accounts
        // that predate the period we fall back to the recorded balance, since
        // no historical ledger is kept.
        $targetAccounts = $selectedBank ? collect([$selectedBank]) : $bankAccounts;
        $openingBalance = $targetAccounts->sum(function (SchoolBankAccount $account) use ($startDate): float {
            if ($account->created_at && $account->created_at->greaterThanOrEqualTo($startDate)) {
                return 0.0;
            }

            return (float) $account->balance;
        });

        $school = current_tenant();
        $season = $this->currentSeasonLabel();

        return [
            'defaultBank' => $selectedBank ?? $defaultBank,
            'bankAccounts' => $bankAccounts,
            'bankAccountId' => $this->bankAccountId,
            'allAccounts' => $this->bankAccountId === '',
            'openingBalance' => $openingBalance,
            'totalRevenue' => $totalRevenue,
            'totalRefunds' => $totalRefunds,
            'totalExpenses' => $totalExpenses,
            'salariesExpense' => $salariesExpense,
            'netCashFlow' => $netCashFlow,
            'startDate' => $startDate->toDateString(),
            'endDate' => now()->toDateString(),
            'company' => $school?->name ?? config('app.name'),
            'companyTagline' => $school?->motto ?? null,
            'companyAddress' => $school?->physical_address ?? '',
            'companyPhone' => $school?->phone_number ?? $school?->phone ?? null,
            'companyEmail' => $school?->email_address ?? null,
            'season' => $season,
        ];
    }

    /**
     * Resolve the current term/season label (e.g. "Term 1 - 2026") so the
     * official statement header matches the reporting period.
     */
    protected function currentSeasonLabel(): ?string
    {
        $schoolId = current_tenant()?->id ?? 1;

        $term = Term::where('school_id', $schoolId)
            ->where('is_active', true)
            ->with('academicYear')
            ->orderByDesc('id')
            ->first();

        if (! $term) {
            return null;
        }

        return trim(implode(' | ', array_filter([
            $term->name,
            $term->academicYear?->name,
        ])));
    }
}
