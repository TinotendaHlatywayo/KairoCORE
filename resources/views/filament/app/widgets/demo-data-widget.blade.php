<div class="fi-widget sc-demo-data-widget rounded-xl border border-slate-200/70 bg-white p-5 shadow-sm dark:border-slate-700/60 dark:bg-gray-900">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3.5">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg"
                 style="background: {{ $this->hasDemoData ? 'rgba(16,185,129,0.12)' : 'rgba(245,158,11,0.14)' }};">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                     stroke-width="1.8" stroke="currentColor"
                     style="width: 1.35rem; height: 1.35rem; color: {{ $this->hasDemoData ? '#059669' : '#d97706' }};">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                </svg>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-800 dark:text-gray-100">
                    {{ $this->hasDemoData ? __('Demonstration data is active') : __('Seed demonstration data') }}
                </h3>
                <p class="mt-0.5 text-sm leading-relaxed text-slate-500 dark:text-gray-400">
                    @if ($this->hasDemoData)
                        {{ __(':students demo students with :reports academic reports, enrollments and marks are loaded across the system.', [
                            'students' => $this->demoStats['students'],
                            'reports' => $this->demoStats['reports'],
                        ]) }}
                    @else
                        {{ __('Populate the whole system with realistic test data — students, classes, assessments, marks and reports — for immediate testing.') }}
                    @endif
                </p>
            </div>
        </div>

        <div class="shrink-0">
            @if ($this->hasDemoData)
                <button type="button"
                        wire:click="wipe"
                        wire:loading.attr="disabled"
                        wire:target="wipe"
                        x-data
                        x-bind:disabled="{{ $this->busy ? 'true' : 'false' }}"
                        x-on:click="if (confirm(@js(__('Remove ALL demonstration students, enrollments, marks and reports? Real records are never touched.')))) $wire.wipe()"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60"
                        style="background: linear-gradient(135deg, #dc2626, #ef4444); box-shadow: 0 4px 14px -4px rgba(220,38,38,.45);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-.422 3.096-.422-3.096m0 0-.365-2.674a2.25 2.25 0 0 1 2.235-2.55h4.584a2.25 2.25 0 0 1 2.235 2.55l-.365 2.674m-8.324 0H6.18m11.64 0-1.36 9.93A2.25 2.25 0 0 1 14.22 21H9.78a2.25 2.25 0 0 1-2.24-2.044L6.18 12.096M19.41 5.25h-5.32m-3.66 0H4.59m3.658 0V4.687c0-.62.504-1.124 1.125-1.124h2.25c.621 0 1.125.504 1.125 1.124v.563" />
                    </svg>
                    <span wire:loading.remove wire:target="wipe">{{ __('Wipe Demo Data') }}</span>
                    <span wire:loading wire:target="wipe">{{ __('Removing...') }}</span>
                </button>
            @else
                <button type="button"
                        wire:click="seed"
                        wire:loading.attr="disabled"
                        wire:target="seed"
                        x-bind:disabled="{{ $this->busy ? 'true' : 'false' }}"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60"
                        style="background: linear-gradient(135deg, #d97706, #f59e0b); box-shadow: 0 4px 14px -4px rgba(245,158,11,.45);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span wire:loading.remove wire:target="seed">{{ __('Seed Demo Data') }}</span>
                    <span wire:loading wire:target="seed">{{ __('Seeding...') }}</span>
                </button>
            @endif
        </div>
    </div>
</div>
