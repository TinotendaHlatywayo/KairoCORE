<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Modules\Finance\Models\SchoolBankAccount;

/**
 * Lets a user scope finance views to one bank account, or view them combined
 * ("All Accounts"). The choice is stored per-user in the session and broadcast
 * to sibling widgets so Dashboard and Expenses analytics react instantly.
 */
class BankAccountSwitcherWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.bank-account-switcher';

    protected static ?int $sort = -100;

    public string $bankAccountId = '';

    public function mount(): void
    {
        $this->bankAccountId = (string) (request()->query('bank_account') ?? session('finance_bank_account', ''));
    }

    public function bankAccounts(): Collection
    {
        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id;

        return SchoolBankAccount::query()
            ->where('school_id', $schoolId)
            ->orderByDesc('is_default')
            ->get();
    }

    public function updatedBankAccountId(): void
    {
        session(['finance_bank_account' => $this->bankAccountId ?: null]);

        $this->dispatch('bank-account-changed', bankAccountId: (int) $this->bankAccountId ?: null);
    }
}
