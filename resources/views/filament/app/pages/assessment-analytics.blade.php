<x-filament-panels::page>
    @if(!empty($assessmentData))
    {{-- Assessment Overview Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="fi-card fi-card-content p-4 rounded-xl bg-white dark:bg-gray-900 shadow-sm">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Attempts</div>
            <div class="text-2xl font-bold">{{ $assessmentData['total_attempts'] }}</div>
        </div>
        <div class="fi-card fi-card-content p-4 rounded-xl bg-white dark:bg-gray-900 shadow-sm">
            <div class="text-sm text-gray-500 dark:text-gray-400">Average Score</div>
            <div class="text-2xl font-bold">{{ $assessmentData['avg_score'] }}%</div>
        </div>
        <div class="fi-card fi-card-content p-4 rounded-xl bg-white dark:bg-gray-900 shadow-sm">
            <div class="text-sm text-gray-500 dark:text-gray-400">Pass Rate</div>
            <div class="text-2xl font-bold text-success-600">{{ $assessmentData['pass_rate'] }}%</div>
        </div>
        <div class="fi-card fi-card-content p-4 rounded-xl bg-white dark:bg-gray-900 shadow-sm">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Questions</div>
            <div class="text-2xl font-bold">{{ $assessmentData['total_questions'] }}</div>
        </div>
    </div>

    {{-- Score Range --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="fi-card p-4 rounded-xl bg-white dark:bg-gray-900 shadow-sm">
            <div class="text-sm text-gray-500 dark:text-gray-400">Highest Score</div>
            <div class="text-xl font-semibold text-success-600">{{ $assessmentData['highest_score'] }}%</div>
        </div>
        <div class="fi-card p-4 rounded-xl bg-white dark:bg-gray-900 shadow-sm">
            <div class="text-sm text-gray-500 dark:text-gray-400">Lowest Score</div>
            <div class="text-xl font-semibold text-danger-600">{{ $assessmentData['lowest_score'] }}%</div>
        </div>
        <div class="fi-card p-4 rounded-xl bg-white dark:bg-gray-900 shadow-sm">
            <div class="text-sm text-gray-500 dark:text-gray-400">Pass Mark</div>
            <div class="text-xl font-semibold">{{ $assessmentData['pass_mark'] }}%</div>
        </div>
    </div>

    {{-- Score Distribution --}}
    <div class="fi-card p-6 rounded-xl bg-white dark:bg-gray-900 shadow-sm mb-6">
        <h3 class="text-lg font-semibold mb-4">Score Distribution</h3>
        <div class="space-y-3">
            @foreach($assessmentData['score_distribution'] ?? [] as $range => $count)
                @php
                    $maxCount = max(array_values($assessmentData['score_distribution'] ?? [1]));
                    $percentage = $maxCount > 0 ? ($count / $maxCount) * 100 : 0;
                @endphp
                <div class="flex items-center gap-3">
                    <span class="w-20 text-sm text-gray-600 dark:text-gray-400 font-mono">{{ $range }}</span>
                    <div class="flex-1 bg-gray-100 dark:bg-gray-800 rounded-full h-6 overflow-hidden">
                        <div
                            class="h-full rounded-full
                                {{ str_starts_with($range, '8') ? 'bg-success-500' :
                                   (str_starts_with($range, '6') ? 'bg-info-500' :
                                   (str_starts_with($range, '4') ? 'bg-warning-500' :
                                   (str_starts_with($range, '2') ? 'bg-orange-500' : 'bg-danger-500'))) }}"
                            style="width: {{ $percentage }}%"
                        ></div>
                    </div>
                    <span class="w-10 text-right text-sm font-semibold">{{ $count }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Marking Status --}}
    @if(($markingStats['needs_marking'] ?? 0) > 0)
    <div class="fi-card p-6 rounded-xl bg-warning-50 dark:bg-warning-900/20 border border-warning-200 dark:border-warning-700 mb-6">
        <div class="flex items-center gap-3">
            <div class="fi-wp-icon heroicon-o-pencil-square h-8 w-8 text-warning-600"></div>
            <div>
                <div class="font-semibold text-warning-800 dark:text-warning-200">
                    {{ $markingStats['needs_marking'] }} responses need marking
                </div>
                <div class="text-sm text-warning-600 dark:text-warning-400">
                    {{ $markingStats['marked'] ?? 0 }} already marked out of {{ $markingStats['total_subjective'] ?? 0 }} total subjective responses.
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Question Performance Table --}}
    <div class="fi-card p-6 rounded-xl bg-white dark:bg-gray-900 shadow-sm mb-6">
        <h3 class="text-lg font-semibold mb-4">Question Performance</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b dark:border-gray-700">
                        <th class="text-left py-3 px-2 font-medium text-gray-500">Question</th>
                        <th class="text-left py-3 px-2 font-medium text-gray-500">Type</th>
                        <th class="text-center py-3 px-2 font-medium text-gray-500">Difficulty</th>
                        <th class="text-center py-3 px-2 font-medium text-gray-500">Marks</th>
                        <th class="text-center py-3 px-2 font-medium text-gray-500">Attempts</th>
                        <th class="text-center py-3 px-2 font-medium text-gray-500">Correct</th>
                        <th class="text-center py-3 px-2 font-medium text-gray-500">Success Rate</th>
                        <th class="text-center py-3 px-2 font-medium text-gray-500">Avg Time (s)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($questionStats as $qs)
                    <tr class="border-b dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="py-3 px-2 max-w-[200px] truncate" title="{{ $qs['title'] }}">{{ $qs['title'] }}</td>
                        <td class="py-3 px-2">
                            <span class="fi-badge fi-badge-size-sm bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded text-xs">
                                {{ $qs['type'] }}
                            </span>
                        </td>
                        <td class="py-3 px-2 text-center">
                            <span class="fi-badge fi-badge-size-sm bg-{{ $qs['difficulty_color'] }}-100 dark:bg-{{ $qs['difficulty_color'] }}-900/30 text-{{ $qs['difficulty_color'] }}-700 dark:text-{{ $qs['difficulty_color'] }}-300 px-2 py-0.5 rounded text-xs">
                                {{ $qs['difficulty'] }}
                            </span>
                        </td>
                        <td class="py-3 px-2 text-center">{{ $qs['marks'] }}</td>
                        <td class="py-3 px-2 text-center">{{ $qs['total_attempts'] }}</td>
                        <td class="py-3 px-2 text-center">{{ $qs['correct_count'] }}</td>
                        <td class="py-3 px-2 text-center">
                            @php $sr = $qs['success_rate']; @endphp
                            <span class="font-semibold {{ $sr >= 70 ? 'text-success-600' : ($sr >= 40 ? 'text-warning-600' : 'text-danger-600') }}">
                                {{ number_format($sr, 1) }}%
                            </span>
                        </td>
                        <td class="py-3 px-2 text-center">{{ $qs['avg_response_time'] }}s</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-gray-500">No question data yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Mastery Summary (if data exists) --}}
    @if(!empty($classMastery['by_topic']) && count($classMastery['by_topic']) > 0)
    <div class="fi-card p-6 rounded-xl bg-white dark:bg-gray-900 shadow-sm mb-6">
        <h3 class="text-lg font-semibold mb-4">Class Mastery by Topic</h3>
        <div class="space-y-4">
            @foreach($classMastery['by_topic'] as $topicData)
            <div>
                <div class="flex justify-between items-center mb-1">
                    <span class="font-medium">{{ $topicData['topic'] }}</span>
                    <span class="text-sm text-gray-500">{{ $topicData['avg_score'] }}% avg · {{ $topicData['student_count'] }} students</span>
                </div>
                <div class="flex gap-1 h-4 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-800">
                    @if($topicData['student_count'] > 0)
                        @php
                            $total = $topicData['student_count'];
                            $mPct = ($topicData['mastery_breakdown']['mastered'] ?? 0) / $total * 100;
                            $pPct = ($topicData['mastery_breakdown']['proficient'] ?? 0) / $total * 100;
                            $dPct = ($topicData['mastery_breakdown']['developing'] ?? 0) / $total * 100;
                            $bPct = ($topicData['mastery_breakdown']['beginning'] ?? 0) / $total * 100;
                        @endphp
                        @if($mPct > 0)<div class="bg-success-500" style="width:{{ $mPct }}%" title="Mastered: {{ $topicData['mastery_breakdown']['mastered'] }}"></div>@endif
                        @if($pPct > 0)<div class="bg-info-500" style="width:{{ $pPct }}%" title="Proficient: {{ $topicData['mastery_breakdown']['proficient'] }}"></div>@endif
                        @if($dPct > 0)<div class="bg-warning-500" style="width:{{ $dPct }}%" title="Developing: {{ $topicData['mastery_breakdown']['developing'] }}"></div>@endif
                        @if($bPct > 0)<div class="bg-danger-500" style="width:{{ $bPct }}%" title="Beginning: {{ $topicData['mastery_breakdown']['beginning'] }}"></div>@endif
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        <div class="flex gap-4 mt-4 text-xs text-gray-500">
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-success-500"></span> Mastered</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-info-500"></span> Proficient</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-warning-500"></span> Developing</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded bg-danger-500"></span> Beginning</span>
        </div>
    </div>
    @endif

    @else
    <div class="fi-card p-8 rounded-xl bg-white dark:bg-gray-900 shadow-sm text-center">
        <div class="text-gray-500 dark:text-gray-400">No analytics data available for this assessment yet.</div>
    </div>
    @endif
</x-filament-panels::page>
