<div class="rounded-xl bg-white p-4 shadow-sm dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Bank Account View') }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ __('Scope revenue and expenses to one account, or view them combined.') }}</p>
        </div>
        <select
            wire:model.live="bankAccountId"
            class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-2.5 py-1.5 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
        >
            <option value="">{{ __('All Accounts (Combined)') }}</option>
            @foreach($this->bankAccounts() as $account)
                <option value="{{ $account->id }}">{{ $account->bank_name }} — {{ $account->account_name ?: $account->account_number }}</option>
            @endforeach
        </select>
    </div>
</div>