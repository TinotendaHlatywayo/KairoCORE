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

        @if(($creditStudents ?? collect())->isNotEmpty())
            <div class="sc-hub-hero mt-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="sc-hub-hero-title">{{ __('Carried Forward Student Credits') }}</div>
                        <div class="sc-hub-hero-desc">
                            {{ __('Overpayments parents chose to keep instead of refunding — applied automatically to the next term\'s invoices. Total: $') }}{{ number_format($totalCredits ?? 0, 2) }}
                        </div>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-gray-800">
                                <th class="py-2 pr-4 font-semibold">{{ __('Student') }}</th>
                                <th class="py-2 pr-4 font-semibold">{{ __('Class') }}</th>
                                <th class="py-2 pr-4 font-semibold">{{ __('Stream') }}</th>
                                <th class="py-2 font-semibold text-right">{{ __('Credit Balance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($creditStudents as $cs)
                                <tr class="border-b border-gray-100 dark:border-gray-800/60">
                                    <td class="py-2 pr-4 font-medium text-gray-900 dark:text-white">{{ $cs->full_name ?? ($cs->first_name.' '.$cs->last_name) }}</td>
                                    <td class="py-2 pr-4 text-gray-600 dark:text-gray-400">{{ $cs->currentEnrollment?->course?->name ?? '—' }}</td>
                                    <td class="py-2 pr-4 text-gray-600 dark:text-gray-400">{{ $cs->currentEnrollment?->section?->name ?? '—' }}</td>
                                    <td class="py-2 text-right font-semibold text-indigo-600 dark:text-indigo-400">${{ number_format((float) $cs->credit_balance, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
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
