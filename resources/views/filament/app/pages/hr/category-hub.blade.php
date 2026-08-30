<x-filament-panels::page>
    <div class="flex items-center justify-center py-24">
        <div class="text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                @svg('heroicon-o-arrow-path', 'h-6 w-6')
            </div>
            <div class="text-sm text-gray-500">{{ __('Opening category…') }}</div>
            @if (! empty($categoryLabel))
                <div class="mt-1 text-base font-medium text-gray-900">{{ $categoryLabel }}</div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
