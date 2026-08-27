<x-filament-panels::page>
    @if(! $assessment)
        <div class="text-center py-12 text-gray-500">Assessment not found.</div>
    @else
        <div class="max-w-3xl mx-auto space-y-6">

            {{-- Back Link --}}
            <a href="{{ \App\Filament\Student\Resources\StudentAssessmentResource::getUrl('index') }}"
               class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <x-heroicon-o-arrow-left class="w-4 h-4"/>
                Back to Assessments
            </a>

            {{-- Assessment Header --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-extrabold text-slate-900 dark:text-white">{{ $assessment->title }}</h1>
                        <p class="mt-1 text-sm text-slate-500">{{ $assessment->subject?->name ?? 'General' }}</p>
                    </div>
                    @php
                        $catColor = match($assessment->assessment_category?->value ?? 'quiz') {
                            'quiz' => 'blue',
                            'test' => 'purple',
                            'exam' => 'red',
                            'assignment' => 'green',
                            default => 'gray',
                        };
                    @endphp
                    <span class="inline-flex rounded-full bg-{{ $catColor }}-100 px-3 py-1 text-xs font-bold text-{{ $catColor }}-700 dark:bg-{{ $catColor }}-900/30 dark:text-{{ $catColor }}-300">
                        {{ $assessment->assessment_category?->label() ?? 'Quiz' }}
                    </span>
                </div>

                @if($assessment->description)
                    <p class="mt-3 text-sm text-slate-600 dark:text-slate-400">{{ $assessment->description }}</p>
                @endif
            </div>

            {{-- Assessment Info Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="rounded-xl border border-slate-200 bg-white p-4 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ $assessment->questions_count ?? 0 }}</div>
                    <div class="text-xs text-slate-500">Questions</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ $assessment->total_marks }}</div>
                    <div class="text-xs text-slate-500">Total Marks</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ $assessment->duration_minutes ? $assessment->duration_minutes . 'm' : '∞' }}</div>
                    <div class="text-xs text-slate-500">Duration</div>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="text-2xl font-extrabold text-slate-900 dark:text-white">{{ $assessment->pass_mark }}%</div>
                    <div class="text-xs text-slate-500">Pass Mark</div>
                </div>
            </div>

            {{-- Attempt Status --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">Your Attempts</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <div class="text-lg font-bold text-blue-600">{{ $attemptCount }}</div>
                        <div class="text-xs text-slate-500">Taken</div>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-emerald-600">{{ $attemptsRemaining }}</div>
                        <div class="text-xs text-slate-500">Remaining</div>
                    </div>
                    <div>
                        <div class="text-lg font-bold text-slate-600">{{ $assessment->max_attempts }}</div>
                        <div class="text-xs text-slate-500">Max Allowed</div>
                    </div>
                </div>
            </div>

            {{-- Instructions --}}
            @if($assessment->instructions)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 shadow-sm dark:border-amber-800 dark:bg-amber-950/20">
                <h3 class="text-sm font-bold text-amber-800 dark:text-amber-200 mb-2 flex items-center gap-2">
                    <x-heroicon-o-information-circle class="w-4 h-4"/>
                    Instructions
                </h3>
                <div class="text-sm text-amber-700 dark:text-amber-300 whitespace-pre-line">{{ $assessment->instructions }}</div>
            </div>
            @endif

            {{-- Rules --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">Rules & Settings</h3>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="flex items-center gap-2">
                        @if($assessment->randomize_questions)
                            <x-heroicon-o-check-circle class="w-4 h-4 text-emerald-500"/>
                        @else
                            <x-heroicon-o-x-circle class="w-4 h-4 text-slate-300"/>
                        @endif
                        <span class="{{ $assessment->randomize_questions ? 'text-slate-700 dark:text-slate-300' : 'text-slate-400' }}">Randomized Questions</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($assessment->allow_backward_navigation)
                            <x-heroicon-o-check-circle class="w-4 h-4 text-emerald-500"/>
                        @else
                            <x-heroicon-o-x-circle class="w-4 h-4 text-slate-300"/>
                        @endif
                        <span class="{{ $assessment->allow_backward_navigation ? 'text-slate-700 dark:text-slate-300' : 'text-slate-400' }}">Backward Navigation</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($assessment->allow_question_skipping)
                            <x-heroicon-o-check-circle class="w-4 h-4 text-emerald-500"/>
                        @else
                            <x-heroicon-o-x-circle class="w-4 h-4 text-slate-300"/>
                        @endif
                        <span class="{{ $assessment->allow_question_skipping ? 'text-slate-700 dark:text-slate-300' : 'text-slate-400' }}">Skip Questions</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($assessment->show_feedback)
                            <x-heroicon-o-check-circle class="w-4 h-4 text-emerald-500"/>
                        @else
                            <x-heroicon-o-x-circle class="w-4 h-4 text-slate-300"/>
                        @endif
                        <span class="{{ $assessment->show_feedback ? 'text-slate-700 dark:text-slate-300' : 'text-slate-400' }}">Show Feedback</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($assessment->calculator_enabled)
                            <x-heroicon-o-check-circle class="w-4 h-4 text-emerald-500"/>
                        @else
                            <x-heroicon-o-x-circle class="w-4 h-4 text-slate-300"/>
                        @endif
                        <span class="{{ $assessment->calculator_enabled ? 'text-slate-700 dark:text-slate-300' : 'text-slate-400' }}">Calculator</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($assessment->anti_cheating_enabled)
                            <x-heroicon-o-shield-check class="w-4 h-4 text-amber-500"/>
                        @else
                            <x-heroicon-o-x-circle class="w-4 h-4 text-slate-300"/>
                        @endif
                        <span class="{{ $assessment->anti_cheating_enabled ? 'text-amber-700 dark:text-amber-300 font-semibold' : 'text-slate-400' }}">
                            Anti-Cheating {{ $assessment->anti_cheating_enabled ? '(Enabled)' : '' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Action Button --}}
            <div class="flex justify-center">
                @if($blockReason)
                    <div class="text-center">
                        <p class="text-sm text-red-600 dark:text-red-400 mb-3">{{ $blockReason }}</p>
                        @if($currentAttempt)
                            <a href="{{ \App\Filament\Student\Pages\TakeAssessmentPage::getUrl([$assessment->id]) }}"
                               class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-8 py-3 text-sm font-bold text-white shadow-lg hover:bg-amber-400 transition">
                                <x-heroicon-o-arrow-right class="w-5 h-5"/>
                                Continue Assessment
                            </a>
                        @endif
                    </div>
                @else
                    <a href="{{ \App\Filament\Student\Pages\TakeAssessmentPage::getUrl([$assessment->id]) }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-8 py-3 text-sm font-bold text-white shadow-lg hover:bg-blue-500 transition">
                        <x-heroicon-o-play class="w-5 h-5"/>
                        {{ $currentAttempt ? 'Continue Assessment' : 'Start Assessment' }}
                    </a>
                @endif
            </div>

        </div>
    @endif
</x-filament-panels::page>
