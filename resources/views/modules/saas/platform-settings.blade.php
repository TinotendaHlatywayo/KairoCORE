<x-filament-panels::page>
    <div class="space-y-6">
        @php
            // Load every font in the catalog so the font dropdown renders each
            // option name in the actual font it represents.
            $fontCatalog = method_exists($this, 'getFontCatalog') ? $this->getFontCatalog() : [];
            $fontImports = array_values(array_filter(array_column($fontCatalog, 'import')));
            $fontStylesheet = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', $fontImports) . '&display=swap';
        @endphp
        <link href="{{ $fontStylesheet }}" rel="stylesheet">
        <style>
            [role="option"] span, .fi-select-option span, .choices__item span, li span {
                font-family: inherit;
            }
        </style>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-gray-800">
                <x-filament::button type="submit" color="primary" size="md" icon="heroicon-o-check">
                    {{ __('Save System Settings') }}
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>