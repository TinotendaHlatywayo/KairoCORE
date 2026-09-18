<x-filament-widgets::widget>
    <div class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <div class="mr-2 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
            <x-heroicon-o-arrow-down-tray class="h-5 w-5 text-primary-500" />
            <span class="font-medium">{{ __('Download Official Statement') }}</span>
        </div>
        <x-filament::button color="primary" size="sm" icon="heroicon-o-document-text" wire:click="downloadPdf">
            {{ __('PDF') }}
        </x-filament::button>
        <x-filament::button color="gray" size="sm" icon="heroicon-o-table-cells" wire:click="downloadExcel">
            {{ __('Excel') }}
        </x-filament::button>
        <x-filament::button color="gray" size="sm" icon="heroicon-o-document" wire:click="downloadTxt">
            {{ __('TXT') }}
        </x-filament::button>
    </div>
</x-filament-widgets::widget>