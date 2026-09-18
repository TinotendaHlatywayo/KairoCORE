<?php

namespace App\Filament\App\Widgets;

use Carbon\Carbon;
use Filament\Widgets\Widget;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\FinanceDocumentTemplate;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Finance\Services\BillingDocumentSettingsService;
use Modules\Finance\Services\FinancialAnalyticsEngine;
use Modules\Finance\Services\FinancialReportService;
use Modules\HR\Services\PayrollCalculationService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialStatementDownloadWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.financial-statement-download';

    public function downloadPdf(): StreamedResponse
    {
        return $this->streamReport('pdf');
    }

    public function downloadCsv(): StreamedResponse
    {
        return $this->streamReport('csv');
    }

    public function downloadExcel(): StreamedResponse
    {
        return $this->streamReport('xlsx');
    }

    public function downloadTxt(): StreamedResponse
    {
        return $this->streamReport('txt');
    }

    /**
     * Mirror the date range selected on the Financial Statements page,
     * including the custom from/to dates when a custom range is active.
     *
     * @return array{range: string, startDate: ?string, endDate: ?string}
     */
    protected function currentRange(): array
    {
        $parent = method_exists($this, 'getParent') ? $this->getParent() : null;

        $range = $parent?->range ?? 'month';
        $startDate = $parent?->startDate ?? '';
        $endDate = $parent?->endDate ?? '';

        return [
            'range' => $range,
            'startDate' => $startDate !== '' ? (string) $startDate : null,
            'endDate' => $endDate !== '' ? (string) $endDate : null,
        ];
    }

    /**
     * Mirror the bank account selected on the Financial Statements page
     * (empty string => all accounts combined).
     */
    protected function currentBankAccountId(): ?int
    {
        $parent = method_exists($this, 'getParent') ? $this->getParent() : null;
        $value = $parent?->bankAccountId ?? '';

        return $value !== '' ? (int) $value : null;
    }

    protected function streamReport(string $format): StreamedResponse
    {
        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id ?? 5;
        $rangeInfo = $this->currentRange();
        $bankAccountId = $this->currentBankAccountId();

        if ($rangeInfo['startDate'] && $rangeInfo['endDate']) {
            $startDate = Carbon::parse($rangeInfo['startDate'])->startOfDay();
            $endDate = Carbon::parse($rangeInfo['endDate'])->endOfDay();
        } else {
            $startDate = match ($rangeInfo['range']) {
                'day' => now()->subDay(),
                'week' => now()->subWeek(),
                'month' => now()->subMonth(),
                'year' => now()->subYear(),
                default => now()->subMonth(),
            };
            $endDate = now();
        }

        $totalRevenue = (float) Payment::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId))
            ->where(fn ($q) => $q->where('is_refund', false)->orWhereNull('is_refund'))
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->sum('amount');
        // Refund rows are stored as negative amounts; normalise to a positive
        // "amount refunded" figure for the statement line items.
        $totalRefunds = abs((float) Payment::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId))
            ->where('is_refund', true)
            ->where('created_at', '>=', $startDate)
            ->where('created_at', '<=', $endDate)
            ->sum('amount'));
        $totalExpenses = (float) Expense::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId))
            ->where('expense_date', '>=', $startDate->toDateString())
            ->where('expense_date', '<=', $endDate->toDateString())
            ->sum('amount');
        $totalSalaries = (float) app(PayrollCalculationService::class)
            ->payrollExpenseTotal($schoolId, $startDate->toDateString(), $endDate->toDateString(), $bankAccountId);

        $engine = app(FinancialAnalyticsEngine::class);
        $startStr = $startDate->toDateString();
        $endStr = $endDate->toDateString();
        $revenueStreams = $engine->getRevenueStreamsForPeriod($schoolId, $startStr, $endStr, $bankAccountId);
        $revenueStreamTotal = array_sum(array_column($revenueStreams, 'amount'));
        $refundItems = $engine->getRefundsForPeriod($schoolId, $startStr, $endStr, $bankAccountId);
        $expenseItems = $engine->getExpensesForPeriod($schoolId, $startStr, $endStr, $bankAccountId);

        // Total Revenue is net of refunds, mirroring FinancialStatementPage and
        // the Executive Dashboard summary.
        $totalRevenueInclStreams = $totalRevenue + $revenueStreamTotal - $totalRefunds;
        $netCashFlow = $totalRevenueInclStreams - $totalExpenses;

        // Split totals for the two movement columns. Only real period movements
        // are summed: inflows = fees + other income, outflows = refunds + expenses.
        $totalInflows = $totalRevenue + $revenueStreamTotal;
        $totalOutflows = $totalRefunds + $totalExpenses;

        // Date(s) of the payroll expense(s) behind the "Of which — Staff
        // Salaries" memo line, mirroring the on-screen statement.
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

        $bankAccount = $bankAccountId
            ? SchoolBankAccount::where('school_id', $schoolId)->where('id', $bankAccountId)->first()
            : null;

        // Mirror the opening-balance logic used on the page: an account created
        // within the period opened at $0.
        $targetAccounts = $bankAccount
            ? collect([$bankAccount])
            : SchoolBankAccount::where('school_id', $schoolId)->get();
        $openingBalance = $targetAccounts->sum(function (SchoolBankAccount $account) use ($startDate): float {
            if ($account->created_at && $account->created_at->greaterThanOrEqualTo($startDate)) {
                return 0.0;
            }

            return (float) $account->balance;
        });

        $school = current_tenant();

        $data = [
            'school' => $school?->name ?? config('app.name'),
            'companyTagline' => $school?->motto,
            'companyAddress' => $school?->physical_address ?? '',
            'companyPhone' => $school?->phone_number ?? $school?->phone,
            'companyEmail' => $school?->email_address,
            'bankAccountName' => $bankAccount
                ? trim(implode(' — ', array_filter([$bankAccount->bank_name, $bankAccount->account_name])))
                : __('All Accounts (Combined)'),
            'startDate' => $startStr,
            'endDate' => $endStr,
            'openingBalance' => $openingBalance,
            'feeRevenue' => $totalRevenue,
            'revenueStreams' => $revenueStreams,
            'revenueStreamTotal' => $revenueStreamTotal,
            'totalRevenue' => $totalRevenueInclStreams,
            'totalRefunds' => $totalRefunds,
            'refundItems' => $refundItems,
            'totalExpenses' => $totalExpenses,
            'expenseItems' => $expenseItems,
            'totalSalaries' => $totalSalaries,
            'salariesDate' => $salariesDate,
            'totalInflows' => $totalInflows,
            'totalOutflows' => $totalOutflows,
            'netCashFlow' => $netCashFlow,
            'closingBalance' => $openingBalance + $netCashFlow,
            'generatedAt' => $endDate->format('Y-m-d H:i'),
            // Pass the school + billing-document layout so the PDF renders with
            // the same branded theme as receipts, invoices and statements.
            'schoolModel' => $school,
            'config' => BillingDocumentSettingsService::get(),
            'template' => FinanceDocumentTemplate::resolveFor((int) $school->id, 'statement'),
        ];

        return FinancialReportService::download($format, $data, $startDate, $endDate);
    }
}
