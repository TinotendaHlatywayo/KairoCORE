<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Filters --}}
        <x-filament::section
            icon="heroicon-o-funnel"
            iconColor="primary"
            :heading="__('Filter Analytics')"
            :description="__('Pick a term, grade/level, class stream or subject to refine the insights below.')"
        >
            {{ $this->form }}
        </x-filament::section>

        @if ($kpis['students'] === 0)
            <x-filament::section>
                <div class="flex flex-col items-center gap-2 py-10 text-center">
                    <x-heroicon-o-chart-bar class="h-10 w-10 text-gray-400 dark:text-gray-500" />
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('No assessment data found for this selection.') }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Enter marks via Grading & Marks Management → Marks Entry, or publish results to the student portal, to see analytics here.') }}
                    </p>
                </div>
            </x-filament::section>
        @else
            {{-- KPI cards --}}
            <x-filament::grid default="2" md="3" xl="7" class="gap-3">
                <x-filament::section compact>
                    <p class="flex items-center gap-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-users class="h-4 w-4" /> {{ __('Students') }}
                    </p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ number_format($kpis['students']) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('with marks assessed') }}</p>
                </x-filament::section>

                <x-filament::section compact>
                    <p class="flex items-center gap-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-chart-bar class="h-4 w-4" /> {{ __('Average') }}
                    </p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $kpis['avg_overall'] ?? '—' }}<span class="text-sm font-semibold text-gray-400">%</span></p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('overall performance') }}</p>
                </x-filament::section>

                <x-filament::section compact>
                    <p class="flex items-center gap-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-check-badge class="h-4 w-4" /> {{ __('Pass Rate') }}
                    </p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">{{ $kpis['pass_rate'] }}<span class="text-sm font-semibold text-gray-400">%</span></p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('scoring 50% or more') }}</p>
                </x-filament::section>

                <x-filament::section compact>
                    <p class="flex items-center gap-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-sparkles class="h-4 w-4" /> {{ __('Distinction') }}
                    </p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-primary-600 dark:text-primary-400">{{ $kpis['distinction_rate'] }}<span class="text-sm font-semibold text-gray-400">%</span></p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('scoring 80% or more') }}</p>
                </x-filament::section>

                <x-filament::section compact>
                    <p class="flex items-center gap-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-exclamation-triangle class="h-4 w-4" /> {{ __('Needs Support') }}
                    </p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-red-600 dark:text-red-400">{{ $kpis['support_rate'] }}<span class="text-sm font-semibold text-gray-400">%</span></p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('scoring under 40%') }}</p>
                </x-filament::section>

                <x-filament::section compact>
                    <p class="flex items-center gap-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-clipboard-document-list class="h-4 w-4" /> {{ __('Assessments') }}
                    </p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ number_format($kpis['assessments']) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('with marks recorded') }}</p>
                </x-filament::section>

                <x-filament::section compact>
                    <p class="flex items-center gap-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-book-open class="h-4 w-4" /> {{ __('Subjects') }}
                    </p>
                    <p class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ number_format($kpis['subjects']) }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __('covered by the scope') }}</p>
                </x-filament::section>
            </x-filament::grid>

            {{-- Charts --}}
            <x-filament::grid lg="2" class="gap-4">
                @if (count($subjectStats))
                    <x-filament::section
                        icon="heroicon-o-book-open"
                        iconColor="primary"
                        :heading="__('Average Performance by Subject')"
                    >
                        <div class="space-y-2.5">
                            @php($subjectMax = max(array_column($subjectStats, 'avg')) ?: 1)
                            @foreach ($subjectStats as $stat)
                                <div class="grid grid-cols-[130px_1fr_60px] items-center gap-2">
                                    <p class="truncate text-xs font-medium text-gray-500 dark:text-gray-400">{{ $stat['name'] }}</p>
                                    <div class="h-3 rounded-full bg-gray-100 dark:bg-gray-700">
                                        <div class="h-3 rounded-full bg-gradient-to-r from-primary-400 to-primary-600"
                                             style="width: {{ round($stat['avg'] / $subjectMax * 100, 1) }}%;"></div>
                                    </div>
                                    <p class="text-right text-xs font-bold text-gray-700 dark:text-gray-200">{{ $stat['avg'] }}%</p>
                                </div>
                            @endforeach
                        </div>
                    </x-filament::section>
                @endif

                @if (count($sectionStats))
                    <x-filament::section
                        icon="heroicon-o-table-cells"
                        iconColor="info"
                        :heading="__('Average Performance by Class Stream')"
                    >
                        <div class="space-y-2.5">
                            @php($sectionMax = max(array_column($sectionStats, 'avg')) ?: 1)
                            @foreach ($sectionStats as $stat)
                                <div class="grid grid-cols-[130px_1fr_60px] items-center gap-2">
                                    <p class="truncate text-xs font-medium text-gray-500 dark:text-gray-400">{{ $stat['name'] }}</p>
                                    <div class="h-3 rounded-full bg-gray-100 dark:bg-gray-700">
                                        <div class="h-3 rounded-full bg-gradient-to-r from-cyan-400 to-cyan-600"
                                             style="width: {{ round($stat['avg'] / $sectionMax * 100, 1) }}%;"></div>
                                    </div>
                                    <p class="text-right text-xs font-bold text-gray-700 dark:text-gray-200">{{ $stat['avg'] }}%</p>
                                </div>
                            @endforeach
                        </div>
                    </x-filament::section>
                @endif

                <x-filament::section
                    icon="heroicon-o-chart-pie"
                    iconColor="warning"
                    :heading="__('Grade Distribution')"
                >
                    @php($gradeMax = max(array_column($gradeDistribution, 'count')) ?: 1)
                    <div class="space-y-2.5">
                        @foreach ($gradeDistribution as $grade)
                            <div class="grid grid-cols-[110px_1fr_60px] items-center gap-2">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $grade['label'] }}</p>
                                <div class="h-3 rounded-full bg-gray-100 dark:bg-gray-700">
                                    <div class="h-3 rounded-full" style="width: {{ round($grade['count'] / $gradeMax * 100, 1) }}%; background-color: {{ $grade['color'] }};"></div>
                                </div>
                                <p class="text-right text-xs font-bold text-gray-700 dark:text-gray-200">{{ $grade['count'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>

                <x-filament::section
                    icon="heroicon-o-squares-2x2"
                    iconColor="info"
                    :heading="__('Score Band Distribution')"
                >
                    @php($bandMax = max(array_column($bandDistribution, 'count')) ?: 1)
                    <div class="space-y-2.5">
                        @foreach ($bandDistribution as $band)
                            <div class="grid grid-cols-[110px_1fr_60px] items-center gap-2">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $band['label'] }}</p>
                                <div class="h-3 rounded-full bg-gray-100 dark:bg-gray-700">
                                    <div class="h-3 rounded-full" style="width: {{ round($band['count'] / $bandMax * 100, 1) }}%; background-color: {{ $band['color'] }};"></div>
                                </div>
                                <p class="text-right text-xs font-bold text-gray-700 dark:text-gray-200">{{ $band['count'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>

                @if (count(array_filter(array_column($trend, 'avg'), fn ($avg) => $avg !== null)) >= 2)
                    <x-filament::section
                        class="lg:col-span-2"
                        icon="heroicon-o-arrow-trending-up"
                        iconColor="success"
                        :heading="__('Term-over-Term Trend (this school year)')"
                    >
                        <div class="grid grid-cols-2 items-end gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach ($trend as $point)
                                <div class="flex flex-col items-center gap-1">
                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-200">{{ $point['avg'] === null ? '—' : $point['avg'].'%' }}</p>
                                    <div class="flex w-full justify-center">
                                        @if ($point['avg'] !== null)
                                            <div class="w-10 rounded-t-md bg-gradient-to-t from-primary-500 to-primary-300"
                                                 style="height: {{ max(8, min(140, $point['avg'] * 1.4)) }}px;"></div>
                                        @else
                                            <div class="h-8 w-10 rounded-t-md bg-gray-200 dark:bg-gray-700"></div>
                                        @endif
                                    </div>
                                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $point['term'] }}</p>
                                    <p class="text-[11px] text-gray-400 dark:text-gray-500">{{ $point['students'] }} {{ __('students') }}</p>
                                </div>
                            @endforeach
                        </div>
                    </x-filament::section>
                @endif
            </x-filament::grid>

            {{-- Tables --}}
            <x-filament::grid lg="2" class="gap-4">

                <x-filament::section
                    icon="heroicon-o-trophy"
                    iconColor="success"
                    :heading="__('Top Learners')"
                >
                    @if (count($topLearners))
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr>
                                        <th class="border-b border-gray-200 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">#</th>
                                        <th class="border-b border-gray-200 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Student') }}</th>
                                        <th class="border-b border-gray-200 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Class') }}</th>
                                        <th class="border-b border-gray-200 px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Average') }}</th>
                                        <th class="border-b border-gray-200 px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Grade') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($topLearners as $learner)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                            <td class="border-b border-gray-100 px-3 py-2 text-gray-400 dark:border-gray-800">{{ $learner['rank'] }}</td>
                                            <td class="border-b border-gray-100 px-3 py-2 font-medium text-gray-700 dark:border-gray-800 dark:text-gray-200">{{ $learner['student_name'] }}</td>
                                            <td class="border-b border-gray-100 px-3 py-2 text-gray-500 dark:border-gray-800 dark:text-gray-400">{{ $learner['section'] }}</td>
                                            <td class="border-b border-gray-100 px-3 py-2 text-right font-bold text-emerald-600 dark:border-gray-800 dark:text-emerald-400">{{ $learner['overall'] }}%</td>
                                            <td class="border-b border-gray-100 px-3 py-2 text-right dark:border-gray-800">
                                                <x-filament::badge :color="$learner['grade_tone'] ?? 'gray'">{{ $learner['grade'] }}</x-filament::badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('No learners with recorded marks.') }}</p>
                    @endif
                </x-filament::section>

                <x-filament::section
                    icon="heroicon-o-lifebuoy"
                    iconColor="danger"
                    :heading="__('Students Needing Support')"
                >
                    @if (count($supportLearners))
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr>
                                        <th class="border-b border-gray-200 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">#</th>
                                        <th class="border-b border-gray-200 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Student') }}</th>
                                        <th class="border-b border-gray-200 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Class') }}</th>
                                        <th class="border-b border-gray-200 px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Average') }}</th>
                                        <th class="border-b border-gray-200 px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Grade') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($supportLearners as $learner)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                            <td class="border-b border-gray-100 px-3 py-2 text-gray-400 dark:border-gray-800">{{ $learner['rank'] }}</td>
                                            <td class="border-b border-gray-100 px-3 py-2 font-medium text-gray-700 dark:border-gray-800 dark:text-gray-200">{{ $learner['student_name'] }}</td>
                                            <td class="border-b border-gray-100 px-3 py-2 text-gray-500 dark:border-gray-800 dark:text-gray-400">{{ $learner['section'] }}</td>
                                            <td class="border-b border-gray-100 px-3 py-2 text-right font-bold text-red-600 dark:border-gray-800 dark:text-red-400">{{ $learner['overall'] }}%</td>
                                            <td class="border-b border-gray-100 px-3 py-2 text-right dark:border-gray-800">
                                                <x-filament::badge :color="$learner['grade_tone'] ?? 'gray'">{{ $learner['grade'] }}</x-filament::badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-emerald-600 dark:text-emerald-400">{{ __('No learners below the support threshold.') }}</p>
                    @endif
                </x-filament::section>

                @if (count($subjectStats))
                    <x-filament::section
                        class="lg:col-span-2"
                        icon="heroicon-o-clipboard-document-check"
                        iconColor="primary"
                        :heading="__('Subject Performance Summary')"
                    >
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr>
                                        <th class="border-b border-gray-200 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Subject') }}</th>
                                        <th class="border-b border-gray-200 px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Students') }}</th>
                                        <th class="border-b border-gray-200 px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Average') }}</th>
                                        <th class="border-b border-gray-200 px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Pass Rate') }}</th>
                                        <th class="border-b border-gray-200 px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Highest') }}</th>
                                        <th class="border-b border-gray-200 px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Lowest') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subjectStats as $stat)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                            <td class="border-b border-gray-100 px-3 py-2 font-medium text-gray-700 dark:border-gray-800 dark:text-gray-200">{{ $stat['name'] }}</td>
                                            <td class="border-b border-gray-100 px-3 py-2 text-right text-gray-600 dark:border-gray-800 dark:text-gray-300">{{ $stat['students'] }}</td>
                                            <td class="border-b border-gray-100 px-3 py-2 text-right font-bold text-gray-700 dark:border-gray-800 dark:text-gray-200">{{ $stat['avg'] }}%</td>
                                            <td class="border-b border-gray-100 px-3 py-2 text-right dark:border-gray-800">
                                                <x-filament::badge :color="$stat['pass_rate'] >= 50 ? 'success' : 'danger'">{{ $stat['pass_rate'] }}%</x-filament::badge>
                                            </td>
                                            <td class="border-b border-gray-100 px-3 py-2 text-right text-emerald-600 dark:border-gray-800 dark:text-emerald-400">{{ $stat['highest'] }}%</td>
                                            <td class="border-b border-gray-100 px-3 py-2 text-right text-red-600 dark:border-gray-800 dark:text-red-400">{{ $stat['lowest'] }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-filament::section>
                @endif
            </x-filament::grid>
        @endif
    </div>
</x-filament-panels::page>