@php
    $pageClass = get_class(filament()->getCurrentPage() ?? new stdClass());
    $help = \App\Support\HelpContent::for($pageClass);
@endphp

<div x-data="{ open: false }" class="relative inline-flex items-center">
    <button
        @click="open = true"
        type="button"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 hover:text-primary-600 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-primary-400 transition"
        title="Page Help & Workflow Guide"
    >
        <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>{{ __('Help Guide') }}</span>
    </button>

    <!-- Modal Overlay -->
    <template x-teleport="body">
        <div
            x-show="open"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-950/50 backdrop-blur-sm"
            x-transition.opacity
            style="display: none;"
        >
            <div
                @click.away="open = false"
                class="w-full max-w-2xl max-h-[85vh] overflow-y-auto bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 p-6 space-y-6"
                x-transition
            >
                <!-- Modal Header -->
                <div class="flex items-start justify-between border-b border-gray-200 dark:border-gray-800 pb-4">
                    <div class="space-y-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-950 dark:text-primary-300">
                            {{ __('Interactive Workflow Guide') }}
                        </span>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $help['title'] }}
                        </h3>
                    </div>
                    <button @click="open = false" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body: Summary & Workflow -->
                <div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
                    <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-800">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">{{ __('Overview') }}</h4>
                        <p>{{ $help['summary'] }}</p>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ __('Step-by-Step Workflow') }}</h4>
                        <ul class="space-y-2">
                            @foreach($help['workflow'] as $step)
                                <li class="flex items-start gap-2">
                                    <span class="flex-shrink-0 w-5 h-5 rounded-full bg-primary-500/10 text-primary-600 dark:text-primary-400 flex items-center justify-center text-xs font-bold mt-0.5">✓</span>
                                    <span>{!! str_replace(['**', '—'], ['<strong>', '—</strong>'], $step) !!}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    @if(!empty($help['details']))
                        <div class="border-t border-gray-200 dark:border-gray-800 pt-4 space-y-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white">{{ __('Context & Relationships') }}</h4>
                            @foreach($help['details'] as $label => $text)
                                <div class="text-xs">
                                    <strong class="text-gray-900 dark:text-white">{{ $label }}:</strong> {{ $text }}
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-800">
                    <button
                        @click="open = false"
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 rounded-xl transition shadow-sm"
                    >
                        {{ __('Got it, close guide') }}
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
