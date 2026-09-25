<x-filament-panels::page>
    <style>
        .pa-kpi { @apply rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800; }
        .pa-card { @apply rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800; }
        .pa-kpi-label { @apply text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400; }
        .pa-kpi-value { @apply mt-1 text-2xl font-bold text-gray-900 dark:text-white; }
        .pa-kpi-sub { @apply mt-0.5 text-xs text-gray-500 dark:text-gray-400; }
        .pa-filter { @apply w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white; }
        .pa-table { @apply w-full text-left text-sm; }
        .pa-table thead th { @apply border-b border-gray-200 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400; }
        .pa-table tbody td { @apply border-b border-gray-100 px-3 py-2 text-gray-700 dark:border-gray-800 dark:text-gray-300; }
        .pa-row-hover tbody tr:hover { @apply bg-gray-50 dark:bg-gray-700/40; }
        .pa-pill { @apply inline-flex rounded-full px-2 py-0.5 text-xs font-semibold; }
        .pa-chart-tick { @apply text-[11px] text-gray-500 dark:text-gray-400; }
    </style>

    <div class="space-y-4">
        {{-- Header --}}
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Student Performance Analytics') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $selected['term'] }} · {{ $selected['year'] }} · <span class="font-semibold">{{ $selected['scope'] }}</span>
                </p>
            </div>
        </div>

        {{-- Filters --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('Term') }}</label>
                <select wire:model.live="termId" class="pa-filter">
                    @foreach ($terms as $termOption)
                        <option value="{{ $termOption->id }}">{{ $termOption->name }} · {{ $termOption->academicYear?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('Grade / Level') }}</label>
                <select wire:model.live="courseId" class="pa-filter">
                    <option value="">{{ __('All Levels') }}</option>
                    @foreach ($courses as $courseId => $courseName)
                        <option value="{{ $courseId }}">{{ $courseName }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('Class Stream') }}</label>
                <select wire:model.live="sectionId" class="pa-filter">
                    <option value="">{{ __('All Class Streams') }}</option>
                    @foreach ($sections as $sectionId => $sectionName)
                        <option value="{{ $sectionId }}">{{ $sectionName }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">{{ __('Subject') }}</label>
                <select wire:model.live="subjectId" class="pa-filter">
                    <option value="">{{ __('All Subjects') }}</option>
                    @foreach ($subjects as $subjectId => $subjectName)
                        <option value="{{ $subjectId }}">{{ $subjectName }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($kpis['students'] === 0)
            <div class="pa-card flex flex-col items-center gap-2 py-10 text-center">
                <x-heroicon-o-chart-bar class="h-10 w-10 text-gray-400" />
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ __('No assessment data found for this selection.') }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Enter marks via Grading & Marks Management → Marks Entry, or publish results to the student portal, to see analytics here.') }}
                </p>
            </div>
        @else
            {{-- KPI cards --}}
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-7">
                <div class="pa-kpi">
                    <p class="pa-kpi-label">{{ __('Students') }}</p>
                    <p class="pa-kpi-value">{{ number_format($kpis['students']) }}</p>
                    <p class="pa-kpi-sub">{{ __('with marks assessed') }}</p>
                </div>
                <div class="pa-kpi">
                    <p class="pa-kpi-label">{{ __('Average') }}</p>
                    <p class="pa-kpi-value">{{ $kpis['avg_overall'] ?? '—' }}<span class="text-sm font-semibold text-gray-400">%</span></p>
                    <p class="pa-kpi-sub">{{ __('overall performance') }}</p>
                </div>
                <div class="pa-kpi">
                    <p class="pa-kpi-label">{{ __('Pass Rate') }}</p>
                    <p class="pa-kpi-value">{{ $kpis['pass_rate'] }}<span class="text-sm font-semibold text-gray-400">%</span></p>
                    <p class="pa-kpi-sub">{{ __('scoring 50% or more') }}</p>
                </div>
                <div class="pa-kpi">
                    <p class="pa-kpi-label">{{ __('Distinction') }}</p>
                    <p class="pa-kpi-value">{{ $kpis['distinction_rate'] }}<span class="text-sm font-semibold text-gray-400">%</span></p>
                    <p class="pa-kpi-sub">{{ __('scoring 80% or more') }}</p>
                </div>
                <div class="pa-kpi">
                    <p class="pa-kpi-label">{{ __('Needs Support') }}</p>
                    <p class="pa-kpi-value text-red-600 dark:text-red-400">{{ $kpis['support_rate'] }}<span class="text-sm font-semibold text-gray-400">%</span></p>
                    <p class="pa-kpi-sub">{{ __('scoring under 40%') }}</p>
                </div>
                <div class="pa-kpi">
                    <p class="pa-kpi-label">{{ __('Assessments') }}</p>
                    <p class="pa-kpi-value">{{ number_format($kpis['assessments']) }}</p>
                    <p class="pa-kpi-sub">{{ __('with marks recorded') }}</p>
                </div>
                <div class="pa-kpi">
                    <p class="pa-kpi-label">{{ __('Subjects') }}</p>
                    <p class="pa-kpi-value">{{ number_format($kpis['subjects']) }}</p>
                    <p class="pa-kpi-sub">{{ __('covered by the scope') }}</p>
                </div>
            </div>

            {{-- Charts --}}
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @if (count($subjectStats))
                    <div class="pa-card">
                        <h2 class="mb-4 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('Average Performance by Subject') }}</h2>
                        <div class="space-y-2.5">
                            @php($subjectMax = max(array_column($subjectStats, 'avg')) ?: 1)
                            @foreach ($subjectStats as $stat)
                                <div class="grid grid-cols-[130px_1fr_60px] items-center gap-2">
                                    <p class="pa-chart-tick truncate font-medium">{{ $stat['name'] }}</p>
                                    <div class="h-3 rounded-full bg-gray-100 dark:bg-gray-700">
                                        <div class="h-3 rounded-full bg-gradient-to-r from-primary-400 to-primary-600"
                                             style="width: {{ round($stat['avg'] / $subjectMax * 100, 1) }}%;"></div>
                                    </div>
                                    <p class="pa-chart-tick text-right font-bold">{{ $stat['avg'] }}%</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (count($sectionStats))
                    <div class="pa-card">
                        <h2 class="mb-4 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('Average Performance by Class Stream') }}</h2>
                        <div class="space-y-2.5">
                            @php($sectionMax = max(array_column($sectionStats, 'avg')) ?: 1)
                            @foreach ($sectionStats as $stat)
                                <div class="grid grid-cols-[130px_1fr_60px] items-center gap-2">
                                    <p class="pa-chart-tick truncate font-medium">{{ $stat['name'] }}</p>
                                    <div class="h-3 rounded-full bg-gray-100 dark:bg-gray-700">
                                        <div class="h-3 rounded-full bg-gradient-to-r from-cyan-400 to-cyan-600"
                                             style="width: {{ round($stat['avg'] / $sectionMax * 100, 1) }}%;"></div>
                                    </div>
                                    <p class="pa-chart-tick text-right font-bold">{{ $stat['avg'] }}%</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="pa-card">
                    <h2 class="mb-4 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('Grade Distribution') }}</h2>
                    @php($gradeMax = max(array_column($gradeDistribution, 'count')) ?: 1)
                    <div class="space-y-2.5">
                        @foreach ($gradeDistribution as $grade)
                            <div class="grid grid-cols-[110px_1fr_60px] items-center gap-2">
                                <p class="pa-chart-tick font-medium">{{ $grade['label'] }}</p>
                                <div class="h-3 rounded-full bg-gray-100 dark:bg-gray-700">
                                    <div class="h-3 rounded-full" style="width: {{ round($grade['count'] / $gradeMax * 100, 1) }}%; background-color: {{ $grade['color'] }};"></div>
                                </div>
                                <p class="pa-chart-tick text-right font-bold">{{ $grade['count'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="pa-card">
                    <h2 class="mb-4 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('Score Band Distribution') }}</h2>
                    @php($bandMax = max(array_column($bandDistribution, 'count')) ?: 1)
                    <div class="space-y-2.5">
                        @foreach ($bandDistribution as $band)
                            <div class="grid grid-cols-[110px_1fr_60px] items-center gap-2">
                                <p class="pa-chart-tick font-medium">{{ $band['label'] }}</p>
                                <div class="h-3 rounded-full bg-gray-100 dark:bg-gray-700">
                                    <div class="h-3 rounded-full" style="width: {{ round($band['count'] / $bandMax * 100, 1) }}%; background-color: {{ $band['color'] }};"></div>
                                </div>
                                <p class="pa-chart-tick text-right font-bold">{{ $band['count'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if (count(array_filter(array_column($trend, 'avg'), fn ($avg) => $avg !== null)) >= 2)
                    <div class="pa-card lg:col-span-2">
                        <h2 class="mb-4 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('Term-over-Term Trend (this school year)') }}</h2>
                        <div class="grid grid-cols-2 items-end gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach ($trend as $point)
                                <div class="flex flex-col items-center gap-1">
                                    <p class="pa-chart-tick font-bold">{{ $point['avg'] === null ? '—' : $point['avg'].'%' }}</p>
                                    <div class="w-full flex justify-center">
                                        @if ($point['avg'] !== null)
                                            <div class="w-10 rounded-t-md bg-gradient-to-t from-primary-500 to-primary-300"
                                                 style="height: {{ max(8, min(140, $point['avg'] * 1.4)) }}px;"></div>
                                        @else
                                            <div class="h-8 w-10 rounded-t-md bg-gray-200 dark:bg-gray-700"></div>
                                        @endif
                                    </div>
                                    <p class="pa-chart-tick uppercase">{{ $point['term'] }}</p>
                                    <p class="pa-chart-tick text-gray-400">{{ $point['students'] }} {{ __('students') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Tables --}}
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="pa-card overflow-hidden">
                    <h2 class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('Top Learners') }}</h2>
                    @if (count($topLearners))
                        <div class="overflow-x-auto">
                            <table class="pa-table pa-row-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('Student') }}</th>
                                        <th>{{ __('Class') }}</th>
                                        <th class="text-right">{{ __('Average') }}</th>
                                        <th class="text-right">{{ __('Grade') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($topLearners as $learner)
                                        <tr>
                                            <td class="text-gray-400">{{ $learner['rank'] }}</td>
                                            <td class="font-medium">{{ $learner['student_name'] }}</td>
                                            <td class="text-gray-500 dark:text-gray-400">{{ $learner['section'] }}</td>
                                            <td class="text-right font-bold text-emerald-600 dark:text-emerald-400">{{ $learner['overall'] }}%</td>
                                            <td class="text-right">
                                                <span class="pa-pill text-emerald-700 dark:text-emerald-300">{{ $learner['grade'] }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No learners with recorded marks.') }}</p>
                    @endif
                </div>

                <div class="pa-card overflow-hidden">
                    <h2 class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('Students Needing Support') }}</h2>
                    @if (count($supportLearners))
                        <div class="overflow-x-auto">
                            <table class="pa-table pa-row-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>{{ __('Student') }}</th>
                                        <th>{{ __('Class') }}</th>
                                        <th class="text-right">{{ __('Average') }}</th>
                                        <th class="text-right">{{ __('Grade') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($supportLearners as $learner)
                                        <tr>
                                            <td class="text-gray-400">{{ $learner['rank'] }}</td>
                                            <td class="font-medium">{{ $learner['student_name'] }}</td>
                                            <td class="text-gray-500 dark:text-gray-400">{{ $learner['section'] }}</td>
                                            <td class="text-right font-bold text-red-600 dark:text-red-400">{{ $learner['overall'] }}%</td>
                                            <td class="text-right">
                                                <span class="pa-pill text-red-700 dark:text-red-300">{{ $learner['grade'] }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-emerald-600 dark:text-emerald-400">{{ __('No learners below the support threshold.') }}</p>
                    @endif
                </div>

                @if (count($subjectStats))
                    <div class="pa-card overflow-hidden lg:col-span-2">
                        <h2 class="mb-3 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('Subject Performance Summary') }}</h2>
                        <div class="overflow-x-auto">
                            <table class="pa-table pa-row-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('Subject') }}</th>
                                        <th class="text-right">{{ __('Students') }}</th>
                                        <th class="text-right">{{ __('Average') }}</th>
                                        <th class="text-right">{{ __('Pass Rate') }}</th>
                                        <th class="text-right">{{ __('Highest') }}</th>
                                        <th class="text-right">{{ __('Lowest') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subjectStats as $stat)
                                        <tr>
                                            <td class="font-medium">{{ $stat['name'] }}</td>
                                            <td class="text-right">{{ $stat['students'] }}</td>
                                            <td class="text-right font-bold">{{ $stat['avg'] }}%</td>
                                            <td class="text-right">
                                                <span class="pa-pill {{ $stat['pass_rate'] >= 50 ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                                                    {{ $stat['pass_rate'] }}%
                                                </span>
                                            </td>
                                            <td class="text-right text-emerald-600 dark:text-emerald-400">{{ $stat['highest'] }}%</td>
                                            <td class="text-right text-red-600 dark:text-red-400">{{ $stat['lowest'] }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>