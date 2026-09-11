@props([
    'matched' => false,
    'fileHeaders' => [],
])

<div class="mt-4 rounded-xl border p-4 {{ $matched ? 'border-success-200 bg-success-50' : 'border-warning-200 bg-warning-50' }}">
    <div class="flex items-start gap-2">
        @if ($matched)
            <x-heroicon-o-check-circle class="h-5 w-5 shrink-0 text-success-600" />
            <div>
                <p class="text-sm font-semibold text-success-700">{{ __('Columns matched automatically') }}</p>
                <p class="mt-0.5 text-xs text-success-600">
                    @if (filled($fileHeaders))
                        {{ __('No column matching needed — your file columns were mapped automatically. Click "Import" to begin.') }}
                    @else
                        {{ __('Upload your CSV file to begin the import.') }}
                    @endif
                </p>
            </div>
        @else
            <x-heroicon-o-exclamation-triangle class="h-5 w-5 shrink-0 text-warning-600" />
            <div>
                <p class="text-sm font-semibold text-warning-700">{{ __('Column mismatch detected') }}</p>
                <p class="mt-0.5 text-xs text-warning-600">
                    {{ __('One or more of your file columns could not be matched automatically. Continue to the "Match Columns" step to map them, or replace your file with the template.') }}
                </p>
            </div>
        @endif
    </div>
</div>