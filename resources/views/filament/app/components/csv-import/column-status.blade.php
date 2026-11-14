@props([
    'matched' => false,
    'fileHeaders' => [],
    'missingColumns' => [],
])

<div class="rounded-xl border p-4 {{ $matched ? 'border-success-200 bg-success-50 dark:border-success-500/30 dark:bg-success-500/10' : 'border-warning-200 bg-warning-50 dark:border-warning-500/30 dark:bg-warning-500/10' }}">
    <div class="flex items-start gap-2">
        @if ($matched)
            <x-heroicon-o-check-circle class="h-5 w-5 shrink-0 text-success-600 dark:text-success-400" />
            <div>
                <p class="text-sm font-semibold text-success-700 dark:text-success-300">{{ __('Columns matched automatically') }}</p>
                <p class="mt-0.5 text-xs text-success-600 dark:text-success-400">
                    {{ __('Your file columns were mapped automatically. Continue to the import step when you are ready.') }}
                </p>
            </div>
        @else
            <x-heroicon-o-exclamation-triangle class="h-5 w-5 shrink-0 text-warning-600 dark:text-warning-400" />
            <div>
                <p class="text-sm font-semibold text-warning-700 dark:text-warning-300">{{ __('Column mismatch detected') }}</p>
                <p class="mt-0.5 text-xs text-warning-600 dark:text-warning-400">
                    {{ __('Download the template and use its exact column names, then re-upload your file.') }}
                </p>
                @if (filled($missingColumns))
                    <p class="mt-2 text-xs font-semibold text-warning-700 dark:text-warning-300">{{ __('Missing required columns') }}</p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-4 text-xs text-warning-700 dark:text-warning-300">
                        @foreach ($missingColumns as $column)
                            <li>{{ $column }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </div>

    @if (filled($fileHeaders))
        <p class="mt-3 border-t border-black/5 pt-2 text-[11px] text-gray-600 dark:border-white/10 dark:text-gray-400">
            <span class="font-semibold">{{ __('Your columns') }}:</span>
            {{ implode(', ', $fileHeaders) }}
        </p>
    @endif
</div>
