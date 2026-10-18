@php
    $notice = (string) request()->query('notice', '');
    $reason = trim((string) request()->query('reason', ''));
@endphp

@if ($notice !== '')
    <div
        x-data="{ show: true }"
        x-show="show"
        x-cloak
        class="mb-4 rounded-lg border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900 dark:border-rose-700 dark:bg-rose-950/40 dark:text-rose-200"
        role="alert"
    >
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <x-heroicon-o-exclamation-triangle class="mt-0.5 h-5 w-5 shrink-0" />
                <div>
                    <p class="font-semibold">
                        @if ($notice === 'entry_failed')
                            {{ __('Could not enter this school') }}
                        @else
                            {{ __('Heads up') }}
                        @endif
                    </p>
                    @if ($reason !== '')
                        <p class="mt-0.5">{{ $reason }}</p>
                    @endif
                    @if ($notice === 'entry_failed')
                        <p class="mt-1 text-xs opacity-80">
                            {{ __('Entry links expire quickly. Close this message and choose Enter school again to get a fresh link.') }}
                        </p>
                    @endif
                </div>
            </div>
            <button
                type="button"
                @click="show = false"
                class="shrink-0 rounded p-1 text-rose-500 hover:bg-rose-100 dark:hover:bg-rose-900/40"
                aria-label="{{ __('Dismiss') }}"
            >
                <x-heroicon-o-x-mark class="h-4 w-4" />
            </button>
        </div>
    </div>
@endif
