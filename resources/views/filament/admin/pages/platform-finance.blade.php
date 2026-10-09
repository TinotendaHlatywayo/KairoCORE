<x-filament-panels::page>
    <div class="space-y-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Statement period') }}</h3>
                <p class="text-xs text-slate-500">{{ __('Choose a date range to build the KairoCORE profit & loss view, and a year for the monthly breakdown below.') }}</p>
            </div>
            {{ $this->form }}
        </div>

        @php
            $currency = $report['currency'] ?? 'USD';
            $revenue = $report['revenue'] ?? 0;
            $expenses = $report['expenses'] ?? 0;
            $net = $report['net_profit'] ?? 0;
            $margin = $report['margin'] ?? null;
        @endphp

        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Revenue') }}</span>
                    <h3 class="mt-2 text-3xl font-extrabold text-slate-950 dark:text-white">${{ number_format($revenue, 2) }}</h3>
                    <p class="mt-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">{{ __('Collected payments') }}</p>
                </div>
                <div class="rounded-xl bg-emerald-50 p-3 text-emerald-600 dark:bg-emerald-950/40">
                    <x-heroicon-o-banknotes class="h-7 w-7" />
                </div>
            </div>

            <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Operating Expenses') }}</span>
                    <h3 class="mt-2 text-3xl font-extrabold text-slate-950 dark:text-white">${{ number_format($expenses, 2) }}</h3>
                    <p class="mt-1 text-xs font-semibold text-rose-500">{{ __(':count entries', ['count' => $report['expense_count'] ?? 0]) }}</p>
                </div>
                <div class="rounded-xl bg-rose-50 p-3 text-rose-600 dark:bg-rose-950/40">
                    <x-heroicon-o-receipt-refund class="h-7 w-7" />
                </div>
            </div>

            <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Net Profit') }}</span>
                    <h3 class="mt-2 text-3xl font-extrabold {{ $net >= 0 ? 'text-slate-950 dark:text-white' : 'text-rose-600 dark:text-rose-400' }}">${{ number_format($net, 2) }}</h3>
                    <p class="mt-1 text-xs font-semibold {{ $net >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500' }}">{{ __('Revenue minus expenses') }}</p>
                </div>
                <div class="rounded-xl bg-indigo-50 p-3 text-indigo-600 dark:bg-indigo-950/40">
                    <x-heroicon-o-scale class="h-7 w-7" />
                </div>
            </div>

            <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Profit Margin') }}</span>
                    <h3 class="mt-2 text-3xl font-extrabold text-slate-950 dark:text-white">{{ $margin === null ? '—' : number_format($margin, 1).'%' }}</h3>
                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ __('Outstanding: $:amount', ['amount' => number_format($report['outstanding_total'] ?? 0, 2)]) }}</p>
                </div>
                <div class="rounded-xl bg-amber-50 p-3 text-amber-600 dark:bg-amber-950/40">
                    <x-heroicon-o-arrow-trending-up class="h-7 w-7" />
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Monthly Breakdown') }} — {{ $report['year'] ?? now()->year }}</h3>
                <span class="text-xs font-semibold text-slate-400">{{ $currency }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs">
                    <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:bg-slate-950">
                        <tr>
                            <th class="px-6 py-3.5">{{ __('Month') }}</th>
                            <th class="px-6 py-3.5 text-right">{{ __('Revenue') }}</th>
                            <th class="px-6 py-3.5 text-right">{{ __('Expenses') }}</th>
                            <th class="px-6 py-3.5 text-right">{{ __('Net') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-600 dark:divide-slate-800 dark:text-slate-400">
                        @forelse($report['months'] ?? [] as $month)
                            <tr>
                                <td class="px-6 py-3.5 font-bold text-slate-900 dark:text-white">{{ $month['label'] }}</td>
                                <td class="px-6 py-3.5 text-right font-semibold text-emerald-600 dark:text-emerald-400">${{ number_format($month['revenue'], 2) }}</td>
                                <td class="px-6 py-3.5 text-right font-semibold text-rose-600 dark:text-rose-400">${{ number_format($month['expenses'], 2) }}</td>
                                <td class="px-6 py-3.5 text-right font-bold {{ $month['net'] >= 0 ? 'text-slate-900 dark:text-white' : 'text-rose-600 dark:text-rose-400' }}">${{ number_format($month['net'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-400">{{ __('No completed months recorded for this year.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Expenses by Category') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:bg-slate-950">
                            <tr>
                                <th class="px-6 py-3.5">{{ __('Category') }}</th>
                                <th class="px-6 py-3.5 text-right">{{ __('Total') }}</th>
                                <th class="px-6 py-3.5 text-right">{{ __('Count') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600 dark:divide-slate-800 dark:text-slate-400">
                            @forelse($report['by_category'] ?? [] as $row)
                                <tr>
                                    <td class="px-6 py-3.5 font-semibold text-slate-900 dark:text-white">{{ $row['category'] }}</td>
                                    <td class="px-6 py-3.5 text-right font-bold">${{ number_format($row['total'], 2) }}</td>
                                    <td class="px-6 py-3.5 text-right">{{ $row['count'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-8 text-center text-slate-400">{{ __('No expenses recorded in this period.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Revenue by Tenant') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:bg-slate-950">
                            <tr>
                                <th class="px-6 py-3.5">{{ __('Institution') }}</th>
                                <th class="px-6 py-3.5 text-right">{{ __('Payments') }}</th>
                                <th class="px-6 py-3.5 text-right">{{ __('Revenue') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600 dark:divide-slate-800 dark:text-slate-400">
                            @forelse($report['by_tenant'] ?? [] as $row)
                                <tr>
                                    <td class="px-6 py-3.5 font-semibold text-slate-900 dark:text-white">{{ $row['school'] }}</td>
                                    <td class="px-6 py-3.5 text-right">{{ $row['count'] }}</td>
                                    <td class="px-6 py-3.5 text-right font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($row['total'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-8 text-center text-slate-400">{{ __('No payments received in this period.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
