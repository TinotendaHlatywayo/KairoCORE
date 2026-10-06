<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end border-t border-gray-100 pt-4 dark:border-gray-800">
            <x-filament::button type="submit" color="primary" size="md" icon="heroicon-o-check">
                {{ __('Save Billing Automation') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>