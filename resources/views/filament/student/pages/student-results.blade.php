<x-filament-panels::page>
    <div class="space-y-6">

        @if(! $student)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center dark:border-amber-800 dark:bg-amber-950/20">
                <x-heroicon-o-user-circle class="mx-auto h-10 w-10 text-amber-500"/>
                <h2 class="mt-3 text-sm font-bold text-amber-800 dark:text-amber-200">{{ __('Profile Not Linked') }}</h2>
                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ __('Link your student profile to view your results.') }}</p>
            </div>
        @else

            <!-- Filters Bar (Term & Subject) -->
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ __('Filter History & Results') }}</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Select academic term and subject to filter report cards and assessment marks.') }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-400 uppercase tracking-wide mb-1">{{ __('Term') }}</label>
                            <select wire:model.change="selectedTermId" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                <option value="">{{ __('All Terms & History') }}</option>
                                @foreach($terms as $term)
                                    <option value="{{ $term->id }}">
                                        {{ $term->academicYear?->name ?? 'Year' }} — {{ $term->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-400 uppercase tracking-wide mb-1">{{ __('Subject') }}</label>
                            <select wire:model.change="selectedSubjectId" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                <option value="">{{ __('All Subjects') }}</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Cards / Transcript History -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">{{ __('Report Cards') }}</h3>
                <div class="space-y-4">
                    @forelse($reports as $report)
                        @php($termLabel = ($report->term?->academicYear?->name ?? '2026').' — '.($report->term?->name ?? 'Term 1'))
                        <div class="rounded-lg border border-slate-100 bg-slate-50/60 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="text-sm font-bold text-slate-800 dark:text-slate-200">
                                        {{ $report->section?->course?->name ?? __('Report') }}
                                        · <span class="text-indigo-600 dark:text-indigo-400">{{ $termLabel }}</span>
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                                        {{ __('Overall Score') }}:
                                        <span class="font-bold">{{ $report->overall_score !== null ? $report->overall_score.'%' : '—' }}</span>
                                    </p>
                                </div>
                                <span class="inline-flex items-center gap-2">
                                    <a href="{{ route('student.report.pdf', $report->id) }}" target="_blank"
                                       class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-[11px] font-bold text-white transition hover:bg-indigo-500 shadow-sm">
                                        <x-heroicon-o-arrow-down-tray class="mr-1.5 h-3.5 w-3.5"/>
                                        {{ __('View Report Card PDF') }}
                                    </a>
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ __('PUBLISHED') }}</span>
                                </span>
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
                        <p class="py-6 text-center text-xs text-slate-400">{{ __('No report cards published for the selected term/year.') }}</p>
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
                                    <td class="py-3 text-right font-bold text-indigo-600 dark:text-indigo-400">{{ isset($mark->marks_obtained) ? round($mark->marks_obtained) : '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-8 text-center text-xs text-slate-400">{{ __('No continuous assessment marks recorded for this term/subject.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        @endif
    </div>
</x-filament-panels::page>
