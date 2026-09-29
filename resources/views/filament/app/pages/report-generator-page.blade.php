@php
    /** @var \App\Filament\App\Pages\ReportGeneratorPage $this */
    $presets = $this->getPresets();
    $categories = $this->getPresetCategories();
    $active = $this->activePreset;
@endphp

<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Preset cards. Picking one fills the whole form below, so a routine
             report is two clicks: pick, generate. --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-sparkles" class="h-5 w-5 text-primary-600" />
                    {{ __('Start from a report') }}
                </div>
            </x-slot>

            <x-slot name="description">
                {{ __('Choose a ready-made report, or build your own below.') }}
            </x-slot>

            <div class="mb-4 flex flex-wrap items-center gap-2">
                @foreach ($categories as $label => $value)
                    <button
                        type="button"
                        wire:click="setPresetCategory(@js($value))"
                        @class([
                            'rounded-lg px-3 py-1.5 text-sm font-medium transition',
                            'bg-primary-600 text-white shadow-sm' => $this->presetCategory === $value,
                            'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' => $this->presetCategory !== $value,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <button
                    type="button"
                    wire:click="startCustomReport"
                    @class([
                        'group flex flex-col items-start gap-2 rounded-xl border p-4 text-left transition',
                        'border-primary-500 bg-primary-50 ring-1 ring-primary-500 dark:bg-primary-500/10' => $active === null,
                        'border-gray-200 bg-white hover:border-primary-400 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-gray-800' => $active !== null,
                    ])
                >
                    <div class="flex w-full items-center gap-2">
                        <x-filament::icon icon="heroicon-o-adjustments-horizontal" class="h-5 w-5 text-primary-600" />
                        <span class="text-sm font-semibold text-gray-950 dark:text-white">
                            {{ __('Build a custom report') }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Choose your own sources, columns, filters and totals.') }}
                    </p>
                </button>

                @foreach ($presets as $preset)
                    <button
                        type="button"
                        wire:click="applyPreset(@js($preset['name']))"
                        wire:loading.attr="disabled"
                        @class([
                            'group flex flex-col items-start gap-2 rounded-xl border p-4 text-left transition',
                            'border-primary-500 bg-primary-50 ring-1 ring-primary-500 dark:bg-primary-500/10' => $active === $preset['name'],
                            'border-gray-200 bg-white hover:border-primary-400 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-gray-800' => $active !== $preset['name'],
                        ])
                    >
                        <div class="flex w-full items-start gap-2">
                            <x-filament::icon :icon="$preset['icon']" class="h-5 w-5 shrink-0 text-primary-600" />
                            <span class="flex-1 text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $preset['name'] }}
                            </span>
                            @if ($preset['recommended'])
                                <span class="rounded-md bg-primary-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-primary-700 dark:bg-primary-500/20 dark:text-primary-300">
                                    {{ __('Popular') }}
                                </span>
                            @endif
                        </div>

                        <p class="line-clamp-2 text-xs text-gray-500 dark:text-gray-400">
                            {{ $preset['description'] }}
                        </p>

                        <div class="mt-auto flex items-center gap-2 text-[11px] text-gray-400 dark:text-gray-500">
                            <span>{{ $preset['category_label'] }}</span>
                            <span aria-hidden="true">&middot;</span>
                            <span>{{ trans_choice('{0} :count column|[1,*] :count columns', $preset['field_count'], ['count' => $preset['field_count']]) }}</span>
                            @if ($preset['dataset_count'] > 1)
                                <span aria-hidden="true">&middot;</span>
                                <span>{{ trans_choice('{0} :count source|[1,*] :count sources', $preset['dataset_count'], ['count' => $preset['dataset_count']]) }}</span>
                            @endif
                        </div>
                    </button>
                @endforeach
            </div>

            @if (empty($presets))
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('No presets in this category yet. Build a custom report instead.') }}
                </p>
            @endif
        </x-filament::section>

        <x-filament-panels::form wire:submit="submit">
            {{ $this->form }}

            <div class="mt-6 flex items-center gap-3">
                <x-filament::button type="submit" icon="heroicon-m-play">
                    {{ __('Generate report') }}
                </x-filament::button>

                @if ($active)
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Based on the preset') }} <strong class="font-semibold">{{ $active }}</strong>.
                        {{ __('Edit the columns below to customise it.') }}
                    </span>
                @endif
            </div>
        </x-filament-panels::form>
    </div>
</x-filament-panels::page>
