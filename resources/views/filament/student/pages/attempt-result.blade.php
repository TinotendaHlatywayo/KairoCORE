@php
    $attempt = $this->attemptModel;
    $summary = $this->summary;
    $assessment = $attempt->assessment;
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ $assessment?->title }}</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $assessment?->subject?->name }}</p>
                </div>
                <a href="{{ route('filament.student.resources.digital-assessments.index') }}"
                   class="text-sm text-primary-600 hover:underline">
                    ← Back to Assessments
                </a>
            </div>
        </div>

        {{-- Score Card --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border p-6">
            <div class="text-center space-y-2">
                @if($summary['passed'])
                    <x-heroicon-o-check-circle class="w-12 h-12 mx-auto text-success-500" />
                    <h2 class="text-lg font-bold text-success-600">You Passed!</h2>
                @else
                    <x-heroicon-o-x-circle class="w-12 h-12 mx-auto text-danger-500" />
                    <h2 class="text-lg font-bold text-danger-600">Not Passed</h2>
                @endif

                <div class="text-5xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($summary['percentage'] ?? 0, 1) }}%
                </div>
                <p class="text-sm text-gray-500">
                    {{ number_format($summary['marks_obtained'] ?? 0, 2) }} / {{ number_format($summary['max_possible'] ?? 0, 2) }} marks
                </p>
            </div>
        </div>

        {{-- Stats Grid --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border p-4 text-center">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $summary['total_questions'] ?? 0 }}</div>
                <div class="text-xs text-gray-500">Total Questions</div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border p-4 text-center">
                <div class="text-2xl font-bold text-success-600">{{ $summary['correct'] ?? 0 }}</div>
                <div class="text-xs text-gray-500">Correct</div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border p-4 text-center">
                <div class="text-2xl font-bold text-danger-600">{{ $summary['incorrect'] ?? 0 }}</div>
                <div class="text-xs text-gray-500">Incorrect</div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border p-4 text-center">
                <div class="text-2xl font-bold text-gray-400">{{ $summary['unanswered'] ?? 0 }}</div>
                <div class="text-xs text-gray-500">Unanswered</div>
            </div>
        </div>

        @if($summary['manual_pending'] ?? 0 > 0)
            <div class="bg-warning-50 dark:bg-warning-900/20 border border-warning-200 rounded-xl p-4 text-sm text-warning-700">
                {{ $summary['manual_pending'] }} question(s) require manual marking by your teacher. The final score may change.
            </div>
        @endif

        {{-- Question Review --}}
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border">
            <div class="px-6 py-4 border-b">
                <h3 class="font-semibold text-gray-900 dark:text-white">Question Review</h3>
            </div>
            <div class="divide-y">
                @foreach($attempt->responses()->with('question')->get() as $idx => $response)
                    @php
                        $q = $response->question;
                    @endphp
                    <div class="px-6 py-4 space-y-2">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-bold {{ $response->is_correct ? 'text-success-600' : ($response->is_correct === false ? 'text-danger-600' : 'text-gray-400') }}">
                                        Q{{ $idx + 1 }}
                                    </span>
                                    <span class="text-xs text-gray-500">{{ $q?->question_type?->label() }}</span>
                                    <span class="text-xs text-gray-500">{{ $response->marks_possible }} marks</span>
                                    @if($response->marks_awarded !== null)
                                        <span class="text-xs font-medium {{ $response->marks_awarded > 0 ? 'text-success-600' : 'text-danger-600' }}">
                                            +{{ number_format($response->marks_awarded, 2) }}
                                        </span>
                                    @endif
                                </div>
                                <div class="prose prose-sm dark:prose-invert max-w-none text-sm">
                                    {!! $q?->question_text ?? 'Question unavailable' !!}
                                </div>
                            </div>
                            @if($response->is_correct === true)
                                <x-heroicon-o-check-circle class="w-5 h-5 text-success-500 flex-shrink-0" />
                            @elseif($response->is_correct === false)
                                <x-heroicon-o-x-circle class="w-5 h-5 text-danger-500 flex-shrink-0" />
                            @else
                                <x-heroicon-o-clock class="w-5 h-5 text-gray-400 flex-shrink-0" />
                            @endif
                        </div>

                        @if($response->learner_answer !== null)
                            <div class="text-sm">
                                @if($q?->question_type?->value === 'file_upload' && $response->file_path)
                                    <span class="text-gray-500">Your upload:</span>
                                    <a href="{{ asset('storage/' . $response->file_path) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-1.5 ml-1 font-medium text-primary-600 hover:underline">
                                        <x-heroicon-o-document class="w-4 h-4" />
                                        {{ $response->original_filename ?? 'View uploaded file' }}
                                        @if($response->file_size)
                                            <span class="text-xs text-gray-400">({{ round($response->file_size / 1024, 1) }} KB)</span>
                                        @endif
                                    </a>
                                @else
                                    <span class="text-gray-500">Your answer:</span>
                                    <span class="font-medium {{ $response->is_correct ? 'text-success-600' : 'text-danger-600' }}">
                                        @if(is_array($response->learner_answer))
                                            {{ implode(', ', array_map(fn($v) => is_array($v) ? json_encode($v) : $v, $response->learner_answer)) }}
                                        @else
                                            {{ $response->learner_answer }}
                                        @endif
                                    </span>
                                @endif
                            </div>
                        @endif

                        @if($response->correct_answer !== null && $response->is_correct === false && ($assessment?->show_feedback ?? true))
                            <div class="text-sm">
                                <span class="text-gray-500">Correct answer:</span>
                                <span class="font-medium text-success-600">
                                    @if(is_array($response->correct_answer))
                                        {{ implode(', ', array_map(fn($v) => is_array($v) ? json_encode($v) : $v, $response->correct_answer)) }}
                                    @else
                                        {{ $response->correct_answer }}
                                    @endif
                                </span>
                            </div>
                        @endif

                        @if($q?->explanation && ($assessment?->show_feedback ?? true))
                            <div class="text-sm bg-gray-50 dark:bg-gray-800 rounded-lg p-3 mt-2">
                                <span class="font-medium text-gray-700 dark:text-gray-300">Explanation:</span>
                                <span class="text-gray-600 dark:text-gray-400">{!! $q->explanation !!}</span>
                            </div>
                        @endif

                        @if($response->teacher_feedback)
                            <div class="text-sm bg-primary-50 dark:bg-primary-900/20 rounded-lg p-3 mt-2">
                                <span class="font-medium text-primary-700">Teacher Feedback:</span>
                                <span class="text-primary-600">{{ $response->teacher_feedback }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-filament-panels::page>
