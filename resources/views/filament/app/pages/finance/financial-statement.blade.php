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
                <p class="text-[11px] text-gray-400 mt-0.5">{{ __('Fees + other income − refunds') }}</p>
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
        <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white border-b pb-2 mb-3">
                {{ __('Statement Summary for Period: ') }} {{ $startDateDisp }} {{ __('to') }} {{ $endDateDisp }}
            </h3>

            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 text-[11px] uppercase tracking-wide text-gray-500">
                            <th class="py-2 text-left font-semibold">{{ __('Description') }}</th>
                            <th class="py-2 text-right font-semibold whitespace-nowrap w-32">{{ __('Outflows (−)') }}</th>
                            <th class="py-2 text-right font-semibold whitespace-nowrap w-32">{{ __('Inflows (+)') }}</th>
                            <th class="py-2 text-right font-semibold whitespace-nowrap w-32">{{ __('Balance (USD)') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Opening -->
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 font-medium text-gray-600 dark:text-gray-400">{{ __('Opening Bank Balance') }}</td>
                            <td class="py-2"></td>
                            <td class="py-2"></td>
                            <td class="py-2 text-right font-semibold text-gray-900 dark:text-white">${{ number_format($openingBalance ?? 0, 2) }}</td>
                        </tr>

                        <!-- Fee revenue + per-account breakdown -->
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 font-medium text-gray-600 dark:text-gray-400">{{ __('Total Fees Collected') }}</td>
                            <td class="py-2"></td>
                            <td class="py-2 text-right font-medium text-success-600 dark:text-success-400">+${{ number_format($feeRevenue ?? 0, 2) }}</td>
                            <td class="py-2"></td>
                        </tr>
                        @if($allAccounts && !empty($accountBreakdown['feeBreakdown']))
                            @foreach($accountBreakdown['feeBreakdown'] as $feeRow)
                                <tr class="border-b border-gray-50 dark:border-gray-800/50">
                                    <td class="py-1.5 pl-6 text-[13px] text-gray-500 dark:text-gray-500">{{ $feeRow['account'] }} <span class="text-gray-400 text-xs">{{ __('(School fees)') }}</span></td>
                                    <td class="py-1.5"></td>
                                    <td class="py-1.5 text-right text-[13px] font-medium text-success-600/80 dark:text-success-400/80">+${{ number_format($feeRow['amount'], 2) }}</td>
                                    <td class="py-1.5"></td>
                                </tr>
                            @endforeach
                        @else
                            <tr class="border-b border-gray-50 dark:border-gray-800/50">
                                <td class="py-1.5 pl-6 text-[13px] text-gray-500 dark:text-gray-500">{{ __('School fees recorded within the period') }}</td>
                                <td class="py-1.5"></td>
                                <td class="py-1.5 text-right text-[13px] font-medium text-success-600/80 dark:text-success-400/80">+${{ number_format($feeRevenue ?? 0, 2) }}</td>
                                <td class="py-1.5"></td>
                            </tr>
                        @endif

                        <!-- Revenue streams -->
                        @if(count($revenueStreams ?? []))
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 font-medium text-gray-600 dark:text-gray-400">{{ __('Other Income (Revenue Streams)') }}</td>
                                <td class="py-2"></td>
                                <td class="py-2 text-right font-medium text-success-600 dark:text-success-400">+${{ number_format($revenueStreamTotal ?? 0, 2) }}</td>
                                <td class="py-2"></td>
                            </tr>
                            @foreach($revenueStreams as $stream)
                                <tr class="border-b border-gray-50 dark:border-gray-800/50">
                                    <td class="py-1.5 pl-6 text-[13px] text-gray-500 dark:text-gray-500">
                                        {{ $stream['name'] }}
                                        <span class="text-gray-400">({{ $stream['category'] }})</span>
                                        @if($stream['date'])
                                            <span class="text-gray-400 text-xs"> — {{ $stream['date'] }}</span>
                                        @endif
                                        @if($stream['bank'])
                                            <span class="text-gray-400 text-xs"> — {{ $stream['bank'] }}</span>
                                        @endif
                                    </td>
                                    <td class="py-1.5"></td>
                                    <td class="py-1.5 text-right text-[13px] font-medium text-success-600/80 dark:text-success-400/80">+${{ number_format($stream['amount'], 2) }}</td>
                                    <td class="py-1.5"></td>
                                </tr>
                            @endforeach
                            @if($allAccounts && !empty($accountBreakdown['incomeBreakdown']) && count($accountBreakdown['incomeBreakdown']) > 1)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-1.5 pl-6 text-[13px] font-semibold text-gray-600 dark:text-gray-400">{{ __('By Account') }}</td>
                                    <td class="py-1.5"></td>
                                    <td class="py-1.5"></td>
                                    <td class="py-1.5"></td>
                                </tr>
                                @foreach($accountBreakdown['incomeBreakdown'] as $incomeRow)
                                    <tr class="border-b border-gray-50 dark:border-gray-800/50">
                                        <td class="py-1.5 pl-8 text-[13px] text-gray-500 dark:text-gray-500">{{ $incomeRow['account'] }}</td>
                                        <td class="py-1.5"></td>
                                        <td class="py-1.5 text-right text-[13px] font-medium text-success-600/80 dark:text-success-400/80">+${{ number_format($incomeRow['amount'], 2) }}</td>
                                        <td class="py-1.5"></td>
                                    </tr>
                                @endforeach
                            @endif
                        @endif

                        <!-- Refunds (outflow) -->
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 font-medium text-gray-600 dark:text-gray-400">{{ __('Less Refunds Issued') }}</td>
                            <td class="py-2 text-right font-medium text-danger-600 dark:text-danger-400">-${{ number_format($totalRefunds ?? 0, 2) }}</td>
                            <td class="py-2"></td>
                            <td class="py-2"></td>
                        </tr>
                        @foreach($refundItems ?? [] as $refund)
                            <tr class="border-b border-gray-50 dark:border-gray-800/50">
                                <td class="py-1.5 pl-6 text-[13px] text-gray-500 dark:text-gray-500">
                                    {{ $refund['reference'] ?? __('Refund') }}
                                    @if($refund['date'])<span class="text-gray-400 text-xs"> — {{ $refund['date'] }}</span>@endif
                                    @if($refund['account'])<span class="text-gray-400 text-xs"> — {{ $refund['account'] }}</span>@endif
                                </td>
                                <td class="py-1.5 text-right text-[13px] font-medium text-danger-600/80 dark:text-danger-400/80">-${{ number_format($refund['amount'], 2) }}</td>
                                <td class="py-1.5"></td>
                                <td class="py-1.5"></td>
                            </tr>
                        @endforeach
                        @if($allAccounts && !empty($accountBreakdown['refundBreakdown']))
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-1.5 pl-6 text-[13px] font-semibold text-gray-600 dark:text-gray-400">{{ __('By Account') }}</td>
                                <td class="py-1.5"></td>
                                <td class="py-1.5"></td>
                                <td class="py-1.5"></td>
                            </tr>
                            @foreach($accountBreakdown['refundBreakdown'] as $refundRow)
                                <tr class="border-b border-gray-50 dark:border-gray-800/50">
                                    <td class="py-1.5 pl-8 text-[13px] text-gray-500 dark:text-gray-500">{{ $refundRow['account'] }}</td>
                                    <td class="py-1.5 text-right text-[13px] font-medium text-danger-600/80 dark:text-danger-400/80">-${{ number_format($refundRow['amount'], 2) }}</td>
                                    <td class="py-1.5"></td>
                                    <td class="py-1.5"></td>
                                </tr>
                            @endforeach
                        @endif

                        <!-- Expenses (outflow) -->
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="py-2 font-medium text-gray-600 dark:text-gray-400">{{ __('Total Expenses & Outflows') }}</td>
                            <td class="py-2 text-right font-medium text-danger-600 dark:text-danger-400">-${{ number_format($totalExpenses ?? 0, 2) }}</td>
                            <td class="py-2"></td>
                            <td class="py-2"></td>
                        </tr>
                        @foreach($expenseItems ?? [] as $expense)
                            <tr class="border-b border-gray-50 dark:border-gray-800/50">
                                <td class="py-1.5 pl-6 text-[13px] text-gray-500 dark:text-gray-500">
                                    {{ $expense['name'] }}
                                    <span class="text-gray-400">({{ $expense['category'] }})</span>
                                    @if($expense['date'])
                                        <span class="text-gray-400 text-xs"> — {{ $expense['date'] }}</span>
                                    @endif
                                    @if($expense['reference'])
                                        <span class="text-gray-400 text-xs"> — {{ $expense['reference'] }}</span>
                                    @endif
                                    @if($expense['account'])
                                        <span class="text-gray-400 text-xs"> — {{ $expense['account'] }}</span>
                                    @endif
                                </td>
                                <td class="py-1.5 text-right text-[13px] font-medium text-danger-600/80 dark:text-danger-400/80">-${{ number_format($expense['amount'], 2) }}</td>
                                <td class="py-1.5"></td>
                                <td class="py-1.5"></td>
                            </tr>
                        @endforeach
                        @if($allAccounts && !empty($accountBreakdown['expenseBreakdown']))
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-1.5 pl-6 text-[13px] font-semibold text-gray-600 dark:text-gray-400">{{ __('By Account') }}</td>
                                <td class="py-1.5"></td>
                                <td class="py-1.5"></td>
                                <td class="py-1.5"></td>
                            </tr>
                            @foreach($accountBreakdown['expenseBreakdown'] as $expenseRow)
                                <tr class="border-b border-gray-50 dark:border-gray-800/50">
                                    <td class="py-1.5 pl-8 text-[13px] text-gray-500 dark:text-gray-500">{{ $expenseRow['account'] }}</td>
                                    <td class="py-1.5 text-right text-[13px] font-medium text-danger-600/80 dark:text-danger-400/80">-${{ number_format($expenseRow['amount'], 2) }}</td>
                                    <td class="py-1.5"></td>
                                    <td class="py-1.5"></td>
                                </tr>
                            @endforeach
                        @endif

                        <!-- Column totals (real period movements only) -->
                        <tr class="border-y-2 border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            <td class="py-2 font-bold text-gray-800 dark:text-gray-200">{{ __('Total Outflows (−) / Total Inflows (+)') }}</td>
                            <td class="py-2 text-right font-bold text-danger-600 dark:text-danger-400">-${{ number_format($totalOutflows ?? 0, 2) }}</td>
                            <td class="py-2 text-right font-bold text-success-600 dark:text-success-400">+${{ number_format($totalInflows ?? 0, 2) }}</td>
                            <td class="py-2"></td>
                        </tr>

                        <!-- Balances -->
                        <tr class="bg-primary-50/60 dark:bg-primary-900/20">
                            <td class="py-2 font-bold text-gray-900 dark:text-white">{{ __('Net Cash Flow Balance') }}</td>
                            <td class="py-2"></td>
                            <td class="py-2"></td>
                            <td class="py-2 text-right font-bold {{ ($netCashFlow ?? 0) < 0 ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}">${{ number_format($netCashFlow ?? 0, 2) }}</td>
                        </tr>
                        <tr class="bg-primary-50/60 dark:bg-primary-900/20">
                            <td class="py-2 font-bold text-gray-900 dark:text-white">{{ __('Closing Balance') }}</td>
                            <td class="py-2"></td>
                            <td class="py-2"></td>
                            <td class="py-2 text-right font-bold text-primary-600 dark:text-primary-400">${{ number_format($closingBalance ?? 0, 2) }}</td>
                        </tr>

                        <!-- Per-account balances (combined view only) -->
                        @if($allAccounts && !empty($accountBreakdown['openingByAccount']) && !empty($accountBreakdown['closingByAccount']))
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2 pt-3 font-semibold text-gray-700 dark:text-gray-300">{{ __('Balances by Bank Account') }}</td>
                                <td class="py-2"></td>
                                <td class="py-2"></td>
                                <td class="py-2"></td>
                            </tr>
                            @foreach($accountBreakdown['openingByAccount'] as $i => $openingRow)
                                @php $closingRow = $accountBreakdown['closingByAccount'][$i] ?? $openingRow; @endphp
                                <tr class="border-b border-gray-50 dark:border-gray-800/50">
                                    <td class="py-1.5 pl-8 text-[13px] font-medium text-gray-700 dark:text-gray-300">
                                        {{ $openingRow['account'] }}
                                        <span class="text-gray-400 text-xs">{{ __('Opening') }}</span>
                                    </td>
                                    <td class="py-1.5"></td>
                                    <td class="py-1.5"></td>
                                    <td class="py-1.5 text-right text-[13px] font-medium text-gray-700 dark:text-gray-300">${{ number_format($openingRow['amount'], 2) }}</td>
                                </tr>
                                <tr class="border-b border-gray-50 dark:border-gray-800/50">
                                    <td class="py-1.5 pl-8 text-[13px] font-medium text-gray-700 dark:text-gray-300">
                                        {{ $closingRow['account'] }}
                                        <span class="text-gray-400 text-xs">{{ __('Closing') }}</span>
                                    </td>
                                    <td class="py-1.5"></td>
                                    <td class="py-1.5"></td>
                                    <td class="py-1.5 text-right text-[13px] font-medium text-gray-700 dark:text-gray-300">${{ number_format($closingRow['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>