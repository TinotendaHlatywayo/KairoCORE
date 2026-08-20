<x-filament::page>
    <div class="space-y-8 text-slate-900 dark:text-slate-100">

        {{-- ─── Header ─── --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">
                    {{ __('Academic Operations Center') }}
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ __('Guided academic setup — every step leads to the next.') }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if ($activeYearName)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400">
                        <x-heroicon-o-calendar class="h-4 w-4" />
                        {{ $activeYearName }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        <x-heroicon-o-calendar-date-range class="h-4 w-4" />
                        {{ $activeTerms }} {{ Str::plural(__('term'), $activeTerms) }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 dark:bg-rose-950/30 dark:text-rose-400">
                        <x-heroicon-o-exclamation-triangle class="h-4 w-4" />
                        {{ __('No active academic year') }}
                    </span>
                @endif
                <button wire:click="resetReadinessCache"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    <x-heroicon-s-arrow-path class="h-3.5 w-3.5" />
                    {{ __('Refresh') }}
                </button>
            </div>
        </div>

        {{-- ─── Readiness Hero ─── --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Score + progress breakdown --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Academic Readiness') }}</span>
                    <span @class([
                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                        'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' => ($readinessData['status'] ?? '') === 'excellent' || ($readinessData['status'] ?? '') === 'good',
                        'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400' => ($readinessData['status'] ?? '') === 'partial',
                        'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400' => in_array($readinessData['status'] ?? '', ['minimal', 'not_started']),
                    ])>
                        {{ __(str_replace('_', ' ', $readinessData['status'] ?? 'not_started')) }}
                    </span>
                </div>

                <div class="mt-5 flex items-center gap-5">
                    <div class="relative h-28 w-28 flex-shrink-0">
                        <svg viewBox="0 0 36 36" class="h-28 w-28 -rotate-90">
                            <circle cx="18" cy="18" r="15.915" fill="none" stroke="currentColor" stroke-width="3.5"
                                    class="text-slate-100 dark:text-slate-800" />
                            <circle cx="18" cy="18" r="15.915" fill="none"
                                    stroke="{{ $readinessData['score'] >= 75 ? '#10b981' : ($readinessData['score'] >= 50 ? '#f59e0b' : '#ef4444') }}"
                                    stroke-width="3.5" stroke-dasharray="{{ max(0, min(100, $readinessData['score'] ?? 0)) }} 100"
                                    stroke-linecap="round" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-extrabold text-slate-950 dark:text-white">{{ round($readinessData['score'] ?? 0) }}%</span>
                            <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ __('Complete') }}</span>
                        </div>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500 dark:text-slate-400">{{ __('Workflow') }}</span>
                            <span class="font-bold text-slate-900 dark:text-white">
                                {{ $progressData['completed_steps'] ?? 0 }}/{{ $progressData['total_steps'] ?? 0 }}
                                @if (($progressData['skipped_steps'] ?? 0) > 0)
                                    <span class="text-amber-500">(+{{ $progressData['skipped_steps'] }} skipped)</span>
                                @endif
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500 dark:text-slate-400">{{ __('Checks passed') }}</span>
                            <span class="font-bold text-slate-900 dark:text-white">
                                {{ $readinessData['details']['passed_checks'] ?? 0 }}/{{ $readinessData['details']['total_checks'] ?? 0 }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-slate-500 dark:text-slate-400">{{ __('Blocked steps') }}</span>
                            <span class="font-bold {{ ($progressData['blocked_steps'] ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }}">
                                {{ $progressData['blocked_steps'] ?? 0 }}
                            </span>
                        </div>
                    </div>
                </div>

                @if (! empty($readinessData['recommendations']))
                    <div class="mt-5 space-y-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                        @foreach (array_slice($readinessData['recommendations'], 0, 3) as $rec)
                            <div class="flex items-start gap-2 text-xs text-amber-700 dark:text-amber-400">
                                <x-heroicon-o-exclamation-triangle class="h-4 w-4 flex-shrink-0 mt-0.5" />
                                <span>{{ $rec }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- KPI grid --}}
            <div class="lg:col-span-2">
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    @forelse ($kpis as $kpi)
                        <div @class([
                            'rounded-xl border bg-white p-4 shadow-sm dark:bg-slate-900',
                            'border-slate-200 dark:border-slate-800',
                            'border-emerald-200 dark:border-emerald-900/40' => $kpi['status'] === 'good',
                            'border-amber-200 dark:border-amber-900/40' => $kpi['status'] === 'warning',
                            'border-rose-200 dark:border-rose-900/40' => $kpi['status'] === 'critical',
                            'border-indigo-200 dark:border-indigo-900/40' => $kpi['status'] === 'info',
                        ])>
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ __($kpi['label']) }}</span>
                                @if ($kpi['status'] === 'critical')
                                    <x-heroicon-o-exclamation-triangle class="h-4 w-4 text-rose-500" />
                                @elseif ($kpi['status'] === 'warning')
                                    <x-heroicon-o-exclamation-circle class="h-4 w-4 text-amber-500" />
                                @elseif ($kpi['status'] === 'info')
                                    <x-heroicon-o-information-circle class="h-4 w-4 text-indigo-500" />
                                @else
                                    <x-heroicon-o-check-circle class="h-4 w-4 text-emerald-500" />
                                @endif
                            </div>
                            <div class="mt-3 text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">
                                {{ $kpi['value'] }}
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-sm text-slate-400">{{ __('No KPIs available.') }}</div>
                    @endforelse
                </div>

                {{-- Validation strip --}}
                <div @class([
                    'mt-4 flex flex-wrap items-center gap-3 rounded-xl border p-4 text-sm shadow-sm dark:bg-slate-900',
                    'border-slate-200 bg-white dark:border-slate-800' => empty($validationIssues),
                    'border-rose-200 bg-rose-50/40 dark:border-rose-900/40 dark:bg-rose-950/10' => ! empty($validationIssues),
                ])>
                    @if (empty($validationIssues))
                        <x-heroicon-o-check-circle class="h-5 w-5 text-emerald-500" />
                        <span class="font-semibold text-slate-700 dark:text-slate-300">{{ __('All validation checks passed') }}</span>
                    @else
                        <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-rose-500" />
                        <span class="font-semibold text-rose-700 dark:text-rose-400">
                            {{ count($validationIssues) }} {{ __(count($validationIssues) === 1 ? 'issue' : 'issues') }} {{ __('requiring attention') }}
                        </span>
                        <span class="text-xs text-rose-600/70 dark:text-rose-400/70">
                            {{ __('Review the list on the right to unblock downstream steps.') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- ─── Main split: Timeline + Side panel ─── --}}
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">

            {{-- Left: Workflow Timeline (2/3) --}}
            <div class="lg:col-span-2 space-y-8">
                <livewire:academic.academic-workflow-timeline :school-id="auth()->user()?->school_id" />

                {{-- Pending tasks / suggestions --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Next Suggested Actions') }}</h3>
                        <span class="text-xs font-semibold text-slate-400">{{ count($suggestions) }} recommended</span>
                    </div>
                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($suggestions as $suggestion)
                            <div class="flex items-center justify-between gap-4 py-3">
                                <div class="flex items-start gap-3">
                                    <span @class([
                                        'mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full',
                                        'bg-rose-100 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400' => $suggestion['priority'] === 'critical',
                                        'bg-amber-100 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400' => $suggestion['priority'] === 'high',
                                        'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' => $suggestion['priority'] === 'medium',
                                    ])>
                                        @if ($suggestion['priority'] === 'critical')
                                            <x-heroicon-o-exclamation-triangle class="h-3.5 w-3.5" />
                                        @else
                                            <x-heroicon-o-light-bulb class="h-3.5 w-3.5" />
                                        @endif
                                    </span>
                                    <div>
                                        <div class="text-sm font-bold text-slate-900 dark:text-white">{{ __($suggestion['title']) }}</div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ __($suggestion['description']) }}</div>
                                    </div>
                                </div>
                                @if ($suggestion['action'] !== '#')
                                    <a href="{{ route($suggestion['action']) }}"
                                       class="inline-flex flex-shrink-0 items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                        {{ __('Open') }}
                                    </a>
                                @endif
                            </div>
                        @empty
                            <div class="py-8 text-center text-sm text-slate-400">
                                {{ __('No immediate actions required — everything is on track.') }}
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Upcoming deadlines --}}
                @if (! empty($deadlines))
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Upcoming Academic Tasks') }}</h3>
                            <span class="text-xs font-semibold text-slate-400">{{ __('Next 60 days') }}</span>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach ($deadlines as $deadline)
                                <div class="flex items-center justify-between gap-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                                            @if ($deadline['type'] === 'assessment')
                                                <x-heroicon-o-pencil-square class="h-4 w-4" />
                                            @else
                                                <x-heroicon-o-calendar-days class="h-4 w-4" />
                                            @endif
                                        </span>
                                        <div>
                                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ __($deadline['title']) }}</div>
                                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $deadline['detail'] }}</div>
                                        </div>
                                    </div>
                                    <span @class([
                                        'inline-flex flex-shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-bold',
                                        'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400' => $deadline['days_left'] <= 7,
                                        'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400' => $deadline['days_left'] > 7 && $deadline['days_left'] <= 30,
                                        'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' => $deadline['days_left'] > 30,
                                    ])>
                                        {{ $deadline['days_left'] > 0 ? $deadline['days_left'] . __('d') : __('Today') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Right: Side panel (1/3) --}}
            <div class="space-y-8">
                {{-- Validation issues --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 pb-3 dark:border-slate-800">
                        {{ __('Validation Issues') }}
                    </h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($validationIssues as $issue)
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-start gap-2">
                                    @if ($issue['type'] === 'error')
                                        <x-heroicon-o-x-circle class="h-4 w-4 flex-shrink-0 mt-0.5 text-rose-500" />
                                    @elseif ($issue['type'] === 'warning')
                                        <x-heroicon-o-exclamation-triangle class="h-4 w-4 flex-shrink-0 mt-0.5 text-amber-500" />
                                    @else
                                        <x-heroicon-o-information-circle class="h-4 w-4 flex-shrink-0 mt-0.5 text-indigo-500" />
                                    @endif
                                    <div>
                                        <div class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __($issue['message']) }}</div>
                                        @if (isset($issue['action']))
                                            <a href="{{ $issue['action'] }}"
                                               class="mt-1 inline-block text-xs font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                                {{ __('Resolve →') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-4 text-center text-xs text-slate-400">{{ __('No issues detected.') }}</div>
                        @endforelse
                    </div>
                </div>

                {{-- Quick actions --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 pb-3 dark:border-slate-800">
                        {{ __('Quick Actions') }}
                    </h3>
                    <div class="mt-4 grid grid-cols-1 gap-2">
                        @forelse ($quickActions as $action)
                            <a href="{{ $action['url'] }}"
                               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2 text-xs font-semibold text-slate-700 transition-colors hover:border-slate-300 hover:bg-white dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-200 dark:hover:bg-slate-800">
                                <x-heroicon-o-plus-circle class="h-4 w-4 text-emerald-500" />
                                {{ __($action['label']) }}
                            </a>
                        @empty
                            <div class="py-4 text-center text-xs text-slate-400">{{ __('Everything is configured.') }}</div>
                        @endforelse
                    </div>
                </div>

                {{-- Recent activity --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 pb-3 dark:border-slate-800">
                        {{ __('Recent Activity') }}
                    </h3>
                    <div class="mt-4 space-y-3">
                        @forelse ($recentActivity as $activity)
                            <div class="flex items-start gap-2.5">
                                <span @class([
                                    'mt-0.5 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full',
                                    'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400' => $activity['type'] === 'success',
                                    'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' => $activity['type'] !== 'success',
                                ])>
                                    @if ($activity['type'] === 'success')
                                        <x-heroicon-o-check class="h-3.5 w-3.5" />
                                    @else
                                        <x-heroicon-o-information-circle class="h-3.5 w-3.5" />
                                    @endif
                                </span>
                                <div>
                                    <div class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __($activity['description']) }}</div>
                                    <div class="text-[10px] text-slate-400">
                                        {{ $activity['user'] }} · {{ $activity['timestamp'] }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-4 text-center text-xs text-slate-400">{{ __('No recent activity.') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament::page>
