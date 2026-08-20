<x-filament-panels::page>
    <div class="max-w-xl">
        <div class="p-6 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
            <div class="flex items-center space-x-3 mb-6">
                <div class="p-2.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-lg">
                    <x-heroicon-o-document-arrow-down class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ __('Enterprise Data Exporter') }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Download complete local datasets from your workspace modules.') }}</p>
                </div>
            </div>

            <!-- Wire submit mapping correctly to compile and download logic -->
            <form wire:submit.prevent="downloadDataset" class="space-y-6">
                <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800">
                    {{ $this->form }}
                </div>

                <!-- Green Button with explicit inline-css variables (Guarantees visible styling) -->
                <button type="submit" 
                        style="background-color: #10b981 !important; color: #ffffff !important;"
                        class="w-full py-3 px-4 hover:bg-emerald-600 font-bold text-sm rounded-lg transition duration-200 shadow-md text-center inline-flex items-center justify-center space-x-2">
                    <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-white" />
                    <span>{{ __('Compile & Download Dataset') }}</span>
                </button>
            </form>
        </div>
    </div>
</x-filament-panels::page>