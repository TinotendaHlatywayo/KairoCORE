<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header & Date Range Presets -->
        <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800 flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ __('Official Financial Statement & Cash Flow') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    @if($company)
                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $company }}</span>
                        @if($season)<span class="mx-1 text-gray-300">|</span><span>{{ $season }}</span>@endif
                    @endif
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    @if($allAccounts)
                        {{ __('Viewing: ') }} <span class="font-semibold text-gray-800 dark:text-gray-200">{{ __('All Accounts (Combined)') }}</span>
                    @elseif($defaultBank)
                        {{ __('Active Bank Account: ') }} <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $defaultBank->bank_name }} — {{ $defaultBank->account_name }} ({{ $defaultBank->account_number }})</span>
                    @else
                        {{ __('Default School Account') }}
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <label for="statement-bank-account" class="text-xs font-medium text-gray-500">{{ __('Account:') }}</label>
                <select
                    id="statement-bank-account"
                    wire:model.live="bankAccountId"
                    class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-2.5 py-1.5 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                >
                    <option value="">{{ __('All Accounts (Combined)') }}</option>
                    @foreach($bankAccounts as $account)
                        <option value="{{ $account->id }}">{{ $account->bank_name }} — {{ $account->account_name ?: $account->account_number }}</option>
                    @endforeach
                </select>
                <x-filament::button wire:click="$set('range', 'day')" color="{{ $range === 'day' ? 'primary' : 'gray' }}" size="sm">
                    {{ __('Past 1 Day') }}
                </x-filament::button>
                <x-filament::button wire:click="$set('range', 'week')" color="{{ $range === 'week' ? 'primary' : 'gray' }}" size="sm">
                    {{ __('Past Week') }}
                </x-filament::button>
                <x-filament::button wire:click="$set('range', 'month')" color="{{ $range === 'month' ? 'primary' : 'gray' }}" size="sm">
                    {{ __('Past Month') }}
                </x-filament::button>
                <x-filament::button wire:click="$set('range', 'year')" color="{{ $range === 'year' ? 'primary' : 'gray' }}" size="sm">
                    {{ __('Past Year') }}
                </x-filament::button>
                <x-filament::button onclick="window.print()" color="success" size="sm" icon="heroicon-o-printer">
                    {{ __('Print Statement') }}
                </x-filament::button>
            </div>
        </div>

        @livewire(App\Filament\App\Widgets\FinancialStatementDownloadWidget::class)

        <!-- Summary Cards in the Same Row -->
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
            <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                <p class="text-xs text-gray-500 uppercase font-medium">{{ __('Total Revenue / Inflows') }}</p>
                <p class="text-2xl font-bold text-success-600 dark:text-success-400 mt-1">${{ number_format($totalRevenue ?? 0, 2) }}</p>
            </div>
            <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                <p class="text-xs text-gray-500 uppercase font-medium">{{ __('Total Refunds') }}</p>
                <p class="text-2xl font-bold text-danger-600 dark:text-danger-400 mt-1">-${{ number_format($totalRefunds ?? 0, 2) }}</p>
            </div>
            <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                <p class="text-xs text-gray-500 uppercase font-medium">{{ __('Total Expenses & Outflows') }}</p>
                <p class="text-2xl font-bold text-danger-600 dark:text-danger-400 mt-1">${{ number_format($totalExpenses ?? 0, 2) }}</p>
            </div>
            <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                <p class="text-xs text-gray-500 uppercase font-medium">{{ __('Staff Salaries') }}</p>
                <p class="text-2xl font-bold text-warning-600 dark:text-warning-400 mt-1">${{ number_format($salariesExpense ?? 0, 2) }}</p>
            </div>
            <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                <p class="text-xs text-gray-500 uppercase font-medium">{{ __('Net Cash Flow Balance') }}</p>
                <p class="text-2xl font-bold text-primary-600 dark:text-primary-400 mt-1">${{ number_format($netCashFlow ?? 0, 2) }}</p>
            </div>
        </div>

        <!-- Detailed Statement Box -->
        <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800 space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white border-b pb-2">
                {{ __('Statement Summary for Period: ') }} {{ $startDate }} {{ __('to') }} {{ $endDate }}
            </h3>
            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800 text-sm">
                <span class="text-gray-600 dark:text-gray-400">{{ __('Opening Bank Balance') }}</span>
                <span class="font-medium text-gray-900 dark:text-white">${{ number_format($openingBalance ?? 0, 2) }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800 text-sm">
                <span class="text-gray-600 dark:text-gray-400">{{ __('Total Fees & Revenue Collected') }}</span>
                <span class="font-medium text-success-600 dark:text-success-400">+${{ number_format($totalRevenue, 2) }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800 text-sm">
                <span class="text-gray-600 dark:text-gray-400">{{ __('Less Refunds Issued') }}</span>
                <span class="font-medium text-danger-600 dark:text-danger-400">-${{ number_format($totalRefunds, 2) }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800 text-sm">
                <span class="text-gray-600 dark:text-gray-400">{{ __('Total Expenses Disbursed (Salaries, Inventory, Assets)') }}</span>
                <span class="font-medium text-danger-600 dark:text-danger-400">-${{ number_format($totalExpenses, 2) }}</span>
            </div>
            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800 text-sm pl-4">
                <span class="text-gray-500 dark:text-gray-500">{{ __('Of which: Staff Salaries') }}</span>
                <span class="font-medium text-warning-600 dark:text-warning-400">-${{ number_format($salariesExpense ?? 0, 2) }}</span>
            </div>
            <div class="flex justify-between py-3 text-base font-bold">
                <span class="text-gray-900 dark:text-white">{{ __('Closing Balance') }}</span>
                <span class="text-primary-600 dark:text-primary-400">${{ number_format($netCashFlow, 2) }}</span>
            </div>
        </div>
    </div>
</x-filament-panels::page>
