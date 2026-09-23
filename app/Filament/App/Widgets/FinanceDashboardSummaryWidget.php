<?php

namespace App\Filament\App\Widgets;

use App\Filament\App\Pages\Finance\StudentBillingHub;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Finance\Services\FinancialAnalyticsEngine;
use Modules\Students\Models\Student;

/**
 * Compact finance snapshot shown on the main Dashboard. Every fee payment
 * grows "Total Revenue" and the school bank balance, so these figures always
 * reflect what has actually been collected. When a bank account is selected
 * via BankAccountSwitcherWidget the figures scope to that account.
 */
class FinanceDashboardSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 2;

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
        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id ?? 5;

        // Internal carry-forward settlements (created by BalanceCarryForwardService
        // when a term is activated) move an unpaid balance onto a CF- invoice with
        // a zero-cash "credit" Payment row. They must never count as collected
        // revenue: exclude payments attached to an invoice that was carried forward.
        $collectedPayments = (float) Payment::where('school_id', $schoolId)
            ->when($this->bankAccountId, SchoolBankAccount::filterClosure($this->bankAccountId, $schoolId))
            ->where(fn ($q) => $q->where('is_refund', false)->orWhereNull('is_refund'))
            ->whereDoesntHave('invoice', fn ($q) => $q->whereNotNull('carried_forward_at'))
            ->sum('amount');

        $studentCredits = (float) Student::where('school_id', $schoolId)->sum('credit_balance');

        // A credited overpayment (e.g. paid $200 on a $100 invoice) deposited the
        // whole $200 into the bank; only the applied $100 became a Payment row and
        // the $100 excess is carried on credit_balance. That money is already
        // collected, so count it as revenue too. On a single bank account view the
        // credit has no bank tag, so it only joins the combined school wide view.
        $totalRevenue = $collectedPayments
            + (float) app(FinancialAnalyticsEngine::class)->getRevenueStreamTotal($schoolId, $this->bankAccountId)
            + ($this->bankAccountId ? 0.0 : $studentCredits);
        // Refund rows are stored as negative amounts; normalise to a positive
        // "amount refunded" figure so it is subtracted, not added.
        $totalRefunds = abs((float) Payment::where('school_id', $schoolId)
            ->when($this->bankAccountId, SchoolBankAccount::filterClosure($this->bankAccountId, $schoolId))
            ->where('is_refund', true)
            ->sum('amount'));
        $totalExpenses = (float) Expense::where('school_id', $schoolId)
            ->when($this->bankAccountId, SchoolBankAccount::filterClosure($this->bankAccountId, $schoolId))
            ->sum('amount');

        $net = $totalRevenue - $totalRefunds - $totalExpenses;

        $outstanding = (float) Invoice::where('school_id', $schoolId)
            ->where('status', '!=', 'paid')
            ->sum('balance_amount');

        $bankBalance = max(0, $net);

        $account = $this->bankAccountId
            ? SchoolBankAccount::where('school_id', $schoolId)->where('id', $this->bankAccountId)->first()
            : null;

        $bankLabel = $account
            ? __('Balance of :account', ['account' => $account->bank_name])
            : __('Combined school bank accounts');

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
                ->url(StudentBillingHub::getUrl(['hub' => 'outstanding']))
                ->color('warning'),
            Stat::make(__('Student Credits'), '$'.number_format($studentCredits, 2))
                ->description(__('Carried forward for next term fees'))
                ->descriptionIcon('heroicon-m-sparkles')
                ->url(StudentBillingHub::getUrl(['hub' => 'credits']))
                ->color('info'),
            Stat::make(__('Bank Balance'), '$'.number_format($bankBalance, 2))
                ->description($bankLabel)
                ->descriptionIcon('heroicon-m-building-library')
                ->color('gray'),
        ];
    }
}
