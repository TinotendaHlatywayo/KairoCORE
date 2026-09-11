<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\Widget;
use Modules\Finance\Services\FinancialReportService;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\Expense;
use Carbon\Carbon;

class FinancialStatementDownloadWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.financial-statement-download';

    public function downloadPdf(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->streamReport('pdf');
    }

    public function downloadCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->streamReport('csv');
    }

    public function downloadTxt(): \Symfony\Component\HttpFoundation\StreamedResponse
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

    protected function streamReport(string $format): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $schoolId = current_tenant()?->id ?? 1;
        $range = $this->currentRange();
        $startDate = match ($range) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };
        $endDate = now();

        $totalRevenue = (float) Payment::where('school_id', $schoolId)
            ->where('is_refund', false)
            ->where('created_at', '>=', $startDate)
            ->sum('amount');
        $totalRefunds = (float) Payment::where('school_id', $schoolId)
            ->where('is_refund', true)
            ->where('created_at', '>=', $startDate)
            ->sum('amount');
        $totalExpenses = (float) Expense::where('school_id', $schoolId)
            ->where('expense_date', '>=', $startDate->toDateString())
            ->sum('amount');

        $data = [
            'school' => current_tenant()?->name ?? config('app.name'),
            'companyTagline' => current_tenant()?->tagline,
            'companyAddress' => current_tenant()?->address ?? (string) (current_tenant()?->city ?? ''),
            'companyPhone' => current_tenant()?->phone,
            'companyEmail' => current_tenant()?->email,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'totalRevenue' => $totalRevenue,
            'totalRefunds' => $totalRefunds,
            'totalExpenses' => $totalExpenses,
            'netCashFlow' => ($totalRevenue - $totalRefunds) - $totalExpenses,
            'generatedAt' => $endDate->format('Y-m-d H:i'),
        ];

        return FinancialReportService::download($format, $data, $startDate, $endDate);
    }
}