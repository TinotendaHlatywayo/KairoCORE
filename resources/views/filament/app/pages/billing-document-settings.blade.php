<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Configure the layout and wording that appears on your school's invoices, receipts and
                statements of account. Banking details (bank name, account number, branch code and the
                EcoCash merchant pin) are configured under
                <strong>{{ __('System Administration → System Settings → Banking &amp; Payments') }}</strong>{{ __('.') }}
            </p>
        </div>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-gray-800">
                <x-filament::button type="submit" color="primary" size="md" icon="heroicon-o-check">
                    {{ __('Save Document Settings') }}
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
