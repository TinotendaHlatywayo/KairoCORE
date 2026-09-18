<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header & Date Range -->
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
            </div>
        </div>

        <!-- Custom date range -->
        <div class="p-4 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800 flex flex-wrap items-center gap-3">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Custom Date Range') }}:</span>
            <input
                type="date"
                wire:model.live="startDate"
                class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-2.5 py-1.5 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />
            <span class="text-xs text-gray-400">{{ __('to') }}</span>
            <input
                type="date"
                wire:model.live="endDate"
                class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-2.5 py-1.5 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
            />
            <span class="text-xs text-gray-400">
                {{ __('Reporting period:') }} <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $startDateDisp }}</span> {{ __('to') }} <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $endDateDisp }}</span>
            </span>
        </div>

        @livewire(App\Filament\App\Widgets\FinancialStatementDownloadWidget::class)

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-4">
            <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                <p class="text-xs text-gray-500 uppercase font-medium">{{ __('Total Revenue / Inflows') }}</p>
                <p class="text-2xl font-bold text-success-600 dark:text-success-400 mt-1">${{ number_format($totalRevenue ?? 0, 2) }}</p>
                <p class="text-[11px] text-gray-400 mt-0.5">{{ __('Fees + other income') }}</p>
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

        <!-- Detailed Statement -->
        <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800 space-y-1">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white border-b pb-2 mb-3">
                {{ __('Statement Summary for Period: ') }} {{ $startDateDisp }} {{ __('to') }} {{ $endDateDisp }}
            </h3>

            <!-- Opening -->
            <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800 text-sm">
                <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Opening Bank Balance') }}</span>
                <span class="font-semibold text-gray-900 dark:text-white">${{ number_format($openingBalance ?? 0, 2) }}</span>
            </div>

            <!-- Revenue -->
            <div class="pt-2">
                <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800 text-sm">
                    <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Total Fees Collected') }}</span>
                    <span class="font-medium text-success-600 dark:text-success-400">+${{ number_format($feeRevenue ?? 0, 2) }}</span>
                </div>
                <div class="flex justify-between py-1.5 pl-6 border-b border-gray-50 dark:border-gray-800/50 text-[13px]">
                    <span class="text-gray-500 dark:text-gray-500">{{ __('School fees recorded within the period') }}</span>
                    <span class="font-medium text-success-600/80 dark:text-success-400/80">+${{ number_format($feeRevenue ?? 0, 2) }}</span>
                </div>
            </div>

            <!-- Revenue streams -->
            @if(count($revenueStreams ?? []))
                <div class="pt-2">
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800 text-sm">
                        <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Other Income (Revenue Streams)') }}</span>
                        <span class="font-medium text-success-600 dark:text-success-400">+${{ number_format($revenueStreamTotal ?? 0, 2) }}</span>
                    </div>
                    @foreach($revenueStreams as $stream)
                        <div class="flex justify-between py-1.5 pl-6 border-b border-gray-50 dark:border-gray-800/50 text-[13px]">
                            <span class="text-gray-500 dark:text-gray-500">
                                {{ $stream['name'] }}
                                <span class="text-gray-400">({{ $stream['category'] }})</span>
                                @if($stream['date'])
                                    <span class="text-gray-400 text-xs"> — {{ $stream['date'] }}</span>
                                @endif
                                @if($stream['bank'])
                                    <span class="text-gray-400 text-xs"> — {{ $stream['bank'] }}</span>
                                @endif
                            </span>
                            <span class="font-medium text-success-600/80 dark:text-success-400/80">+${{ number_format($stream['amount'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex justify-between py-2 text-sm font-bold">
                <span class="text-gray-800 dark:text-gray-200">{{ __('Total Revenue / Inflows') }}</span>
                <span class="text-success-600 dark:text-success-400">+${{ number_format($totalRevenue ?? 0, 2) }}</span>
            </div>

            <!-- Refunds -->
            <div class="pt-2">
                <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800 text-sm">
                    <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Less Refunds Issued') }}</span>
                    <span class="font-medium text-danger-600 dark:text-danger-400">-${{ number_format($totalRefunds ?? 0, 2) }}</span>
                </div>
                @if(count($refundItems ?? []))
                    @foreach($refundItems as $refund)
                        <div class="flex justify-between py-1.5 pl-6 border-b border-gray-50 dark:border-gray-800/50 text-[13px]">
                            <span class="text-gray-500 dark:text-gray-500">
                                {{ $refund['reference'] ?? __('Refund') }}
                                @if($refund['date'])<span class="text-gray-400 text-xs"> — {{ $refund['date'] }}</span>@endif
                                @if($refund['account'])<span class="text-gray-400 text-xs"> — {{ $refund['account'] }}</span>@endif
                            </span>
                            <span class="font-medium text-danger-600/80 dark:text-danger-400/80">-${{ number_format($refund['amount'], 2) }}</span>
                        </div>
                    @endforeach
                @endif
            </div>

            <!-- Expenses -->
            <div class="pt-2">
                <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-800 text-sm">
                    <span class="text-gray-600 dark:text-gray-400 font-medium">{{ __('Total Expenses & Outflows') }}</span>
                    <span class="font-medium text-danger-600 dark:text-danger-400">-${{ number_format($totalExpenses ?? 0, 2) }}</span>
                </div>
                @if(count($expenseItems ?? []))
                    @foreach($expenseItems as $expense)
                        <div class="flex justify-between py-1.5 pl-6 border-b border-gray-50 dark:border-gray-800/50 text-[13px]">
                            <span class="text-gray-500 dark:text-gray-500">
                                {{ $expense['name'] }}
                                <span class="text-gray-400">({{ $expense['category'] }})</span>
                                @if($expense['date'])
                                    <span class="text-gray-400 text-xs"> — {{ $expense['date'] }}</span>
                                @endif
                                @if($expense['reference'])
                                    <span class="text-gray-400 text-xs"> — {{ $expense['reference'] }}</span>
                                @endif
                            </span>
                            <span class="font-medium text-danger-600/80 dark:text-danger-400/80">-${{ number_format($expense['amount'], 2) }}</span>
                        </div>
                    @endforeach
                @endif
                <div class="flex justify-between py-1.5 pl-6 text-[13px]">
                    <span class="text-gray-500 dark:text-gray-500">{{ __('Of which: Staff Salaries') }}</span>
                    <span class="font-medium text-warning-600 dark:text-warning-400">-${{ number_format($salariesExpense ?? 0, 2) }}</span>
                </div>
            </div>

            <!-- Closing -->
            <div class="flex justify-between py-3 text-base font-bold border-t border-gray-200 dark:border-gray-700 mt-2">
                <span class="text-gray-900 dark:text-white">{{ __('Closing Balance') }}</span>
                <span class="text-primary-600 dark:text-primary-400">${{ number_format(($openingBalance ?? 0) + ($netCashFlow ?? 0), 2) }}</span>
            </div>
        </div>
    </div>
</x-filament-panels::page>