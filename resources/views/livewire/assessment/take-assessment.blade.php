@php
    $questions = $this->questions;
    $current = $this->currentQuestion;
    $q = $current?->question;
    $total = $questions->count();
    $answered = $this->getAnsweredCount();
@endphp

@if($this->submitted)
    {{-- ── SUBMITTED STATE ── --}}
    <div class="flex items-center justify-center min-h-[60vh]">
        <div class="text-center space-y-4">
            <x-heroicon-o-check-circle class="w-16 h-16 mx-auto text-success-500" />
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Assessment Submitted!</h2>
            <p class="text-gray-500">Your answers have been recorded successfully.</p>
            <a href="{{ route('filament.student.pages.attempt-result', ['attempt' => $this->attemptId]) }}"
               class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition">
                <x-heroicon-o-chart-bar class="w-5 h-5 mr-2" />
                View Results
            </a>
            <a href="{{ route('filament.student.resources.digital-assessments.index') }}"
               class="inline-flex items-center px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
                Back to Assessments
            </a>
        </div>
    </div>
@else
    {{-- ── ASSESSMENT HEADER ── --}}
    <div class="sticky top-0 z-30 bg-white dark:bg-gray-900 border-b shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-white truncate max-w-md">
                    {{ $this->assessment?->title }}
                </h1>
                <span class="text-sm text-gray-500 hidden sm:inline">
                    {{ $this->assessment?->subject?->name }}
                </span>
            </div>

            <div class="flex items-center gap-4">
                {{-- Adaptive Difficulty --}}
                @if($this->isAdaptive)
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-info-100 dark:bg-info-900/30 text-info-700 dark:text-info-300">
                        <x-heroicon-o-adjustments-vertical class="w-4 h-4" />
                        Difficulty: {{ $this->currentDifficulty }}%
                    </div>
                @endif

                {{-- Progress --}}
                <div class="text-sm text-gray-500">
                    {{ $answered }} / {{ $total }} answered
                </div>

                {{-- Timer --}}
                @if($this->assessment?->duration_minutes)
                    <div wire:poll.1s="tick"
                         class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-mono text-sm font-bold
                                {{ $this->secondsRemaining < 300 ? 'bg-danger-100 text-danger-700 animate-pulse' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300' }}">
                        <x-heroicon-o-clock class="w-4 h-4" />
                        {{ $this->formatTime($this->secondsRemaining) }}
                    </div>
                @endif

                {{-- Submit --}}
                <button wire:click="submit"
                        wire:confirm="Are you sure you want to submit? You cannot change your answers after submission."
                        class="px-4 py-2 bg-success-600 text-white text-sm font-medium rounded-lg hover:bg-success-700 transition">
                    Submit
                </button>
            </div>
        </div>
    </div>

    {{-- ── MAIN CONTENT ── --}}
    <div class="max-w-7xl mx-auto px-4 py-6 flex gap-6">
        {{-- ── QUESTION NAVIGATION SIDEBAR ── --}}
        <div class="hidden lg:block w-64 flex-shrink-0">
            <div class="sticky top-24 space-y-1">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                    @if($this->isAdaptive)
                        Adaptive Mode
                    @else
                        Questions
                    @endif
                </h3>
                @if($this->isAdaptive)
                    <div class="text-xs text-info-600 dark:text-info-400 bg-info-50 dark:bg-info-900/20 rounded-lg p-2 mb-2">
                        Questions adapt based on your performance. Navigate with Next.
                    </div>
                @endif
                <div class="grid grid-cols-5 gap-1.5">
                    @foreach($questions as $i => $resp)
                        @php
                            $ansVal = $this->answers[$resp->question_bank_id] ?? null;
                            $isAnswered = $ansVal !== null && $ansVal !== '' && $ansVal !== [];
                            $isCurrent = $i === $this->currentQuestionIndex;
                        @endphp
                        <button wire:click="goToQuestion({{ $i }})"
                                @if($this->isAdaptive) disabled @endif
                                class="w-10 h-10 rounded-lg text-sm font-medium transition
                                       {{ $isCurrent ? 'ring-2 ring-primary-500 bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : '' }}
                                       {{ !$isCurrent && $isAnswered ? 'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400' : '' }}
                                       {{ !$isCurrent && !$isAnswered ? 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700' : '' }}
                                       {{ $this->isAdaptive ? 'opacity-50 cursor-not-allowed' : '' }}">
                            {{ $i + 1 }}
                        </button>
                    @endforeach
                </div>

                <div class="mt-4 space-y-1 text-xs text-gray-500">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded bg-success-100 border border-success-300"></span> Answered
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded bg-gray-100 border border-gray-300"></span> Unanswered
                    </div>
                </div>
            </div>
        </div>

        {{-- ── QUESTION AREA ── --}}
        <div class="flex-1 min-w-0">
            @if($current && $q)
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border p-6 space-y-6">
                    {{-- Question Header --}}
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-xs font-bold text-primary-600 bg-primary-50 dark:bg-primary-900/20 px-2 py-0.5 rounded">
                                    Question {{ $this->currentQuestionIndex + 1 }} of {{ $total }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ $q->question_type?->label() }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ $current->marks_possible ?? $q->marks }} marks
                                </span>
                            </div>

                            <div class="prose prose-sm dark:prose-invert max-w-none">
                                {!! $q->question_text !!}
                            </div>
                        </div>
                    </div>

                    {{-- Images --}}
                    @if($q->images && count($q->images) > 0)
                        <div class="flex flex-wrap gap-3">
                            @foreach($q->images as $image)
                                <img src="{{ asset('storage/' . $image) }}" alt="Question image"
                                     class="max-h-48 rounded-lg border object-contain" />
                            @endforeach
                        </div>
                    @endif

                    {{-- Answer Input --}}
                    <div class="border-t pt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Your Answer</label>

                        @php
                            $qId = $current->question_bank_id;
                            $currentAnswer = $this->answers[$qId] ?? null;
                        @endphp

                        {{-- Multiple Choice --}}
                        @if($q->question_type?->value === 'multiple_choice')
                            <div class="space-y-2">
                                @foreach($q->options ?? [] as $optIdx => $opt)
                                    <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition
                                                {{ $currentAnswer == $optIdx ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300' }}">
                                        <input type="radio"
                                               wire:model.live="answers.{{ $qId }}"
                                               value="{{ $optIdx }}"
                                               class="mt-0.5 text-primary-600 focus:ring-primary-500" />
                                        <span class="text-sm text-gray-800 dark:text-gray-200">{{ $opt['text'] ?? $opt }}</span>
                                    </label>
                                @endforeach
                            </div>

                        {{-- Multiple Select --}}
                        @elseif($q->question_type?->value === 'multiple_select')
                            <div class="space-y-2">
                                @foreach($q->options ?? [] as $optIdx => $opt)
                                    @php
                                        $selected = is_array($currentAnswer) && in_array($optIdx, $currentAnswer);
                                    @endphp
                                    <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition
                                                {{ $selected ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300' }}">
                                        <input type="checkbox"
                                               wire:model.live="answers.{{ $qId }}.{{ $optIdx }}"
                                               value="{{ $optIdx }}"
                                               class="mt-0.5 text-primary-600 focus:ring-primary-500" />
                                        <span class="text-sm text-gray-800 dark:text-gray-200">{{ $opt['text'] ?? $opt }}</span>
                                    </label>
                                @endforeach
                            </div>

                        {{-- True / False --}}
                        @elseif($q->question_type?->value === 'true_false')
                            <div class="flex gap-3">
                                <label class="flex-1 flex items-center justify-center gap-2 p-4 rounded-lg border cursor-pointer transition
                                            {{ $currentAnswer === '1' || $currentAnswer === 1 || $currentAnswer === true ? 'border-success-500 bg-success-50 dark:bg-success-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300' }}">
                                    <input type="radio" wire:model.live="answers.{{ $qId }}" value="1" class="hidden" />
                                    <x-heroicon-o-check-circle class="w-5 h-5" />
                                    <span class="font-medium">True</span>
                                </label>
                                <label class="flex-1 flex items-center justify-center gap-2 p-4 rounded-lg border cursor-pointer transition
                                            {{ $currentAnswer === '0' || $currentAnswer === 0 || $currentAnswer === false ? 'border-danger-500 bg-danger-50 dark:bg-danger-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300' }}">
                                    <input type="radio" wire:model.live="answers.{{ $qId }}" value="0" class="hidden" />
                                    <x-heroicon-o-x-circle class="w-5 h-5" />
                                    <span class="font-medium">False</span>
                                </label>
                            </div>

                        {{-- Numeric --}}
                        @elseif($q->question_type?->value === 'numeric')
                            <input type="number"
                                   step="any"
                                   wire:model.live="answers.{{ $qId }}"
                                   placeholder="Enter your answer..."
                                   class="w-full max-w-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm focus:border-primary-500 focus:ring-primary-500" />

                        {{-- Short Answer --}}
                        @elseif($q->question_type?->value === 'short_answer')
                            <input type="text"
                                   wire:model.live="answers.{{ $qId }}"
                                   placeholder="Type your answer..."
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm focus:border-primary-500 focus:ring-primary-500" />

                        {{-- Fill in the Blank --}}
                        @elseif($q->question_type?->value === 'fill_in_the_blank')
                            <input type="text"
                                   wire:model.live="answers.{{ $qId }}"
                                   placeholder="Fill in the blank..."
                                   class="w-full max-w-md rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm focus:border-primary-500 focus:ring-primary-500" />

                        {{-- Matching --}}
                        @elseif($q->question_type?->value === 'matching')
                            <div class="space-y-3">
                                @foreach($q->matching_pairs ?? [] as $pairIdx => $pair)
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300 w-40 truncate">{{ $pair['left'] }}</span>
                                        <x-heroicon-o-arrow-right class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                        <input type="text"
                                               wire:model.live="answers.{{ $qId }}.{{ $pairIdx }}.right"
                                               value="{{ $pair['right'] }}"
                                               placeholder="Match..."
                                               class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm focus:border-primary-500 focus:ring-primary-500" />
                                    </div>
                                @endforeach
                            </div>

                        {{-- Ordering --}}
                        @elseif($q->question_type?->value === 'ordering')
                            @php
                                $defaultItems = collect($q->ordering_items ?? [])->map(function ($item) {
                                    return ['text' => is_array($item) ? ($item['text'] ?? '') : $item];
                                })->values()->all();
                                $orderItems = is_array($currentAnswer) && ! empty($currentAnswer)
                                    ? array_values($currentAnswer)
                                    : $defaultItems;
                            @endphp
                            <div class="space-y-2">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Use the arrows to arrange the items in the correct order (top = first).</p>
                                @foreach($orderItems as $ordIdx => $orderItem)
                                    @php
                                        $ordText = is_array($orderItem) ? ($orderItem['text'] ?? $orderItem) : $orderItem;
                                    @endphp
                                    <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                        <div class="flex flex-col">
                                            <button type="button"
                                                    wire:click="moveOrderingItem({{ $qId }}, {{ $ordIdx }}, 'up')"
                                                    @if($ordIdx === 0) disabled @endif
                                                    class="text-gray-400 hover:text-primary-600 disabled:opacity-30 disabled:cursor-not-allowed">
                                                <x-heroicon-o-chevron-up class="w-4 h-4" />
                                            </button>
                                            <button type="button"
                                                    wire:click="moveOrderingItem({{ $qId }}, {{ $ordIdx }}, 'down')"
                                                    @if($ordIdx === count($orderItems) - 1) disabled @endif
                                                    class="text-gray-400 hover:text-primary-600 disabled:opacity-30 disabled:cursor-not-allowed">
                                                <x-heroicon-o-chevron-down class="w-4 h-4" />
                                            </button>
                                        </div>
                                        <span class="text-xs font-bold text-gray-400 w-6">{{ $ordIdx + 1 }}.</span>
                                        <span class="text-sm text-gray-800 dark:text-gray-200 flex-1">{{ $ordText }}</span>
                                    </div>
                                @endforeach
                            </div>

                        {{-- Essay --}}
                        @elseif($q->question_type?->value === 'essay')
                            <textarea
                                wire:model.live="answers.{{ $qId }}"
                                rows="6"
                                placeholder="Write your answer here..."
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm focus:border-primary-500 focus:ring-primary-500"></textarea>

                        {{-- File Upload --}}
                        @elseif($q->question_type?->value === 'file_upload')
                            @php
                                $existingFile = $current?->file_path ?? $currentAnswer;
                                $hasUploadedFile = $existingFile && is_string($existingFile) && $existingFile !== '';
                            @endphp

                            @if($hasUploadedFile)
                                <div class="space-y-3">
                                    <div class="flex items-center gap-3 p-4 bg-success-50 dark:bg-success-900/20 border border-success-200 dark:border-success-800 rounded-lg">
                                        <x-heroicon-o-document-check class="w-8 h-8 text-success-600 flex-shrink-0" />
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-success-800 dark:text-success-200 truncate">
                                                {{ $current?->original_filename ?? 'Uploaded file' }}
                                            </p>
                                            <p class="text-xs text-success-600 dark:text-success-400 mt-0.5">
                                                @if($current?->file_size)
                                                    {{ round($current->file_size / 1024, 1) }} KB
                                                @endif
                                                @if($current?->file_mime)
                                                    · {{ $current->file_mime }}
                                                @endif
                                            </p>
                                        </div>
                                        <a href="{{ asset('storage/' . $existingFile) }}"
                                           target="_blank"
                                           class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-success-700 dark:text-success-300 bg-white dark:bg-gray-800 border border-success-300 dark:border-success-700 rounded-lg hover:bg-success-100 dark:hover:bg-success-900/40 transition">
                                            <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" />
                                            Download
                                        </a>
                                    </div>
                                    <button wire:click="removeFile"
                                            wire:confirm="Remove this file? You can upload a new one after."
                                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-danger-600 hover:text-danger-800 dark:text-danger-400 dark:hover:text-danger-300 transition">
                                        <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
                                        Remove &amp; re-upload
                                    </button>
                                </div>
                            @else
                                <div class="space-y-3">
                                    <div class="flex items-center gap-4">
                                        <label class="flex-1 flex items-center justify-center gap-3 px-6 py-8 border-2 border-dashed rounded-lg cursor-pointer transition
                                                    {{ $file ? 'border-success-400 bg-success-50 dark:bg-success-900/20' : 'border-gray-300 dark:border-gray-600 hover:border-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/10' }}">
                                            <input type="file"
                                                   wire:model.live="file"
                                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                                   style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0);" />
                                            <x-heroicon-o-cloud-arrow-up class="w-8 h-8 {{ $file ? 'text-success-500' : 'text-gray-400' }}" />
                                            <div class="text-center">
                                                @if($file)
                                                    <p class="text-sm font-medium text-success-700 dark:text-success-300">{{ $file->getClientOriginalName() }}</p>
                                                    <p class="text-xs text-success-500 mt-0.5">{{ round($file->getSize() / 1024, 1) }} KB</p>
                                                @else
                                                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Click to upload or drag &amp; drop</p>
                                                    <p class="text-xs text-gray-500 mt-0.5">PDF, DOCX, images, or any file (max 50MB)</p>
                                                @endif
                                            </div>
                                        </label>
                                    </div>

                                    @if($file)
                                        <div class="flex items-center gap-3">
                                            <button wire:click="uploadFile"
                                                    wire:loading.attr="disabled"
                                                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 disabled:opacity-50 transition">
                                                <x-heroicon-o-arrow-up-tray class="w-4 h-4" />
                                                <span wire:loading.remove wire:target="uploadFile">Upload File</span>
                                                <span wire:loading wire:target="uploadFile">Uploading...</span>
                                            </button>
                                            <button wire:click="$set('file', null)"
                                                    class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition">
                                                Cancel
                                            </button>
                                        </div>
                                    @endif

                                    @error('file')
                                        <p class="text-xs text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                        @endif
                    </div>
                </div>

                {{-- Navigation --}}
                <div class="flex items-center justify-between mt-4">
                    <button wire:click="previousQuestion"
                            {{ $this->currentQuestionIndex === 0 ? 'disabled' : '' }}
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border rounded-lg
                                   hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        <x-heroicon-o-arrow-left class="w-4 h-4 mr-1" />
                        Previous
                    </button>

                    @if($this->currentQuestionIndex === $total - 1)
                        <button wire:click="submit"
                                wire:confirm="Are you sure you want to submit?"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-success-600 rounded-lg
                                       hover:bg-success-700 transition">
                            <x-heroicon-o-check class="w-4 h-4 mr-1" />
                            Submit Assessment
                        </button>
                    @else
                        <button wire:click="nextQuestion"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg
                                       hover:bg-primary-700 transition">
                            Next
                            <x-heroicon-o-arrow-right class="w-4 h-4 ml-1" />
                        </button>
                    @endif
                </div>
            @else
                <div class="text-center py-12 text-gray-500">Loading question...</div>
            @endif
        </div>
    </div>

    {{-- Prevent leaving --}}
    @if(!$this->submitted)
        <script>
            window.addEventListener('beforeunload', function (e) {
                e.preventDefault();
                e.returnValue = '';
            });

            @if($this->assessment?->anti_cheating_enabled)
            let tabSwitchCount = 0;
            const maxSwitches = 5;

            document.addEventListener('visibilitychange', function() {
                if (document.hidden && !window.submitted) {
                    tabSwitchCount++;

                    if (tabSwitchCount >= maxSwitches) {
                        Livewire.find('{{ $this->id }}').submit();
                    }
                }
            });

            window.addEventListener('blur', function() {
                if (!window.submitted) {
                    tabSwitchCount++;
                }
            });
            @endif
        </script>
    @endif
@endif
