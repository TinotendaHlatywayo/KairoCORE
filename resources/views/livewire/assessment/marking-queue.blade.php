@php
    $response = $this->currentResponse;
    $question = $response?->question;
    $attempt = $response?->attempt;
    $student = $attempt?->student;
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                Marking Queue
                @if($this->assessment)
                    — {{ $this->assessment->title }}
                @endif
            </h1>
            @if($this->assessment)
                <p class="text-sm text-gray-500">{{ $this->assessment->subject?->name }}</p>
            @endif
        </div>
        <div class="text-right">
            <div class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $this->markedCount }} / {{ $this->totalInQueue }} marked
            </div>
            <div class="w-48 bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
                <div class="bg-success-500 h-2 rounded-full transition-all"
                     style="width: {{ $this->getProgressPercentage() }}%"></div>
            </div>
        </div>
    </div>

    @if($this->totalInQueue === 0)
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border p-12 text-center">
            <x-heroicon-o-check-circle class="w-12 h-12 mx-auto text-success-500" />
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mt-4">All Marked!</h2>
            <p class="text-gray-500 mt-2">All subjective responses have been graded.</p>
        </div>
    @else
        <div class="flex gap-6">
            {{-- ── QUEUE LIST ── --}}
            <div class="w-72 flex-shrink-0 hidden lg:block">
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border overflow-hidden">
                    <div class="px-4 py-3 border-b bg-gray-50 dark:bg-gray-800">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase">Pending ({{ $this->totalInQueue }})</h3>
                    </div>
                    <div class="divide-y max-h-[60vh] overflow-y-auto">
                        @foreach($this->queue as $item)
                            @php
                                $isActive = $this->currentResponseId === $item['id'];
                                $qType = $item['question']['question_type'] ?? '';
                            @endphp
                            <button wire:click="selectResponse({{ $item['id'] }})
                                    class="w-full text-left px-4 py-3 transition
                                           {{ $isActive ? 'bg-primary-50 dark:bg-primary-900/20 border-l-2 border-primary-500' : 'hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $item['attempt']['student']['first_name'] ?? '' }} {{ $item['attempt']['student']['last_name'] ?? '' }}
                                </div>
                                <div class="text-xs text-gray-500 mt-0.5">
                                    {{ $item['question']['question_type'] ?? $qType }} — {{ $item['marks_possible'] ?? 0 }} marks
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ── MARKING AREA ── --}}
            <div class="flex-1 min-w-0">
                @if($response && $question)
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border space-y-0 overflow-hidden">
                        {{-- Student Info --}}
                        <div class="px-6 py-4 border-b bg-gray-50 dark:bg-gray-800 flex items-center justify-between">
                            <div>
                                <span class="font-semibold text-gray-900 dark:text-white">
                                    {{ $student?->first_name }} {{ $student?->last_name }}
                                </span>
                                <span class="text-sm text-gray-500 ml-2">
                                    Attempt #{{ $attempt?->attempt_number }}
                                </span>
                            </div>
                            <span class="text-xs font-medium text-gray-500 bg-white dark:bg-gray-700 px-2 py-1 rounded">
                                {{ $question->question_type?->label() }} — {{ $response->marks_possible }} marks
                            </span>
                        </div>

                        {{-- Question --}}
                        <div class="px-6 py-4 border-b">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">Question</h3>
                            <div class="prose prose-sm dark:prose-invert max-w-none">
                                {!! $question->question_text !!}
                            </div>
                            @if($question->explanation)
                                <div class="mt-3 text-sm bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                    <span class="font-medium text-gray-600">Model Answer:</span>
                                    <span class="text-gray-500">{!! $question->explanation !!}</span>
                                </div>
                            @endif
                        </div>

                        {{-- Learner Answer --}}
                        <div class="px-6 py-4 border-b">
                            <h3 class="text-xs font-semibold text-gray-500 uppercase mb-2">Learner's Answer</h3>
                            @if($question->question_type?->value === 'file_upload')
                                @if($response->file_path)
                                    <div class="space-y-2">
                                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                            <x-heroicon-o-document class="w-8 h-8 text-primary-500 flex-shrink-0" />
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                    {{ $response->original_filename ?? 'Uploaded file' }}
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    @if($response->file_size)
                                                        {{ round($response->file_size / 1024, 1) }} KB
                                                    @endif
                                                    @if($response->file_mime)
                                                        · {{ $response->file_mime }}
                                                    @endif
                                                </p>
                                            </div>
                                            <a href="{{ asset('storage/' . $response->file_path) }}"
                                               target="_blank"
                                               class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-primary-600 bg-white dark:bg-gray-700 border border-primary-200 dark:border-primary-800 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20 transition">
                                                <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" />
                                                Download
                                            </a>
                                        </div>
                                    </div>
                                @elseif($response->learner_answer && is_string($response->learner_answer))
                                    <a href="{{ asset('storage/' . $response->learner_answer) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-2 text-primary-600 hover:underline">
                                        <x-heroicon-o-document class="w-5 h-5" />
                                        View Uploaded File
                                    </a>
                                @else
                                    <p class="text-gray-400">No file uploaded</p>
                                @endif
                            @else
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap">
                                    {{ is_array($response->learner_answer) ? json_encode($response->learner_answer, JSON_PRETTY_PRINT) : $response->learner_answer }}
                                </div>
                            @endif
                        </div>

                        {{-- Marking Form --}}
                        <div class="px-6 py-4 space-y-4">
                            <div class="flex items-end gap-4">
                                <div class="flex-1 max-w-xs">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Marks (out of {{ $response->marks_possible }})
                                    </label>
                                    <input type="number"
                                           wire:model.live="marksAwarded"
                                           min="0"
                                           max="{{ $response->marks_possible }}"
                                           step="0.25"
                                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm focus:border-primary-500 focus:ring-primary-500" />
                                </div>

                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Feedback (optional)
                                    </label>
                                    <input type="text"
                                           wire:model.live="feedback"
                                           placeholder="Brief feedback for the student..."
                                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm focus:border-primary-500 focus:ring-primary-500" />
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <button wire:click="markResponse"
                                        class="inline-flex items-center px-4 py-2 bg-success-600 text-white text-sm font-medium rounded-lg hover:bg-success-700 transition">
                                    <x-heroicon-o-check class="w-4 h-4 mr-1" />
                                    Mark & Next
                                </button>

                                <button wire:click="skipResponse"
                                        class="inline-flex items-center px-4 py-2 text-gray-600 dark:text-gray-400 text-sm font-medium hover:text-gray-900 dark:hover:text-white transition">
                                    Skip →
                                </button>

                                @if($response->marked_at)
                                    <span class="text-xs text-success-600">✓ Already marked</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border p-12 text-center">
                        <p class="text-gray-500">Select a response from the queue to start marking.</p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
