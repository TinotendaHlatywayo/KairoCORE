<div
    x-data="{ active: false, percent: 0 }"
    x-cloak
    @livewire-upload-start.window="active = true; percent = 0"
    @livewire-upload-progress.window="percent = $event.detail.progress"
    @livewire-upload-finish.window="active = false"
    @livewire-upload-error.window="active = false"
    @livewire-upload-cancel.window="active = false"
    x-show="active"
    x-transition.opacity.duration.150ms
    class="pointer-events-none fixed inset-x-0 bottom-0 z-[999]"
    role="status"
    aria-live="polite"
    style="display: none;"
>
    <style>[x-cloak] { display: none !important; }</style>
    <div class="mx-auto max-w-3xl px-4 pb-6">
        <div class="overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center gap-3 px-4 py-3">
                <svg class="h-5 w-5 shrink-0 animate-spin text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ __('Uploading…') }}
                        <span class="tabular-nums" x-text="percent + '%'"></span>
                    </p>
                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                        <div
                            class="h-full rounded-full bg-primary-600 transition-[width] duration-200 ease-out"
                            :style="'width: ' + percent + '%'"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
