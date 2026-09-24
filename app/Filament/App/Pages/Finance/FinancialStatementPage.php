<?php

namespace App\Filament\App\Pages\Finance;

use Carbon\Carbon;
use Filament\Pages\Page;
use Modules\Academics\Models\Term;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Finance\Services\FinancialAnalyticsEngine;
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

    public string $range = 'month'; // day, week, month, year, custom

    public string $bankAccountId = ''; // '' => all accounts combined

    public string $startDate = ''; // yyyy-mm-dd (custom range)

    public string $endDate = '';   // yyyy-mm-dd (custom range)

    public function mount(): void
    {
        $this->bankAccountId = (string) (request()->query('bank_account') ?? session('finance_bank_account', ''));
        $this->startDate = (string) request()->query('start_date', '');
        $this->endDate = (string) request()->query('end_date', '');

        if ($this->startDate && $this->endDate) {
            $this->range = 'custom';
        }
    }

    public function updatedBankAccountId(): void
    {
        session(['finance_bank_account' => $this->bankAccountId ?: null]);
    }

    public function updatedRange(): void
    {
        if ($this->range === 'custom') {
            return;
        }

        [$start, $end] = $this->effectiveDateRange();
        $this->startDate = $start->toDateString();
        $this->endDate = $end->toDateString();
    }

    public function updatedStartDate(): void
    {
        if ($this->startDate && ! $this->endDate) {
            $this->endDate = now()->toDateString();
        }

        if ($this->startDate) {
            $this->range = 'custom';
        }
    }

    public function updatedEndDate(): void
    {
        if ($this->endDate) {
            $this->range = 'custom';
        }
    }

    public function getTitle(): string
    {
        return __('School Financial Statement & Cash Flow');
    }

    /**
     * Resolve the reporting window. Explicit custom dates win; otherwise the
     * preset range (day/week/month/year) is used.
     *
     * @return array{Carbon, Carbon}
     */
    protected function effectiveDateRange(): array
    {
        if ($this->startDate && $this->endDate) {
            return [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay(),
            ];
        }

        $end = now();
        $start = match ($this->range) {
            'day' => $end->copy()->subDay(),
            'week' => $end->copy()->subWeek(),
            'month' => $end->copy()->subMonth(),
            'year' => $end->copy()->subYear(),
            default => $end->copy()->subMonth(),
        };

        return [$start, $end];
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

        [$startDate, $endDate] = $this->effectiveDateRange();
        $bankAccountId = $this->bankAccountId ? (int) $this->bankAccountId : null;

        // School fees collected within the reporting window.
        $feeRevenue = Payment::where('school_id', $schoolId)
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId))
            ->where('is_refund', false)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->sum('amount');

        // Refund rows are stored as negative amounts, so normalise to a positive
        // "amount refunded" figure. Callers render this as a deduction (-$X).
        $totalRefunds = abs((float) Payment::where('school_id', $schoolId)
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId))
            ->where('is_refund', true)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->sum('amount'));

        $totalExpenses = Expense::where('school_id', $schoolId)
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId))
            ->where('expense_date', '>=', $startDate->toDateString())
            ->where('expense_date', '<=', $endDate->toDateString())
            ->sum('amount');

        $salariesExpense = app(PayrollCalculationService::class)
            ->payrollExpenseTotal(
                $schoolId,
                $startDate->toDateString(),
                $endDate->toDateString(),
                $bankAccountId
            );

        $engine = app(FinancialAnalyticsEngine::class);
        $revenueStreams = $engine->getRevenueStreamsForPeriod(
            $schoolId,
            $startDate->toDateString(),
            $endDate->toDateString(),
            $bankAccountId
        );
        $revenueStreamTotal = array_sum(array_column($revenueStreams, 'amount'));
        $refundItems = $engine->getRefundsForPeriod(
            $schoolId,
            $startDate->toDateString(),
            $endDate->toDateString(),
            $bankAccountId
        );
        $expenseItems = $engine->getExpensesForPeriod(
            $schoolId,
            $startDate->toDateString(),
            $endDate->toDateString(),
            $bankAccountId
        );
        $feeItems = $engine->getFeeCollectionsForPeriod(
            $schoolId,
            $startDate->toDateString(),
            $endDate->toDateString(),
            $bankAccountId
        );

        // Per-account breakdown for the "All Accounts (Combined)" view.
        $accountBreakdown = $bankAccountId === null
            ? $engine->buildStatementAccountBreakdown(
                $feeItems,
                $revenueStreams,
                $refundItems,
                $expenseItems,
                $bankAccounts->all(),
                (int) $defaultBank->id,
                $startDate->toDateString(),
            )
            : null;

        // Total Revenue is net of refunds, matching the Executive Dashboard's
        // getSummary() (where refunds are stored as negative payment amounts).
        $totalRevenue = $feeRevenue + $revenueStreamTotal - $totalRefunds;
        $netCashFlow = $totalRevenue - $totalExpenses;

        // Split totals for the two movement columns. Only real period movements
        // are summed: inflows = fees + other income, outflows = refunds + expenses.
        $totalInflows = (float) $feeRevenue + (float) $revenueStreamTotal;
        $totalOutflows = (float) $totalRefunds + (float) $totalExpenses;

        // Date(s) of the payroll expense(s) behind the "Of which: Staff Salaries"
        // memo line, so it carries a date like the other statement rows.
        $salaryDates = collect($expenseItems)
            ->where('category', 'Payroll & Compensation')
            ->pluck('date')
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $salariesDate = $salaryDates->isEmpty()
            ? null
            : ($salaryDates->count() === 1
                ? $salaryDates->first()
                : $salaryDates->first().' – '.$salaryDates->last());

        // How many Payroll & Compensation expense rows are printed in the
        // expenses list, so the "Of which: Staff Salaries" informational note
        // can sit directly under the last payroll line instead of adding a
        // second negative value to the outflow column.
        $payrollExpenseCount = collect($expenseItems)
            ->where('category', 'Payroll & Compensation')
            ->count();

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
            'feeRevenue' => (float) $feeRevenue,
            'revenueStreams' => $revenueStreams,
            'revenueStreamTotal' => (float) $revenueStreamTotal,
            'totalRevenue' => (float) $totalRevenue,
            'totalRefunds' => (float) $totalRefunds,
            'refundItems' => $refundItems,
            'totalExpenses' => (float) $totalExpenses,
            'expenseItems' => $expenseItems,
            'accountBreakdown' => $accountBreakdown,
            'salariesExpense' => $salariesExpense,
            'salariesDate' => $salariesDate,
            'payrollExpenseCount' => $payrollExpenseCount,
            'totalInflows' => $totalInflows,
            'totalOutflows' => $totalOutflows,
            'netCashFlow' => (float) $netCashFlow,
            'closingBalance' => (float) ($openingBalance + $netCashFlow),
            'startDateDisp' => $startDate->toDateString(),
            'endDateDisp' => $endDate->toDateString(),
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
