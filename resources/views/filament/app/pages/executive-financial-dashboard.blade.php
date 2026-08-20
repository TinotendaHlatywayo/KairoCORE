<x-filament-panels::page>
    <div class="space-y-4">
        <style>[x-cloak] { display: none !important; }</style>
        <div
            id="chart-data"
            class="hidden"
            data-json='{{ json_encode([
                'trend' => $revenueExpenseTrend,
                'cashFlow' => $cashFlowTimeline,
                'revenueBreakdown' => $revenueBreakdown,
                'feeAgeing' => $feeAgeing,
                'expenseBreakdown' => $expenseBreakdown,
                'collectionRate' => $collectionRateTrend,
                'yoy' => $yoyComparison,
                'forecast' => $revenueForecast,
                'budgetVariance' => $budgetVariance,
                'paymentMethods' => $paymentMethodBreakdown,
            ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }}'
        ></div>

        <!-- Header with Date Range Filter & Export Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ __('Executive Financial Operations Center') }}</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Real-time financial analytics and performance monitoring') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <label for="date-range" class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ __('Period:') }}</label>
                <select
                    id="date-range"
                    wire:model.live="dateRange"
                    class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-2.5 py-1.5 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                >
                    @foreach($this->getDateRangeOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button
                    wire:click="exportDashboardCSV"
                    class="flex items-center gap-1.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-2.5 py-1.5 text-sm font-semibold text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                >
                    <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                    {{ __('Export CSV') }}
                </button>
                <button
                    wire:click="refreshDashboard"
                    class="flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition"
                >
                    <x-heroicon-o-arrow-path class="h-4 w-4" />
                    {{ __('Refresh') }}
                </button>
            </div>
        </div>

        <!-- KPI Cards Row -->
        <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
            <!-- Total Revenue -->
            <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500">{{ __('Total Revenue') }}</span>
                    <span class="rounded-lg bg-emerald-50 p-1 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                        <x-heroicon-o-banknotes class="h-3.5 w-3.5" />
                    </span>
                </div>
                <div class="mt-1.5">
                    <div class="text-lg font-black tracking-tight text-gray-900 dark:text-white">
                        ${{ number_format($summary['total_revenue'] ?? 0, 2) }}
                    </div>
                </div>
                <div class="mt-1.5 h-8" id="revenue-sparkline"></div>
            </div>

            <!-- Total Expenses -->
            <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500">{{ __('Total Expenses') }}</span>
                    <span class="rounded-lg bg-rose-50 p-1 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400">
                        <x-heroicon-o-arrow-trending-down class="h-3.5 w-3.5" />
                    </span>
                </div>
                <div class="mt-1.5">
                    <div class="text-lg font-black tracking-tight text-gray-900 dark:text-white">
                        ${{ number_format($summary['total_expenses'] ?? 0, 2) }}
                    </div>
                </div>
                <div class="mt-1.5 h-8" id="expense-sparkline"></div>
            </div>

            <!-- Net Surplus / Deficit -->
            <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500">{{ __('Net Surplus') }}</span>
                    <span class="rounded-lg bg-blue-50 p-1 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                        <x-heroicon-o-currency-dollar class="h-3.5 w-3.5" />
                    </span>
                </div>
                <div class="mt-1.5">
                    <div class="text-lg font-black tracking-tight {{ ($summary['net_surplus'] ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                        ${{ number_format($summary['net_surplus'] ?? 0, 2) }}
                    </div>
                </div>
                <div class="mt-1.5 h-8" id="net-sparkline"></div>
            </div>

            <!-- Outstanding Student Fees -->
            <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500">{{ __('Outstanding Fees (AR)') }}</span>
                    <span class="rounded-lg bg-amber-50 p-1 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                        <x-heroicon-o-exclamation-triangle class="h-3.5 w-3.5" />
                    </span>
                </div>
                <div class="mt-1.5">
                    <div class="text-lg font-black tracking-tight text-amber-600 dark:text-amber-400">
                        ${{ number_format($summary['outstanding_student_fees'] ?? 0, 2) }}
                    </div>
                </div>
                <div class="mt-1.5 h-8" id="ar-sparkline"></div>
            </div>

            <!-- Collection Rate -->
            <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-500">{{ __('Collection Rate') }}</span>
                    <span class="rounded-lg bg-indigo-50 p-1 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                        <x-heroicon-o-check-circle class="h-3.5 w-3.5" />
                    </span>
                </div>
                <div class="mt-1.5">
                    <div class="text-lg font-black tracking-tight text-indigo-600 dark:text-indigo-400" id="collection-rate-value"></div>
                </div>
                <div class="mt-1.5 h-8" id="collection-sparkline"></div>
            </div>
        </div>

        <!-- Analytics View Switcher -->
        <div x-data="{ activeAnalyticsTab: 'trends', switchAnalyticsTab(tab) { this.activeAnalyticsTab = tab; this.$nextTick(() => window.dispatchEvent(new Event('resize'))); } }">
            <!-- Tab Buttons -->
            <div class="flex flex-wrap items-center gap-2 mb-3">
                <button
                    @click="switchAnalyticsTab('trends')"
                    :class="activeAnalyticsTab === 'trends' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'"
                    class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition"
                >
                    <x-heroicon-o-chart-bar class="h-3.5 w-3.5" />
                    {{ __('Cash Flow & Trends') }}
                </button>
                <button
                    @click="switchAnalyticsTab('allocations')"
                    :class="activeAnalyticsTab === 'allocations' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'"
                    class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition"
                >
                    <x-heroicon-o-circle-stack class="h-3.5 w-3.5" />
                    {{ __('Allocations & Ageing') }}
                </button>
                <button
                    @click="switchAnalyticsTab('forecasts')"
                    :class="activeAnalyticsTab === 'forecasts' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'"
                    class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition"
                >
                    <x-heroicon-o-arrow-trending-up class="h-3.5 w-3.5" />
                    {{ __('Forecasts & YoY') }}
                </button>
            </div>

            <!-- Tab 1: Cash Flow & Trends -->
            <div x-show="activeAnalyticsTab === 'trends'" x-cloak>
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
                    <!-- Revenue vs Expense Trend (8 cols) -->
                    <div class="lg:col-span-8 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <x-heroicon-o-chart-bar class="h-4 w-4 text-emerald-600" />
                                {{ __('Revenue vs Expense Trend') }}
                            </h3>
                        </div>
                        <div id="revenue-expense-chart" class="h-72 w-full"></div>
                    </div>

                    <!-- Cash Flow Waterfall (4 cols) -->
                    <div class="lg:col-span-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <x-heroicon-o-arrow-path class="h-4 w-4 text-blue-600" />
                                {{ __('Cash Flow Waterfall') }}
                            </h3>
                        </div>
                        <div id="cash-flow-chart" class="h-72 w-full"></div>
                    </div>
                </div>

                <!-- Collection Rate Trend (full width, part of trends) -->
                <div class="mt-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <x-heroicon-o-check-circle class="h-4 w-4 text-indigo-600" />
                                {{ __('Monthly Collection Rate') }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Percentage of invoiced amount collected each month') }}</p>
                        </div>
                    </div>
                    <div id="collection-rate-chart" class="h-56 w-full"></div>
                </div>
            </div>

            <!-- Tab 2: Allocations & Ageing -->
            <div x-show="activeAnalyticsTab === 'allocations'" x-cloak>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
                    <!-- Revenue Breakdown Donut -->
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-2">
                            <x-heroicon-o-circle-stack class="h-4 w-4 text-emerald-600" />
                            {{ __('Revenue Breakdown') }}
                        </h3>
                        <div id="revenue-breakdown-chart" class="h-64 w-full"></div>
                    </div>

                    <!-- Expense Breakdown -->
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-2">
                            <x-heroicon-o-arrow-trending-down class="h-4 w-4 text-rose-600" />
                            {{ __('Expense Breakdown') }}
                        </h3>
                        <div id="expense-breakdown-chart" class="h-64 w-full"></div>
                    </div>

                    <!-- Fee Ageing Analysis -->
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-2">
                            <x-heroicon-o-clock class="h-4 w-4 text-amber-600" />
                            {{ __('Fee Ageing Analysis') }}
                        </h3>
                        <div id="fee-ageing-chart" class="h-64 w-full"></div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Forecasts & YoY -->
            <div x-show="activeAnalyticsTab === 'forecasts'" x-cloak>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                    <!-- Revenue YoY Comparison -->
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <x-heroicon-o-arrow-trending-up class="h-4 w-4 text-purple-600" />
                                    {{ __('Year-over-Year Revenue') }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Current year vs previous year collections') }}</p>
                            </div>
                        </div>
                        <div class="mb-3 flex items-center justify-between gap-3 text-center">
                            <div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400">{{ $yoyComparison['last_year'] }} vs {{ $yoyComparison['current_year'] }}</div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-center">
                                    <div class="text-xl font-black text-gray-900 dark:text-white">${{ number_format($yoyComparison['last_year_revenue'] ?? 0, 0) }}</div>
                                    <div class="text-[9px] text-gray-500">{{ $yoyComparison['last_year'] }}</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-xl font-black text-gray-900 dark:text-white">${{ number_format($yoyComparison['current_revenue'] ?? 0, 0) }}</div>
                                    <div class="text-[9px] text-gray-500">{{ $yoyComparison['current_year'] }}</div>
                                </div>
                                <div class="text-center">
                                    @php $yoyGrowth = $yoyComparison['yoy_growth_percent'] ?? null; @endphp
                                    @if($yoyGrowth === null)
                                        <div class="text-xl font-black text-slate-400 dark:text-slate-500">N/A</div>
                                    @else
                                        <div class="text-xl font-black {{ $yoyGrowth >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                            {{ $yoyGrowth >= 0 ? '+' : '' }}{{ $yoyGrowth }}%
                                        </div>
                                    @endif
                                    <div class="text-[9px] text-gray-500">{{ __('YoY Growth') }}</div>
                                </div>
                            </div>
                        </div>
                        <div id="yoy-comparison-chart" class="h-44 w-full"></div>
                    </div>

                    <!-- Revenue Forecast -->
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <x-heroicon-o-calendar-days class="h-4 w-4 text-blue-600" />
                                    {{ __('Revenue Forecast (6 Months)') }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Projected revenue based on historical trend') }}</p>
                            </div>
                        </div>
                        <div id="forecast-chart" class="h-64 w-full"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Budget Variance -->
        @if(count($budgetVariance) > 0)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-building-library class="h-4 w-4 text-amber-600" />
                            {{ __('Expenditure vs Estimated Baseline') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Actual spend per category vs an estimated baseline (no formal budgets set yet)') }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div id="budget-variance-chart" class="h-64 w-full"></div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 dark:border-slate-800">
                                    <th class="px-3 py-2 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Category') }}</th>
                                    <th class="px-3 py-2 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Est. Baseline') }}</th>
                                    <th class="px-3 py-2 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Actual') }}</th>
                                    <th class="px-3 py-2 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Difference') }}</th>
                                    <th class="px-3 py-2 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Used %') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-600 dark:text-slate-400">
                                @foreach($budgetVariance as $item)
                                    @php
                                        $pct = $item['percentage'] ?? 0;
                                        $pctColor = $pct > 90 ? 'text-rose-600 dark:text-rose-400' : ($pct > 70 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400');
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-950/20">
                                        <td class="px-3 py-2 font-medium text-slate-900 dark:text-white">{{ $item['category'] }}</td>
                                        <td class="px-3 py-2">${{ number_format($item['budget'], 2) }}</td>
                                        <td class="px-3 py-2">${{ number_format($item['actual'], 2) }}</td>
                                        <td class="px-3 py-2 font-semibold {{ $item['variance'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                            ${{ number_format($item['variance'], 2) }}
                                        </td>
                                        <td class="px-3 py-2">
                                            <div class="flex items-center gap-2">
                                                <div class="flex-1 h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full {{ $pct > 90 ? 'bg-rose-500' : ($pct > 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ min($pct, 100) }}%"></div>
                                                </div>
                                                <span class="text-xs font-bold {{ $pctColor }}">{{ $pct }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        <!-- Detail Tables Section with Tabs -->
        <div x-data="{ activeDetailTab: @entangle('activeDetailTab') }" class="rounded-xl border border-slate-200/60 bg-white shadow-sm dark:border-slate-800 dark:bg-gray-900 overflow-hidden">
            <!-- Tab Headers -->
            <div class="px-4 py-3 border-b border-slate-200/60 dark:border-slate-800 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-1.5">
                    @foreach($this->getDetailTabs() as $tabKey => $tabLabel)
                        <button
                            wire:click="$set('activeDetailTab', '{{ $tabKey }}')"
                            class="px-3 py-1.5 text-xs font-bold rounded-lg transition {{ $activeDetailTab === $tabKey ? 'bg-emerald-600 text-white shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700' }}"
                        >
                            {{ $tabLabel }}
                        </button>
                    @endforeach
                </div>
                <div class="text-xs text-slate-400">
                    {{ __('Showing live records') }}
                </div>
            </div>

            <!-- Tab 1: Top Debtors -->
            <div x-show="activeDetailTab === 'debtors'" x-cloak>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-slate-200/60 dark:border-slate-800 bg-slate-50 dark:bg-slate-950">
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Student') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Admission #') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Invoices') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Total Outstanding') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Oldest Due') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Ageing Bucket') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-600 dark:text-slate-400">
                            @forelse($topDebtors as $debtor)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-950/20">
                                    <td class="px-4 py-2.5 font-medium text-slate-900 dark:text-white">{{ $debtor->student_name }}</td>
                                    <td class="px-4 py-2.5 text-slate-500">{{ $debtor->admission_number }}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                            {{ $debtor->invoice_count }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 font-bold text-rose-600 dark:text-rose-400">${{ number_format($debtor->total_outstanding, 2) }}</td>
                                    <td class="px-4 py-2.5 text-slate-500">{{ $debtor->oldest_due_date ? \Carbon\Carbon::parse($debtor->oldest_due_date)->format('M d, Y') : 'N/A' }}</td>
                                    <td class="px-4 py-2.5">
                                        @php
                                            $age = $debtor->oldest_due_date ? \Carbon\Carbon::parse($debtor->oldest_due_date)->diffInDays(now()) : 0;
                                            $bucket = $age <= 30 ? '0-30' : ($age <= 60 ? '31-60' : ($age <= 90 ? '61-90' : '90+'));
                                            $colors = ['0-30' => 'bg-emerald-100 text-emerald-700', '31-60' => 'bg-amber-100 text-amber-700', '61-90' => 'bg-orange-100 text-orange-700', '90+' => 'bg-rose-100 text-rose-700'];
                                            $color = $colors[$bucket] ?? 'bg-slate-100 text-slate-700';
                                        @endphp
                                        <span class="px-2 py-1 rounded-full text-xs font-bold {{ $color }} dark:bg-slate-800 dark:text-slate-300">
                                            {{ $bucket }} Days
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-slate-400">{{ __('No outstanding invoices. All accounts are current.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: Aged Receivables Detail -->
            <div x-show="activeDetailTab === 'receivables'" x-cloak>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-slate-200/60 dark:border-slate-800 bg-slate-50 dark:bg-slate-950">
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Invoice #') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Student') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Total') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Paid') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Balance') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Due Date') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Bucket') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-600 dark:text-slate-400">
                            @forelse($agedReceivablesDetail as $inv)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-950/20">
                                    <td class="px-4 py-2.5 font-medium text-slate-900 dark:text-white">{{ $inv['invoice_number'] }}</td>
                                    <td class="px-4 py-2.5">{{ $inv['student_name'] }} ({{ $inv['student_number'] }})</td>
                                    <td class="px-4 py-2.5 font-semibold">${{ number_format($inv['total_amount'], 2) }}</td>
                                    <td class="px-4 py-2.5 text-emerald-600">${{ number_format($inv['paid_amount'], 2) }}</td>
                                    <td class="px-4 py-2.5 font-bold text-rose-600">${{ number_format($inv['balance_amount'], 2) }}</td>
                                    <td class="px-4 py-2.5 text-slate-500">{{ $inv['due_date'] }}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400">
                                            {{ $inv['ageing_bucket'] }} Days
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 uppercase text-[10px] font-bold text-slate-500">{{ $inv['status'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-slate-400">{{ __('No aged receivables found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 3: Expense Register -->
            <div x-show="activeDetailTab === 'expenses'" x-cloak>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-slate-200/60 dark:border-slate-800 bg-slate-50 dark:bg-slate-950">
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Ref #') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Category / Type') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Supplier') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Amount') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Date') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Status') }}</th>
                                <th class="px-4 py-2.5 font-semibold text-slate-700 dark:text-slate-300 uppercase text-[10px] tracking-wider">{{ __('Recorded By') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-600 dark:text-slate-400">
                            @forelse($expenseRegister as $exp)
                                <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-950/20">
                                    <td class="px-4 py-2.5 font-medium text-slate-900 dark:text-white">{{ $exp->reference_number ?? 'N/A' }}</td>
                                    <td class="px-4 py-2.5">
                                        <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $exp->category ?? 'Uncategorized' }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $exp->type }}</div>
                                    </td>
                                    <td class="px-4 py-2.5">{{ $exp->supplier ?? 'Direct Expense' }}</td>
                                    <td class="px-4 py-2.5 font-bold text-rose-600">${{ number_format($exp->amount, 2) }}</td>
                                    <td class="px-4 py-2.5 text-slate-500">{{ $exp->expense_date }}</td>
                                    <td class="px-4 py-2.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ in_array($exp->status, ['approved', 'paid']) ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400' }}">
                                            {{ $exp->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-500">{{ $exp->recorded_by ?? 'System' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-slate-400">{{ __('No expense records found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 4: Payment Methods -->
            <div x-show="activeDetailTab === 'payments'" x-cloak>
                <div class="p-4">
                    <div id="payment-method-chart" class="h-56 w-full mb-4"></div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach($paymentMethodBreakdown as $method => $data)
                            <div class="p-3 rounded-xl border border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50">
                                <div class="text-xs font-bold uppercase text-slate-400">{{ $method }}</div>
                                <div class="text-lg font-black text-blue-600 dark:text-blue-400 mt-1">${{ number_format($data['total'], 2) }}</div>
                                <div class="text-xs text-slate-500 mt-1">{{ $data['count'] }} total transactions</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    @script
    <script src="https://cdn.plot.ly/plotly-2.35.2.min.js"></script>
    <script>
        function getBaseLayout() {
            const isDark = document.documentElement.classList.contains('dark');
            return {
                paper_bgcolor: 'rgba(0,0,0,0)',
                plot_bgcolor: 'rgba(0,0,0,0)',
                font: { family: 'Inter, system-ui, sans-serif', color: isDark ? '#e5e7eb' : '#374151', size: 11 },
                margin: { t: 10, b: 40, l: 50, r: 20 },
                xaxis: {
                    gridcolor: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)',
                    zerolinecolor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                    tickfont: { size: 10 },
                },
                yaxis: {
                    gridcolor: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)',
                    zerolinecolor: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
                    tickfont: { size: 10 },
                    tickprefix: '$',
                    tickformat: ',.0f',
                },
            };
        }

        function renderSparkline(elId, data, color) {
            const el = document.getElementById(elId);
            if (!el || !data || data.length === 0) return;
            Plotly.newPlot(el, [{
                x: data.map((_, i) => i), y: data, type: 'scatter', mode: 'lines',
                line: { color, width: 2, shape: 'spline' }, fill: 'tozeroy',
                fillcolor: color.replace(')', ', 0.1)').replace('rgb', 'rgba'),
                hoverinfo: 'skip',
            }], {
                margin: { t: 0, b: 0, l: 0, r: 0 }, xaxis: { visible: false }, yaxis: { visible: false },
                paper_bgcolor: 'rgba(0,0,0,0)', plot_bgcolor: 'rgba(0,0,0,0)',
            }, { responsive: true, displayModeBar: false, staticPlot: true });
        }

        function getChartData() {
            const el = document.getElementById('chart-data');
            if (!el) return null;
            try { return JSON.parse(el.dataset.json); } catch (e) { return null; }
        }

        function initDashboardCharts() {
            const data = getChartData();
            if (!data) return;

            const trendData = data.trend || {};
            const cashFlowData = data.cashFlow || {};
            const revenueBreakdown = data.revenueBreakdown || {};
            const feeAgeing = data.feeAgeing || {};
            const expenseBreakdown = data.expenseBreakdown || {};
            const collectionRateData = data.collectionRate || {};
            const yoyData = data.yoy || {};
            const forecastData = data.forecast || {};
            const budgetVariance = data.budgetVariance || [];
            const paymentMethods = data.paymentMethods || {};

            // Set collection rate value (overall rate across the selected period)
            const rates = collectionRateData.collection_rate || [];
            const invoicedTotal = (collectionRateData.invoiced || []).reduce((a, b) => a + b, 0);
            const collectedTotal = (collectionRateData.collected || []).reduce((a, b) => a + b, 0);
            const overallRate = invoicedTotal > 0 ? ((collectedTotal / invoicedTotal) * 100) : 0;
            const crEl = document.getElementById('collection-rate-value');
            if (crEl) crEl.textContent = overallRate.toFixed(1) + '%';

            if (typeof Plotly === 'undefined') return;

            // Sparklines
            renderSparkline('revenue-sparkline', trendData.revenue || [], '#10b981');
            renderSparkline('expense-sparkline', trendData.expenses || [], '#f43f5e');
            renderSparkline('net-sparkline', trendData.net || [], '#3b82f6');
            renderSparkline('ar-sparkline', Object.values(feeAgeing || {}), '#f59e0b');
            renderSparkline('collection-sparkline', rates, '#8b5cf6');

            // Revenue vs Expense Trend
            if (trendData.labels && trendData.labels.length > 0) {
                Plotly.newPlot('revenue-expense-chart', [
                    { x: trendData.labels, y: trendData.revenue, name: 'Revenue', type: 'scatter', mode: 'lines+markers', line: { color: '#10b981', width: 3, shape: 'spline' }, marker: { size: 6 }, fill: 'tozeroy', fillcolor: 'rgba(16,185,129,0.1)', hovertemplate: 'Revenue: $%{y:,.2f}<extra></extra>' },
                    { x: trendData.labels, y: trendData.expenses, name: 'Expenses', type: 'scatter', mode: 'lines+markers', line: { color: '#f43f5e', width: 3, shape: 'spline' }, marker: { size: 6 }, fill: 'tozeroy', fillcolor: 'rgba(244,63,94,0.1)', hovertemplate: 'Expenses: $%{y:,.2f}<extra></extra>' },
                    { x: trendData.labels, y: trendData.net, name: 'Net Position', type: 'scatter', mode: 'lines+markers', line: { color: '#3b82f6', width: 2, dash: 'dot', shape: 'spline' }, marker: { size: 5 }, hovertemplate: 'Net: $%{y:,.2f}<extra></extra>' },
                ], { ...getBaseLayout(), legend: { orientation: 'h', y: -0.15, x: 0.5, xanchor: 'center' }, yaxis: { ...getBaseLayout().yaxis, title: 'Amount ($)' }, hovermode: 'x unified' }, { responsive: true, displayModeBar: false });
            } else {
                document.getElementById('revenue-expense-chart').innerHTML = '<div class="h-full flex items-center justify-center text-gray-400 text-sm">No data available</div>';
            }

            // Cash Flow
            if (cashFlowData.labels && cashFlowData.labels.length > 0) {
                Plotly.newPlot('cash-flow-chart', [
                    { x: cashFlowData.labels, y: cashFlowData.inflow, name: 'Cash In', type: 'bar', marker: { color: 'rgba(16,185,129,0.8)' }, hovertemplate: 'Inflow: $%{y:,.2f}<extra></extra>' },
                    { x: cashFlowData.labels, y: cashFlowData.outflow, name: 'Cash Out', type: 'bar', marker: { color: 'rgba(244,63,94,0.8)' }, hovertemplate: 'Outflow: $%{y:,.2f}<extra></extra>' },
                    { x: cashFlowData.labels, y: cashFlowData.net_flow, name: 'Net Flow', type: 'scatter', mode: 'lines+markers', line: { color: '#3b82f6', width: 3, shape: 'spline' }, marker: { size: 6 }, hovertemplate: 'Net: $%{y:,.2f}<extra></extra>' },
                    { x: cashFlowData.labels, y: cashFlowData.cumulative_flow, name: 'Cumulative Cash', type: 'scatter', mode: 'lines', line: { color: '#8b5cf6', width: 2, dash: 'dash', shape: 'spline' }, yaxis: 'y2', hovertemplate: 'Cumulative: $%{y:,.2f}<extra></extra>' },
                ], { ...getBaseLayout(), barmode: 'relative', legend: { orientation: 'h', y: -0.15, x: 0.5, xanchor: 'center' }, yaxis: { title: 'Monthly Flow ($)', side: 'left' }, yaxis2: { title: 'Cumulative ($)', side: 'right', overlaying: 'y', showgrid: false }, hovermode: 'x unified' }, { responsive: true, displayModeBar: false });
            } else {
                document.getElementById('cash-flow-chart').innerHTML = '<div class="h-full flex items-center justify-center text-gray-400 text-sm">No data available</div>';
            }

            // Revenue Breakdown Donut
            const rbCats = Object.keys(revenueBreakdown);
            const rbVals = Object.values(revenueBreakdown);
            if (rbCats.length > 0) {
                const rbColors = ['#10b981','#3b82f6','#8b5cf6','#f59e0b','#ec4899','#06b6d4','#84cc16','#f97316'];
                Plotly.newPlot('revenue-breakdown-chart', [{
                    labels: rbCats.map(c => c || 'General Revenue'), values: rbVals, type: 'pie', hole: 0.55,
                    marker: { colors: rbColors.slice(0, rbCats.length) }, textinfo: 'label+percent', textposition: 'inside',
                    hovertemplate: '%{label}<br>$%{value:,.2f} (%{percent})<extra></extra>',
                }], { ...getBaseLayout(), showlegend: false, margin: { t: 10, b: 10, l: 10, r: 10 } }, { responsive: true, displayModeBar: false });
            } else {
                document.getElementById('revenue-breakdown-chart').innerHTML = '<div class="h-full flex items-center justify-center text-gray-400 text-sm">No revenue data</div>';
            }

            // Fee Ageing
            const ageBuckets = [
                { key: '0_30_days', label: '0-30 Days', color: '#10b981' },
                { key: '31_60_days', label: '31-60 Days', color: '#f59e0b' },
                { key: '61_90_days', label: '61-90 Days', color: '#f97316' },
                { key: '90_plus_days', label: '90+ Days', color: '#f43f5e' },
            ];
            const ageVals = ageBuckets.map(b => feeAgeing[b.key] || 0);
            Plotly.newPlot('fee-ageing-chart', [{
                x: ageBuckets.map(b => b.label), y: ageVals, type: 'bar',
                marker: { color: ageBuckets.map(b => b.color) },
                text: ageVals.map(v => '$' + v.toLocaleString(undefined, {minimumFractionDigits: 0})), textposition: 'auto',
                hovertemplate: '%{x}<br>Amount: $%{y:,.2f}<extra></extra>',
            }], { ...getBaseLayout(), yaxis: { ...getBaseLayout().yaxis, title: 'Outstanding ($)' }, showlegend: false }, { responsive: true, displayModeBar: false });

            // Expense Breakdown
            const ebCats = Object.keys(expenseBreakdown);
            const ebVals = Object.values(expenseBreakdown);
            if (ebCats.length > 0) {
                const ebColors = ['#f43f5e','#f97316','#f59e0b','#ec4899','#8b5cf6','#3b82f6','#06b6d4','#10b981'];
                Plotly.newPlot('expense-breakdown-chart', [{
                    x: ebVals, y: ebCats.map(c => c || 'Uncategorized'), type: 'bar', orientation: 'h',
                    marker: { color: ebColors.slice(0, ebCats.length) },
                    text: ebVals.map(v => '$' + v.toLocaleString(undefined, {minimumFractionDigits: 0})), textposition: 'outside',
                    hovertemplate: '%{y}<br>Amount: $%{x:,.2f}<extra></extra>',
                }], { ...getBaseLayout(), xaxis: { ...getBaseLayout().xaxis, title: 'Amount ($)' }, yaxis: { automargin: true }, margin: { t: 10, b: 40, l: 100, r: 40 }, showlegend: false }, { responsive: true, displayModeBar: false });
            } else {
                document.getElementById('expense-breakdown-chart').innerHTML = '<div class="h-full flex items-center justify-center text-gray-400 text-sm">No expense data</div>';
            }

            // Collection Rate
            if (collectionRateData.labels && collectionRateData.labels.length > 0) {
                Plotly.newPlot('collection-rate-chart', [
                    { x: collectionRateData.labels, y: collectionRateData.invoiced, name: 'Invoiced', type: 'bar', marker: { color: 'rgba(59,130,246,0.5)' }, hovertemplate: 'Invoiced: $%{y:,.2f}<extra></extra>' },
                    { x: collectionRateData.labels, y: collectionRateData.collected, name: 'Collected', type: 'bar', marker: { color: 'rgba(16,185,129,0.8)' }, hovertemplate: 'Collected: $%{y:,.2f}<extra></extra>' },
                    { x: collectionRateData.labels, y: collectionRateData.collection_rate, name: 'Collection Rate %', type: 'scatter', mode: 'lines+markers', line: { color: '#8b5cf6', width: 3, shape: 'spline' }, marker: { size: 6 }, yaxis: 'y2', hovertemplate: 'Rate: %{y:.1f}%<extra></extra>' },
                ], { ...getBaseLayout(), barmode: 'group', legend: { orientation: 'h', y: -0.15, x: 0.5, xanchor: 'center' }, yaxis: { title: 'Amount ($)', side: 'left' }, yaxis2: { title: 'Rate (%)', side: 'right', overlaying: 'y', range: [0, 110], showgrid: false }, hovermode: 'x unified' }, { responsive: true, displayModeBar: false });
            } else {
                document.getElementById('collection-rate-chart').innerHTML = '<div class="h-full flex items-center justify-center text-gray-400 text-sm">No data available</div>';
            }

            // YoY Comparison
            Plotly.newPlot('yoy-comparison-chart', [{
                x: [String(yoyData.last_year), String(yoyData.current_year)],
                y: [yoyData.last_year_revenue || 0, yoyData.current_revenue || 0],
                type: 'bar', marker: { color: ['rgba(100,116,214,0.7)', 'rgba(16,185,129,0.9)'] },
                text: ['$' + (yoyData.last_year_revenue || 0).toLocaleString(undefined,{minimumFractionDigits:0}), '$' + (yoyData.current_revenue || 0).toLocaleString(undefined,{minimumFractionDigits:0})],
                textposition: 'auto', hovertemplate: '%{x}<br>Revenue: $%{y:,.2f}<extra></extra>',
            }], { ...getBaseLayout(), yaxis: { ...getBaseLayout().yaxis, title: 'Revenue ($)' }, showlegend: false }, { responsive: true, displayModeBar: false });

            // Revenue Forecast
            if (forecastData.labels && forecastData.labels.length > 0) {
                Plotly.newPlot('forecast-chart', [
                    { x: trendData.labels, y: trendData.revenue, name: 'Historical Revenue', type: 'scatter', mode: 'lines+markers', line: { color: '#3b82f6', width: 3, shape: 'spline' }, marker: { size: 5 }, hovertemplate: 'Revenue: $%{y:,.2f}<extra></extra>' },
                    { x: forecastData.labels, y: forecastData.forecast, name: 'Forecast', type: 'scatter', mode: 'lines+markers', line: { color: '#8b5cf6', width: 3, dash: 'dash', shape: 'spline' }, marker: { size: 6 }, fill: 'tozeroy', fillcolor: 'rgba(139,92,246,0.1)', hovertemplate: 'Forecast: $%{y:,.2f}<extra></extra>' },
                ], { ...getBaseLayout(), legend: { orientation: 'h', y: -0.15, x: 0.5, xanchor: 'center' }, yaxis: { ...getBaseLayout().yaxis, title: 'Amount ($)' }, hovermode: 'x unified' }, { responsive: true, displayModeBar: false });
            } else {
                document.getElementById('forecast-chart').innerHTML = '<div class="h-full flex items-center justify-center text-gray-400 text-sm">No forecast data</div>';
            }

            // Budget Variance
            if (budgetVariance && budgetVariance.length > 0) {
                Plotly.newPlot('budget-variance-chart', [
                    { x: budgetVariance.map(d => d.category || 'General'), y: budgetVariance.map(d => d.budget), name: 'Est. Baseline', type: 'bar', marker: { color: 'rgba(59,130,246,0.5)' }, hovertemplate: '%{x}<br>Baseline: $%{y:,.2f}<extra></extra>' },
                    { x: budgetVariance.map(d => d.category || 'General'), y: budgetVariance.map(d => d.actual), name: 'Actual', type: 'bar', marker: { color: budgetVariance.map(d => d.percentage > 90 ? 'rgba(244,63,94,0.8)' : (d.percentage > 70 ? 'rgba(245,158,11,0.8)' : 'rgba(16,185,129,0.8)')) }, hovertemplate: '%{x}<br>Actual: $%{y:,.2f}<extra></extra>' },
                ], { ...getBaseLayout(), barmode: 'group', legend: { orientation: 'h', y: -0.2, x: 0.5, xanchor: 'center' }, yaxis: { ...getBaseLayout().yaxis, title: 'Amount ($)' }, xaxis: { ...getBaseLayout().xaxis, tickangle: -30 }, hovermode: 'x unified' }, { responsive: true, displayModeBar: false });
            }

            // Payment Method Chart
            const pmMethods = Object.keys(paymentMethods);
            if (pmMethods.length > 0) {
                const pmEl = document.getElementById('payment-method-chart');
                if (pmEl) {
                    Plotly.newPlot(pmEl, [{
                        x: pmMethods.map(m => m.charAt(0).toUpperCase() + m.slice(1)),
                        y: pmMethods.map(m => paymentMethods[m].total),
                        type: 'bar', marker: { color: '#3b82f6' },
                        text: pmMethods.map(m => '$' + paymentMethods[m].total.toLocaleString(undefined, {minimumFractionDigits: 0})),
                        textposition: 'outside', hovertemplate: '%{x}<br>Total: $%{y:,.2f}<extra></extra>',
                    }], { ...getBaseLayout(), yaxis: { ...getBaseLayout().yaxis, title: 'Amount ($)' }, showlegend: false, margin: { t: 10, b: 40, l: 50, r: 20 } }, { responsive: true, displayModeBar: false });
                }
            }
        }

        // Register once: resize all rendered plots when the window size changes.
        window.addEventListener('resize', () => {
            document.querySelectorAll('[id$="-chart"],[id$="-sparkline"]').forEach(el => {
                if (el._fullLayout) Plotly.Plots.resize(el);
            });
        });

        // Re-render charts whenever the dashboard data changes (e.g. date range switch).
        window.initDashboardCharts = initDashboardCharts;
        $wire.on('dashboard-data-updated', () => {
            window.initDashboardCharts();
        });

        // Livewire script often runs after DOMContentLoaded has already fired, so
        // initialize immediately when the document is already parsed.
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDashboardCharts);
        } else {
            initDashboardCharts();
        }
    </script>
    @endscript
</x-filament-panels::page>