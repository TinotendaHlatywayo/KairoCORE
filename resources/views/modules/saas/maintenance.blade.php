<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}
            
            <div class="flex justify-end">
                <x-filament::button type="submit" size="md">
                    {{ __('Save Maintenance Configurations') }}
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>