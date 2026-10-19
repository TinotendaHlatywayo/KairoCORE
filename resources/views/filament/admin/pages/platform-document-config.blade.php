<x-filament-panels::page>
    <div class="mb-6 flex flex-wrap items-end gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="min-w-[260px]">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wider text-gray-500">{{ __('Apply changes to') }}</label>
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="docScope">
                    <option value="all">{{ __('All documents (shared)') }}</option>
                    <option value="invoice">{{ __('Invoices only') }}</option>
                    <option value="receipt">{{ __('Receipts only') }}</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
        <p class="max-w-xl pb-1 text-xs text-gray-500">
            @if ($docScope === 'all')
                {{ __('Edits here update every document type. Choose Invoice or Receipt above to override the content for just that document.') }}
            @else
                {{ __('You are editing the :type override. Fields show this document\'s current values (its own override, falling back to the shared configuration).', ['type' => ucfirst($docScope)]) }}
            @endif
        </p>
    </div>

    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}
    </x-filament-panels::form>
</x-filament-panels::page>
