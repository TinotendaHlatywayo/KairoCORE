<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="flex items-center justify-between border-b border-slate-100 pb-4 dark:border-slate-800">
        <div>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Academic Workflow Timeline') }}</h3>
            <p class="mt-0.5 text-xs text-slate-400">{{ __('Guided setup — complete each step to unlock the next.') }}</p>
        </div>
        <button wire:click="loadSteps"
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            <x-heroicon-o-arrow-path class="h-3.5 w-3.5" />
            {{ __('Refresh') }}
        </button>
    </div>

    {{-- Progress summary --}}
    <div class="mt-4 rounded-xl border border-slate-100 bg-slate-50/60 p-4 dark:border-slate-800 dark:bg-slate-950/40">
        <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500 dark:text-slate-400">{{ $progress['completed'] }} {{ __('of') }} {{ $progress['total'] }} {{ __('steps completed') }}</span>
            <span class="font-extrabold text-slate-900 dark:text-white">{{ $progress['percent'] }}%</span>
        </div>
        <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
            <div class="h-2.5 rounded-full bg-gradient-to-r from-indigo-500 to-emerald-500 transition-all duration-500"
                 style="width: {{ $progress['percent'] }}%"></div>
        </div>
        <div class="mt-3 flex flex-wrap gap-4 text-xs text-slate-500 dark:text-slate-400">
            <span class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                {{ $progress['completed'] }} {{ __('Completed') }}
            </span>
            <span class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                {{ $progress['skipped'] ?? 0 }} {{ __('Skipped') }}
            </span>
            <span class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                {{ $progress['blocked'] }} {{ __('Blocked') }}
            </span>
            <span class="flex items-center gap-1.5">
                <span class="h-2 w-2 rounded-full bg-[color:var(--sc-primary-500)]"></span>
                {{ $progress['in_progress'] }} {{ __('Ready') }}
            </span>
        </div>
    </div>

    {{-- Steps --}}
    <div class="mt-5 space-y-3">
        @foreach ($steps as $step)
            @php
                $colorHex = match (true) {
                    $step['status'] === 'completed' => '#10b981',
                    $step['status'] === 'skipped' => '#f59e0b',
                    $step['is_blocked'] => '#ef4444',
                    $step['is_ready'] => 'var(--sc-primary-500)',
                    default => '#94a3b8',
                };
                $dotClass = match (true) {
                    $step['status'] === 'completed' => 'bg-emerald-500',
                    $step['status'] === 'skipped' => 'bg-amber-500',
                    $step['is_blocked'] => 'bg-rose-500',
                    $step['is_ready'] => 'bg-[color:var(--sc-primary-500)]',
                    default => 'bg-slate-400',
                };
                $stepIndex = $step['index'] ?? ($loop->index + 1);
            @endphp
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-shadow hover:shadow-md dark:border-slate-700/60 dark:bg-slate-900"
                 style="border-left: 4px solid {{ $colorHex }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-xs font-extrabold text-white {{ $dotClass }}">
                            @if ($step['status'] === 'completed')
                                <x-heroicon-o-check class="h-4 w-4" />
                            @else
                                {{ $stepIndex }}
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-bold text-slate-900 dark:text-white">{{ __($step['title']) }}</div>
                            <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                {{ Str::limit(__($step['description']), 100) }}
                            </div>
                            @if ($step['depends_on'])
                                <div class="mt-1 text-[11px] text-slate-400">
                                    {{ __('Requires:') }}
                                    {{ __(collect($steps)->firstWhere('key', $step['depends_on'])['title'] ?? $step['depends_on']) }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-shrink-0 items-center gap-2">
                        <span @class([
                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' => $step['status'] === 'completed',
                            'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400' => $step['status'] === 'skipped',
                            'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400' => $step['is_blocked'],
                            'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400' => $step['is_ready'],
                            'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' => true,
                        ])>
                            {{ __($step['status'] === 'completed' ? 'Completed' : ($step['status'] === 'skipped' ? 'Skipped' : ($step['is_blocked'] ? 'Blocked' : ($step['is_ready'] ? 'Ready' : 'Pending')))) }}
                        </span>

                        @if ($step['route'] && $step['status'] !== 'completed' && ! $step['is_blocked'] && $step['status'] !== 'skipped')
                            <a href="{{ route($step['route']) }}"
                               class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                {{ __('Configure') }}
                            </a>
                        @endif

                        @if ($canManageWorkflow && ($step['status'] === 'completed' || $step['status'] === 'skipped'))
                            <button wire:click="resetStatus('{{ $step['key'] }}')" title="{{ __('Clear manual override') }}"
                                    class="inline-flex items-center rounded-lg px-2 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-white">
                                <x-heroicon-o-arrow-path class="h-3.5 w-3.5" />
                            </button>
                        @elseif ($canManageWorkflow && $step['is_ready'] && ! $step['is_blocked'])
                            <button wire:click="setStatus('{{ $step['key'] }}', 'completed')" title="{{ __('Manually mark as complete') }}"
                                    class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-bold text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/30">
                                <x-heroicon-o-check-circle class="h-4 w-4" />
                                <span class="hidden sm:inline">{{ __('Mark done') }}</span>
                            </button>
                            <button wire:click="setStatus('{{ $step['key'] }}', 'skipped')" title="{{ __('Skip this step') }}"
                                    class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-bold text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-950/30">
                                <x-heroicon-o-clock class="h-4 w-4" />
                                <span class="hidden sm:inline">{{ __('Skip') }}</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
