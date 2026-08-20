<x-filament-panels::page>
    <div class="space-y-6">
        <div class="p-6 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-presentation-chart-bar" class="h-6 w-6 text-emerald-600" />
                {{ __('Guided Report Construction Engine') }}
            </h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                {{ __('This designer workspace helps you extract live data, format column schemas, custom style the template header configurations, and save the rules globally for immediate, repeatable generation.') }}
            </p>
        </div>

        <x-filament-panels::form wire:submit="submit">
            {{ $this->form }}
        </x-filament-panels::form>
    </div>
</x-filament-panels::page>