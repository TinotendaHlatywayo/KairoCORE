@php
    $current = app()->getLocale();
    $languages = [
        'en' => ['label' => 'English',    'flag' => '🇬🇧'],
        'sn' => ['label' => 'Shona',      'flag' => '🇿🇼'],
        'sw' => ['label' => 'Swahili',    'flag' => '🇹🇿'],
        'fr' => ['label' => 'Français',   'flag' => '🇫🇷'],
        'pt' => ['label' => 'Português',  'flag' => '🇵🇹'],
        'es' => ['label' => 'Español',    'flag' => '🇪🇸'],
    ];
    $currentLabel = $languages[$current]['label'] ?? 'English';
    $currentFlag  = $languages[$current]['flag']  ?? '🌐';
@endphp

<div
    x-data="{ open: false }"
    x-on:click.away="open = false"
    x-on:keydown.escape.window="open = false"
    class="relative"
>
    <button
        x-on:click="open = !open"
        class="flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-200 dark:hover:bg-white/10"
        title="{{ __('Switch language') }}"
    >
        <span class="text-base leading-none">{{ $currentFlag }}</span>
        <span class="hidden sm:inline">{{ $currentLabel }}</span>
        <x-heroicon-s-chevron-down class="h-3.5 w-3.5 opacity-50" />
    </button>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95 translate-y-1"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute right-0 z-50 mt-1.5 w-48 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-white/10 dark:bg-gray-900"
        style="display: none;"
    >
        <div class="py-1">
            @foreach($languages as $code => $lang)
                <button
                    wire:click="switchLocale('{{ $code }}')"
                    class="flex w-full items-center gap-2.5 px-3 py-2 text-sm transition hover:bg-gray-50 dark:hover:bg-white/5 {{ $code === $current ? 'bg-gray-50 font-semibold text-blue-600 dark:bg-white/5 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300' }}"
                >
                    <span class="text-base leading-none">{{ $lang['flag'] }}</span>
                    <span>{{ $lang['label'] }}</span>
                    @if($code === $current)
                        <x-heroicon-s-check class="ml-auto h-4 w-4 text-blue-500" />
                    @endif
                </button>
            @endforeach
        </div>
    </div>
</div>
