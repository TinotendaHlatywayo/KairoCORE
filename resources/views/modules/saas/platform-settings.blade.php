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
                <x-filament::button type="submit" color="primary" size="md" icon="heroicon-o-check" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">{{ $this->getSaveButtonLabel() }}</span>
                    <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                        <x-filament::loading-indicator class="h-4 w-4" />
                        {{ __('Saving…') }}
                    </span>
                </x-filament::button>
            </div>
        </form>
    </div>

    @script
    <script>
        const WEBSITE_MEDIA_MAX_MB = 50;

        const describeWebsiteMediaSize = (bytes) => (bytes / (1024 * 1024)).toFixed(1) + ' MB';

        const notifyWebsiteMediaRejected = (title, body) => {
            $wire.dispatch('websiteMediaRejected', { title, body });
        };

        const rejectWebsiteMediaInput = (input) => {
            input.value = '';
        };

        // Guard rails that fire before or during the Livewire upload, where
        // the server cannot yet explain itself. Duration is validated on save
        // (trim-aware) — see MaxVideoDuration — so we only pre-check size and
        // surface a useful reason when the transport itself rejects the file.
        const checkWebsiteVideoSize = (file, input) => {
            if (!file) {
                return true;
            }

            if (file.size > WEBSITE_MEDIA_MAX_MB * 1024 * 1024) {
                rejectWebsiteMediaInput(input);
                notifyWebsiteMediaRejected(
                    'Video too large',
                    file.name + ' is ' + describeWebsiteMediaSize(file.size) + '. Videos may be at most ' + WEBSITE_MEDIA_MAX_MB + ' MB — compress it or upload a shorter clip.'
                );
                return false;
            }

            return true;
        };

        document.addEventListener('change', (event) => {
            const input = event.target;
            if (!input || input.type !== 'file' || !input.files || input.files.length === 0) {
                return;
            }

            for (const file of input.files) {
                if (!checkWebsiteVideoSize(file, input)) {
                    break;
                }
            }
        }, true);

        document.addEventListener('livewire-upload-error', (event) => {
            const detail = event.detail && event.detail.content ? String(event.detail.content) : '';
            const serverLimit = Number(window.KAIRO_UPLOAD_MAX_MB) || 0;

            notifyWebsiteMediaRejected(
                'Upload rejected',
                'The file could not be uploaded' + (detail ? ' (' + detail + ')' : '') +
                    (serverLimit
                        ? ' — this site accepts files up to ' + serverLimit + ' MB (the limit PHP, Livewire and the CDN in front of them will each accept). '
                        : ' — it was larger than this site accepts, or the connection dropped before it finished. ') +
                    'Try a smaller or compressed file' + (serverLimit ? ' — under ' + serverLimit + ' MB' : '') + '.'
            );
        });
    </script>
    @endscript
</x-filament-panels::page>