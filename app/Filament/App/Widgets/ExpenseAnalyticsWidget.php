<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\SchoolBankAccount;

class ExpenseAnalyticsWidget extends BaseWidget
{
    public ?int $bankAccountId = null;

    public function mount(): void
    {
        $this->bankAccountId = (int) (session('finance_bank_account')) ?: null;
    }

    #[On('bank-account-changed')]
    public function setBankAccount(?int $bankAccountId = null): void
    {
        $this->bankAccountId = $bankAccountId ?: null;
    }

    protected function getStats(): array
    {
        $schoolId = current_tenant()?->id;

        if (! $schoolId) {
            return [];
        }

        $query = Expense::where('school_id', $schoolId)
            ->when($this->bankAccountId, SchoolBankAccount::filterClosure($this->bankAccountId, $schoolId));

        $totalExpenses = (float) (clone $query)->sum('amount');

        // Highest single expense
        $highestExpense = (clone $query)->orderByDesc('amount')->first();
        $highestText = $highestExpense ? "{$highestExpense->expense_name} ($".number_format($highestExpense->amount, 2).')' : __('None');

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
