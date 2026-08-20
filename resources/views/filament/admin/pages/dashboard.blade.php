<x-filament-panels::page>
    <div class="space-y-8">
        
        <!-- Welcome Banner with Glassmorphism -->
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-900 via-slate-900 to-slate-950 p-8 text-white shadow-xl border border-indigo-500/20">
            <div class="relative z-10 max-w-2xl space-y-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    {{ __('Super Admin Enterprise Control Center') }}
                </span>
                <h1 class="text-3xl font-extrabold tracking-tight">Welcome back, {{ auth()->user()->name }}</h1>
                <p class="text-sm text-slate-300 leading-relaxed">
                    {{ __('Manage multi-tenant school subscriptions, review manual payment confirmations, monitor platform telemetry, and communicate globally with all registered institutions.') }}
                </p>
                <div class="pt-2 flex flex-wrap gap-3">
                    <a href="/platform/schools" class="px-4 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold rounded-xl text-xs shadow-lg shadow-emerald-500/20 transition-all flex items-center gap-2">
                        <x-heroicon-o-building-office-2 class="w-4 h-4"/>
                        {{ __('Manage Institutions') }}
                    </a>
                    <a href="/platform/pending-payments" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white font-bold rounded-xl text-xs backdrop-blur-md transition-all flex items-center gap-2 border border-white/10">
                        <x-heroicon-o-clock class="w-4 h-4"/>
                        Pending Confirmations ({{ $pendingConfirmations }})
                    </a>
                </div>
            </div>
            <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
        </div>

        <!-- Metric Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Total Institutions') }}</span>
                    <h3 class="text-3xl font-extrabold text-slate-950 dark:text-white mt-2">{{ $totalTenants }}</h3>
                    <p class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold mt-1">{{ $activeTenants }} Active online</p>
                </div>
                <div class="p-3 rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40">
                    <x-heroicon-o-building-office-2 class="w-7 h-7"/>
                </div>
            </div>

            <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Total Gross Revenue') }}</span>
                    <h3 class="text-3xl font-extrabold text-slate-950 dark:text-white mt-2">${{ number_format($totalRevenue, 2) }}</h3>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold mt-1">{{ __('Verified collections') }}</p>
                </div>
                <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40">
                    <x-heroicon-o-banknotes class="w-7 h-7"/>
                </div>
            </div>

            <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Pending Approvals') }}</span>
                    <h3 class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 mt-2">{{ $pendingTenants }}</h3>
                    <p class="text-xs text-amber-500 font-semibold mt-1">{{ __('Awaiting onboarding') }}</p>
                </div>
                <div class="p-3 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/40">
                    <x-heroicon-o-user-plus class="w-7 h-7"/>
                </div>
            </div>

            <div class="p-6 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Manual Slips Due') }}</span>
                    <h3 class="text-3xl font-extrabold text-rose-600 dark:text-rose-400 mt-2">{{ $pendingConfirmations }}</h3>
                    <p class="text-xs text-rose-500 font-semibold mt-1">{{ __('Require admin review') }}</p>
                </div>
                <div class="p-3 rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/40">
                    <x-heroicon-o-clock class="w-7 h-7"/>
                </div>
            </div>

        </div>

        <!-- Two Columns: Recent Transactions & Pending Schools -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Recent Transactions Ledger -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Recent Transactions') }}</h3>
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
                                    <td colspan="4" class="px-6 py-6 text-center text-slate-400">{{ __('No transactions recorded yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pending Schools Onboarding -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Pending Institutions Onboarding') }}</h3>
                    <a href="/platform/schools" class="text-xs font-bold text-indigo-600 hover:underline">{{ __('View All →') }}</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-950 text-slate-400 uppercase font-bold text-[10px]">
                            <tr>
                                <th class="px-6 py-3">{{ __('Name') }}</th>
                                <th class="px-6 py-3">{{ __('Subdomain') }}</th>
                                <th class="px-6 py-3">{{ __('Region') }}</th>
                                <th class="px-6 py-3 text-right">{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-600 dark:text-slate-400">
                            @forelse($pendingSchools as $school)
                                <tr>
                                    <td class="px-6 py-3.5 font-bold text-slate-900 dark:text-white">{{ $school->name }}</td>
                                    <td class="px-6 py-3.5 font-mono text-indigo-600">{{ $school->subdomain }}</td>
                                    <td class="px-6 py-3.5 uppercase text-[10px]">{{ $school->region }}</td>
                                    <td class="px-6 py-3.5 text-right">
                                        <a href="/platform/schools" class="px-3 py-1 bg-indigo-600 text-white rounded-lg text-[10px] font-bold">{{ __('Review') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-6 text-center text-slate-400">{{ __('No pending institutions.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</x-filament-panels::page>
