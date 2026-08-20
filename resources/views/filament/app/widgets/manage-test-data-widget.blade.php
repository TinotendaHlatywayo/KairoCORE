<x-filament-widgets::widget>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Seed Card -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm rounded-xl p-5 flex items-start justify-between relative overflow-hidden">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0 font-bold text-lg">
                    {{ __('🧪') }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">{{ __('Seed Test Data') }}</h3>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-300">{{ __('Sandbox') }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                        Choose modules (Students, HR, Inventory, Finance, Academics) or whole system to load comprehensive mock records.
                    </p>
                    <div class="mt-4">
                        {{ $this->seed }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Wipe Card -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm rounded-xl p-5 flex items-start justify-between relative overflow-hidden">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-lg bg-red-50 dark:bg-red-950/50 text-red-600 dark:text-red-400 flex items-center justify-center flex-shrink-0 font-bold text-lg">
                    {{ __('🧹') }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-bold text-gray-900 dark:text-gray-100">{{ __('Wipe Test Data') }}</h3>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">{{ __('Cleanup') }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 leading-relaxed">
                        {{ __('Selectively or fully purge mock records from specific modules or the whole system before production deployment.') }}
                    </p>
                    <div class="mt-4">
                        {{ $this->wipe }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CRUCIAL: Mounts the modal HTML layout needed to render confirmation & form popups on click -->
    <x-filament-actions::modals />
</x-filament-widgets::widget>
