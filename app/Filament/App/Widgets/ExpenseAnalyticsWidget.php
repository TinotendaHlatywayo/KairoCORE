<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Finance\Models\Expense;
use Illuminate\Support\Facades\DB;

class ExpenseAnalyticsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $schoolId = current_tenant()?->id;

        if (! $schoolId) {
            return [];
        }

        $totalExpenses = (float) Expense::where('school_id', $schoolId)->sum('amount');
        $paidExpenses = (float) Expense::where('school_id', $schoolId)->where('status', 'paid')->sum('amount');
        $pendingExpenses = (float) Expense::where('school_id', $schoolId)->where('status', 'pending')->sum('amount');

        // Highest single expense
        $highestExpense = Expense::where('school_id', $schoolId)->orderByDesc('amount')->first();
        $highestText = $highestExpense ? "{$highestExpense->expense_name} ($" . number_format($highestExpense->amount, 2) . ")" : __('None');

        // Top category breakdown description
        $categories = DB::table('expenses')
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->where('expenses.school_id', $schoolId)
            ->select('expense_categories.name', DB::raw('SUM(expenses.amount) as total'))
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->get();

        $catSummary = $categories->map(fn ($c) => "{$c->name}: $" . number_format($c->total, 2))->implode(' | ');
        if (empty($catSummary)) {
            $catSummary = __('No category breakdown available');
        }

        return [
            Stat::make(__('Total Expenses Disbursed'), '$'.number_format($totalExpenses, 2))
                ->description(__('All registered operating & procurement expenses'))
                ->descriptionIcon('heroicon-m-receipt-refund')
                ->color('danger'),
            Stat::make(__('Paid vs Pending'), __('Paid: $%s | Pending: $%s', [number_format($paidExpenses, 2), number_format($pendingExpenses, 2)]))
                ->description(__('Status breakdown of expense ledger'))
                ->descriptionIcon('heroicon-m-wallet')
                ->color('warning'),
            Stat::make(__('Highest Expense'), $highestText)
                ->description(__('Largest single disbursement recorded'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('gray'),
            Stat::make(__('Total per Each Category'), $catSummary)
                ->description(__('Category-wise expenditure aggregation'))
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('primary'),
        ];
    }
}
