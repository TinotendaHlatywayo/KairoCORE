<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Configure how your school sends email. Each category (Admissions, Finance, Academic,
                Communication) has its own sender identity and can optionally use its own SMTP server.
                The sender address must be a school-specific address and can never reuse the platform
                sending account (<strong>{{ \platform_email_address() }}</strong>). All SMTP credentials are
                encrypted at rest and never logged.
            </p>
        </div>

        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end pt-4 border-t border-gray-100 dark:border-gray-800">
                <x-filament::button type="submit" color="primary" size="md" icon="heroicon-o-check">
                    {{ __('Save Email Configuration') }}
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
