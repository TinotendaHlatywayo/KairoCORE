@props([
    'streamName' => 'csv-import-progress',
    'message' => 'Ready to import.',
])

<div {{ $attributes->merge(['class' => 'mt-4 rounded-xl border border-gray-200 bg-white p-4']) }}>
    <div class="flex items-center gap-2 text-sm font-semibold text-gray-900">
        <span wire:loading wire:target="callMountedAction" class="inline-flex items-center gap-2 text-primary-600">
            <x-filament::loading-indicator class="h-4 w-4" />
            {{ __('Importing…') }}
        </span>
        <span wire:loading.remove wire:target="callMountedAction" class="text-gray-500">
            {{ $message }}
        </span>
    </div>

    <div wire:stream="{{ $streamName }}" class="mt-3">
        <div class="flex items-center gap-3">
            <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-200">
                <div class="h-full w-0 rounded-full bg-primary-500"></div>
            </div>
            <span class="w-16 text-right text-xs font-medium text-gray-500">{{ __('0%') }}</span>
        </div>
        <p class="mt-1 text-xs text-gray-400"></p>
    </div>
</div>
