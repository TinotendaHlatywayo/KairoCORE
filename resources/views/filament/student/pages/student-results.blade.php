<x-filament-panels::page>
    <div class="space-y-6">

        @if(! $student)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center dark:border-amber-800 dark:bg-amber-950/20">
                <x-heroicon-o-user-circle class="mx-auto h-10 w-10 text-amber-500"/>
                <h2 class="mt-3 text-sm font-bold text-amber-800 dark:text-amber-200">{{ __('No Student Record') }}</h2>
                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ __('Link your student profile to view your results.') }}</p>
            </div>
        @else

            <!-- Report Cards / Transcript -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">{{ __('Report Cards') }}</h3>
                <div class="space-y-4">
                    @forelse($reports as $report)
                        <div class="rounded-lg border border-slate-100 bg-slate-50/60 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                        {{ $report->section?->course?->name ?? __('Report') }}
                                        @if($report->term)
                                            · {{ __('Term') }} {{ $report->term->name }}
                                        @endif
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                                        {{ __('Overall Score') }}:
                                        <span class="font-bold">{{ $report->overall_score !== null ? $report->overall_score.'%' : '—' }}</span>
                                    </p>
                                </div>
                                @if($report->status === 'published')
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ __('PUBLISHED') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ __('PENDING') }}</span>
                                @endif
                            </div>
                            @if($report->strength || $report->needs_improvement)
                                <div class="mt-3 grid grid-cols-1 gap-3 text-xs sm:grid-cols-2">
                                    @if($report->strength)
                                        <p class="text-slate-600 dark:text-slate-300"><span class="font-bold text-emerald-600 dark:text-emerald-400">{{ __('Strengths') }}:</span> {{ $report->strength }}</p>
                                    @endif
                                    @if($report->needs_improvement)
                                        <p class="text-slate-600 dark:text-slate-300"><span class="font-bold text-amber-600 dark:text-amber-400">{{ __('Areas to improve') }}:</span> {{ $report->needs_improvement }}</p>
                                    @endif
                                </div>
                            @endif
                            @if($report->teacher_comment)
                                <p class="mt-3 rounded-lg bg-white p-3 text-xs italic text-slate-600 dark:bg-slate-900 dark:text-slate-300">{{ $report->teacher_comment }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="py-6 text-center text-xs text-slate-400">{{ __('No report cards published yet.') }}</p>
                    @endforelse
                </div>
            </div>

            <!-- Continuous Assessment Marks -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">{{ __('Continuous Assessment Marks') }}</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-left text-slate-500 dark:border-slate-800 dark:text-slate-400">
                                <th class="py-2.5 pr-3 font-semibold">{{ __('Subject') }}</th>
                                <th class="py-2.5 pr-3 font-semibold">{{ __('Assessment') }}</th>
                                <th class="py-2.5 pr-3 font-semibold text-right">{{ __('Marks') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assessmentMarks as $mark)
                                <tr class="border-b border-slate-50 dark:border-slate-800/60">
                                    <td class="py-3 pr-3 font-semibold text-slate-700 dark:text-slate-300">{{ $mark->subject?->name ?? '—' }}</td>
                                    <td class="py-3 pr-3 text-slate-500 dark:text-slate-400">{{ $mark->assessmentType?->name ?? '—' }}</td>
                                    <td class="py-3 text-right font-bold text-indigo-600 dark:text-indigo-400">{{ $mark->marks_obtained }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-xs text-slate-400">{{ __('No continuous assessment marks recorded yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        @endif
    </div>
</x-filament-panels::page>