<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Finance\Models\Expense;

class ExpenseAnalyticsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $schoolId = current_tenant()?->id;

        if (! $schoolId) {
            return [];
        }

        $totalExpenses = (float) Expense::where('school_id', $schoolId)->sum('amount');

        // Highest single expense
        $highestExpense = Expense::where('school_id', $schoolId)->orderByDesc('amount')->first();
        $highestText = $highestExpense ? "{$highestExpense->expense_name} ($" . number_format($highestExpense->amount, 2) . ")" : __('None');

        return [
            Stat::make(__('Total Expenses Disbursed'), '$'.number_format($totalExpenses, 2))
                ->description(__('All registered operating & procurement expenses'))
                ->descriptionIcon('heroicon-m-receipt-refund')
                ->color('danger'),
            Stat::make(__('Highest Expense'), $highestText)
                ->description(__('Largest single disbursement recorded'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('gray'),
        ];
    }
}
