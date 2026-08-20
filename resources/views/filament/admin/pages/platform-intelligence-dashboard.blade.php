<x-filament-panels::page>
    <div class="space-y-8">
        
        <!-- Row 1: Financial & Tenant KPI Telemetry Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- Gross Lifetime Revenue -->
            <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Gross Revenue') }}</span>
                    <h3 class="text-3xl font-extrabold text-slate-950 dark:text-white mt-2">${{ number_format($totalRevenue, 2) }}</h3>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mt-1">{{ __('Verified platform settlements') }}</p>
                </div>
                <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40">
                    <x-heroicon-o-banknotes class="w-7 h-7"/>
                </div>
            </div>

            <!-- Current MRR -->
            <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Monthly Recurring (MRR)</span>
                    <h3 class="text-3xl font-extrabold text-slate-950 dark:text-white mt-2">${{ number_format($telemetry['mrr'] ?? 0, 2) }}</h3>
                    <p class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold mt-1">{{ __('Projected monthly run-rate') }}</p>
                </div>
                <div class="p-3 rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40">
                    <x-heroicon-o-chart-bar class="w-7 h-7"/>
                </div>
            </div>

            <!-- Outstanding Invoices -->
            <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Outstanding Invoices') }}</span>
                    <h3 class="text-3xl font-extrabold text-rose-600 dark:text-rose-400 mt-2">${{ number_format($outstandingInvoicesTotal, 2) }}</h3>
                    <p class="text-xs text-rose-500 font-semibold mt-1">{{ __('Pending tenant payments') }}</p>
                </div>
                <div class="p-3 rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/40">
                    <x-heroicon-o-exclamation-triangle class="w-7 h-7"/>
                </div>
            </div>

            <!-- Active Tenants -->
            <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Active Institutions') }}</span>
                    <h3 class="text-3xl font-extrabold text-slate-950 dark:text-white mt-2">{{ $telemetry['active_schools'] ?? 0 }} / {{ $telemetry['total_schools'] ?? 0 }}</h3>
                    <p class="text-xs text-amber-600 dark:text-amber-400 font-semibold mt-1">{{ __('Subscribed active tenants') }}</p>
                </div>
                <div class="p-3 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/40">
                    <x-heroicon-o-building-office-2 class="w-7 h-7"/>
                </div>
            </div>

        </div>

        <!-- Row 2: Subscription Plan Performance Breakdown -->
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Subscription Plans & Revenue Contribution') }}</h3>
                <span class="text-xs text-slate-400 font-semibold">{{ __('Live Plan Metrics') }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead class="bg-slate-50 dark:bg-slate-950 text-slate-400 uppercase font-bold text-[10px] tracking-wider">
                        <tr>
                            <th class="px-6 py-3.5">{{ __('Plan Name') }}</th>
                            <th class="px-6 py-3.5">{{ __('Monthly Price') }}</th>
                            <th class="px-6 py-3.5">{{ __('Active Subscribers') }}</th>
                            <th class="px-6 py-3.5">{{ __('Estimated MRR') }}</th>
                            <th class="px-6 py-3.5 text-right">{{ __('Projected ARR') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-600 dark:text-slate-400">
                        @forelse($planBreakdown as $plan)
                            <tr>
                                <td class="px-6 py-4 font-bold text-slate-900 dark:text-white">{{ $plan['name'] }}</td>
                                <td class="px-6 py-4 font-semibold">${{ number_format($plan['price'], 2) }}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400">
                                        {{ $plan['subscribers'] }} schools
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($plan['mrr'], 2) }}</td>
                                <td class="px-6 py-4 font-bold text-right text-slate-900 dark:text-white">${{ number_format($plan['arr'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-slate-400">{{ __('No SaaS plans configured.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Row 3: Recent Transactions & Outstanding Invoices Side-by-Side -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Recent Transactions Ledger -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Recent Financial Transactions') }}</h3>
                    <a href="/platform/saas-transactions" class="text-xs font-bold text-indigo-600 hover:underline">{{ __('View All →') }}</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-950 text-slate-400 uppercase font-bold text-[10px]">
                            <tr>
                                <th class="px-6 py-3">{{ __('Institution') }}</th>
                                <th class="px-6 py-3">{{ __('Amount') }}</th>
                                <th class="px-6 py-3">{{ __('Gateway') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-600 dark:text-slate-400">
                            @forelse($recentTransactions as $tx)
                                <tr>
                                    <td class="px-6 py-3.5 font-bold text-slate-900 dark:text-white">{{ $tx->school?->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-3.5 font-bold text-emerald-600">${{ number_format($tx->amount, 2) }}</td>
                                    <td class="px-6 py-3.5 uppercase text-[10px] font-bold text-slate-500">{{ $tx->payment_gateway_key }}</td>
                                    <td class="px-6 py-3.5 text-right font-medium">{{ $tx->processed_at?->format('M d, H:i') ?? $tx->created_at->format('M d, H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-6 text-center text-slate-400">{{ __('No completed transactions recorded.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Outstanding Invoices Ledger -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Outstanding Unpaid Invoices') }}</h3>
                    <a href="/platform/saas-invoices" class="text-xs font-bold text-indigo-600 hover:underline">{{ __('View All →') }}</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-950 text-slate-400 uppercase font-bold text-[10px]">
                            <tr>
                                <th class="px-6 py-3">{{ __('Invoice') }}</th>
                                <th class="px-6 py-3">{{ __('Institution') }}</th>
                                <th class="px-6 py-3">{{ __('Total Due') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Due Date') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-600 dark:text-slate-400">
                            @forelse($outstandingInvoices as $inv)
                                <tr>
                                    <td class="px-6 py-3.5 font-bold text-slate-900 dark:text-white">{{ $inv->invoice_number }}</td>
                                    <td class="px-6 py-3.5">{{ $inv->school?->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-3.5 font-bold text-rose-600">${{ number_format($inv->total, 2) }}</td>
                                    <td class="px-6 py-3.5 text-right font-medium text-rose-500">{{ $inv->due_date->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-6 text-center text-slate-400">{{ __('No outstanding invoices. All accounts are paid.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</x-filament-panels::page>
