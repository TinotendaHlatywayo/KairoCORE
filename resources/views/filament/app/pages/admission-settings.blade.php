<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Admissions pipeline overview strip --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center justify-center h-10 w-10 rounded-xl text-emerald-600 bg-emerald-50 dark:text-emerald-400 dark:bg-emerald-500/10">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                </span>
                <div>
                    <h2 class="text-base font-bold text-gray-900 dark:text-gray-100">{{ __('Admission Pipeline Settings') }}</h2>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('Configure contact details, email alerts, document guidelines and the admission confirmation email.') }}
                    </p>
                </div>
            </div>
        </div>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-gray-800">
                <x-filament::button type="submit" color="primary" size="md" icon="heroicon-o-check">
                    {{ __('Save Admission Settings') }}
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
