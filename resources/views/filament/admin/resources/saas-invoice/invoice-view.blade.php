<x-filament-panels::page>
    <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900">
        <iframe
            src="{{ route('saas.invoice.view', $this->getRecord()->uuid) }}"
            title="{{ __('Invoice') }} {{ $this->getRecord()->invoice_number }}"
            style="width: 100%; height: 78vh; border: 0; display: block;"
        ></iframe>
    </div>
</x-filament-panels::page>
