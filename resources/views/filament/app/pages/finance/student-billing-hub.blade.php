<x-filament-panels::page>
    <div class="space-y-6">
        <style>[x-cloak] { display: none !important; }</style>

        <div class="sc-hub-hero">
            <div class="sc-hub-hero-title">{{ $categoryLabel }}</div>
            <div class="sc-hub-hero-desc">{{ __('Manage fee structures, categories, invoices, payment proofs and fee waivers.') }}</div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($categoryPages as $tab)
                <a
                    href="{{ url($tab['url']) }}"
                    class="sc-hub-card"
                >
                    <div class="sc-hub-card-icon">
                        @svg($tab['icon'] ?? 'heroicon-o-banknotes', 'h-6 w-6')
                    </div>
                    <div>
                        <div class="sc-hub-card-title">{{ $tab['label'] }}</div>
                        <div class="sc-hub-card-link">{{ __('Open') }} &rarr;</div>
                    </div>
                </a>
            @empty
                <div class="text-sm text-gray-500">{{ __('No pages available in this category.') }}</div>
            @endforelse
        </div>
    </div>

    <style>
        .sc-hub-hero {
            @apply rounded-2xl border border-gray-200 bg-white p-6 shadow-sm;
        }
        .sc-hub-hero-title {
            @apply text-xl font-semibold text-gray-900;
        }
        .sc-hub-hero-desc {
            @apply mt-1 text-sm text-gray-500;
        }
        .sc-hub-card {
            @apply flex items-start gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow-md;
        }
        .sc-hub-card-icon {
            @apply flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600;
        }
        .sc-hub-card-title {
            @apply font-medium text-gray-900;
        }
        .sc-hub-card-link {
            @apply mt-1 text-sm font-medium text-indigo-600;
        }
    </style>
</x-filament-panels::page>
