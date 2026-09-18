<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\Widget;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\FinanceDocumentTemplate;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Finance\Services\BillingDocumentSettingsService;
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

    public function downloadTxt(): StreamedResponse
    {
        return $this->streamReport('txt');
    }

    /**
     * Mirror the date range selected on the Financial Statements page.
     */
    protected function currentRange(): string
    {
        $parent = method_exists($this, 'getParent') ? $this->getParent() : null;

        return $parent?->range ?? 'month';
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
        $range = $this->currentRange();
        $bankAccountId = $this->currentBankAccountId();
        $startDate = match ($range) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };
        $endDate = now();

        $totalRevenue = (float) Payment::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->when($bankAccountId, fn ($q) => $q->where('bank_account_id', $bankAccountId))
            ->where(fn ($q) => $q->where('is_refund', false)->orWhereNull('is_refund'))
            ->sum('amount');
        // Refund rows are stored as negative amounts; normalise to a positive
        // "amount refunded" figure for the statement line items.
        $totalRefunds = abs((float) Payment::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->when($bankAccountId, fn ($q) => $q->where('bank_account_id', $bankAccountId))
            ->where('is_refund', true)
            ->sum('amount'));
        $totalExpenses = (float) Expense::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->when($bankAccountId, fn ($q) => $q->where('bank_account_id', $bankAccountId))
            ->sum('amount');
        $totalSalaries = (float) app(PayrollCalculationService::class)
            ->payrollExpenseTotal($schoolId, $startDate->toDateString(), $endDate->toDateString(), $bankAccountId);

        $school = current_tenant();
        $bankAccount = $bankAccountId
            ? SchoolBankAccount::where('school_id', $schoolId)->where('id', $bankAccountId)->first()
            : null;

        $data = [
            'school' => $school?->name ?? config('app.name'),
            'companyTagline' => $school?->motto,
            'companyAddress' => $school?->physical_address ?? '',
            'companyPhone' => $school?->phone_number ?? $school?->phone,
            'companyEmail' => $school?->email_address,
            'bankAccountName' => $bankAccount
                ? trim(implode(' — ', array_filter([$bankAccount->bank_name, $bankAccount->account_name])))
                : __('All Accounts (Combined)'),
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'totalRevenue' => $totalRevenue,
            'totalRefunds' => $totalRefunds,
            'totalExpenses' => $totalExpenses,
            'totalSalaries' => $totalSalaries,
            'netCashFlow' => $totalRevenue - $totalRefunds - $totalExpenses,
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
