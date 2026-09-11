<?php

namespace App\Filament\App\Pages\Finance;

use Filament\Pages\Page;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\Expense;
use Carbon\Carbon;

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

    public function getTitle(): string
    {
        return __('School Financial Statement & Cash Flow');
    }

    protected function getViewData(): array
    {
        $schoolId = current_tenant()?->id ?? 1;
        $defaultBank = SchoolBankAccount::where('school_id', $schoolId)->where('is_default', true)->first() 
            ?? SchoolBankAccount::where('school_id', $schoolId)->first();

        $startDate = match ($this->range) {
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };

        $totalRevenue = Payment::where('school_id', $schoolId)
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        $totalExpenses = Expense::where('school_id', $schoolId)
            ->where('expense_date', '>=', $startDate->toDateString())
            ->sum('amount');

        $netCashFlow = $totalRevenue - $totalExpenses;

        return [
            'defaultBank' => $defaultBank,
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'netCashFlow' => $netCashFlow,
            'startDate' => $startDate->toDateString(),
            'endDate' => now()->toDateString(),
        ];
    }
}
