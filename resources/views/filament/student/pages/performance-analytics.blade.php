<x-filament-panels::page>
    <div class="space-y-6">

        @if(! $student)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center dark:border-amber-800 dark:bg-amber-950/20">
                <x-heroicon-o-user-circle class="mx-auto h-10 w-10 text-amber-500"/>
                <h2 class="mt-3 text-sm font-bold text-amber-800 dark:text-amber-200">{{ __('Profile Not Linked') }}</h2>
                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ __('Link your student profile to view analytics.') }}</p>
            </div>
        @else

            <!-- Overview Stat Cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Overall Average Performance') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ $overallAvg }}%</p>
                    <p class="mt-1 text-[11px] text-slate-400">{{ __('Across all continuous assessments and exams') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Top Performing Subject') }}</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400 truncate">{{ $bestSubject }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">{{ __('Highest average score') }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Total Tracked Assessments') }}</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-800 dark:text-white">{{ $marks->count() }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">{{ __('Test 1, Test 2, Exams recorded') }}</p>
                </div>
            </div>

            <!-- Subject Performance Breakdown & Intelligence Over Time -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-2">{{ __('Subject Performance & Assessment Trends') }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mb-6">{{ __('Detailed breakdown of marks obtained across Test 1, Test 2, and End of Term Exam for each subject.') }}</p>

                <div class="space-y-6">
                    @forelse($bySubject as $subjectName => $data)
                        <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-4 dark:border-slate-800 dark:bg-slate-950/40">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ $subjectName }}</h4>
                                    <p class="text-[11px] text-slate-500">{{ __('Average') }}: <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $data['avg'] }}%</span> · {{ __('Max') }}: {{ $data['max'] }} · {{ __('Min') }}: {{ $data['min'] }}</p>
                                </div>
                                <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-[10px] font-bold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                    {{ $data['count'] }} {{ __('Assessments') }}
                                </span>
                            </div>

                            <!-- Visual Progress Bar / Trend -->
                            <div class="mb-4 h-2.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                                <div class="h-full rounded-full bg-indigo-600 dark:bg-indigo-500 transition-all duration-500" style="width: {{ min(100, max(5, $data['avg'])) }}%"></div>
                            </div>

                            <!-- Assessment Types breakdown -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                @foreach($data['items'] as $typeName => $typeMarks)
                                    <div class="rounded-lg bg-white p-2.5 text-center shadow-xs dark:bg-slate-900">
                                        <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide">{{ $typeName }}</p>
                                        <p class="mt-1 text-sm font-bold text-slate-800 dark:text-white">
                                            {{ round($typeMarks->avg('marks_obtained')) }}%
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-xs text-slate-400">{{ __('No performance analytics data available yet.') }}</p>
                    @endforelse
                </div>
            </div>

        @endif
    </div>
</x-filament-panels::page>
