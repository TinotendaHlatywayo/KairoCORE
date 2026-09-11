<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Students\Models\Student;

/**
 * Compact finance snapshot shown on the main Dashboard. Every fee payment
 * grows "Total Revenue" and the school bank balance, so these figures always
 * reflect what has actually been collected.
 */
class FinanceDashboardSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $schoolId = current_tenant()?->id;

        if (! $schoolId) {
            return [];
        }

        $totalRevenue = (float) Payment::where('school_id', $schoolId)
            ->where('is_refund', false)
            ->sum('amount');
        $totalRefunds = (float) Payment::where('school_id', $schoolId)
            ->where('is_refund', true)
            ->sum('amount');
        $totalExpenses = (float) Expense::where('school_id', $schoolId)->sum('amount');

        $net = $totalRevenue - $totalRefunds - $totalExpenses;

        $outstanding = (float) Invoice::where('school_id', $schoolId)
            ->where('status', '!=', 'paid')
            ->sum('balance_amount');

        $studentCredits = (float) Student::where('school_id', $schoolId)->sum('credit_balance');

        $bankBalance = (float) SchoolBankAccount::where('school_id', $schoolId)->sum('balance');

        return [
            Stat::make(__('Total Revenue Collected'), '$'.number_format($totalRevenue, 2))
                ->description(__('Fees and other payments received'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make(__('Expenses & Refunds'), '-$'.number_format($totalExpenses + $totalRefunds, 2))
                ->description(__('Salaries, procurement and ad-hoc spending'))
                ->descriptionIcon('heroicon-m-arrow-down-circle')
                ->color('danger'),
            Stat::make(__('Net Cash Position'), '$'.number_format($net, 2))
                ->description(__('Net of revenue, expenses and refunds'))
                ->descriptionIcon('heroicon-m-scale')
                ->color('primary'),
            Stat::make(__('Outstanding Fees'), '$'.number_format($outstanding, 2))
                ->description(__('Unpaid invoice balances'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make(__('Student Credits'), '$'.number_format($studentCredits, 2))
                ->description(__('Carried forward for next term fees'))
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('info'),
            Stat::make(__('Bank Balance'), '$'.number_format($bankBalance, 2))
                ->description(__('Combined school bank accounts'))
                ->descriptionIcon('heroicon-m-building-library')
                ->color('gray'),
        ];
    }
}