<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header & Range Controls -->
        <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800 flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ __('Fee Collections') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ __('Fees actually collected') }} <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $label }}</span>
                </p>
                <p class="text-xs text-gray-400 mt-1">
                    {{ __('Period:') }} <span class="font-semibold">{{ $start }}</span> {{ __('to') }} <span class="font-semibold">{{ $end }}</span>
                </p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <x-filament::button wire:click="$set('range', 'today')" color="{{ ($range ?? 'today') === 'today' ? 'primary' : 'gray' }}" size="sm">
                    {{ __('Today') }}
                </x-filament::button>
                <x-filament::button wire:click="$set('range', 'week')" color="{{ ($range ?? 'today') === 'week' ? 'primary' : 'gray' }}" size="sm">
                    {{ __('Last 7 Days') }}
                </x-filament::button>
                <x-filament::button wire:click="$set('range', 'month')" color="{{ ($range ?? 'today') === 'month' ? 'primary' : 'gray' }}" size="sm">
                    {{ __('Last 30 Days') }}
                </x-filament::button>
                <x-filament::button wire:click="$set('range', 'custom')" color="{{ ($range ?? 'today') === 'custom' ? 'primary' : 'gray' }}" size="sm">
                    {{ __('Custom Range') }}
                </x-filament::button>
                <x-filament::button wire:click="downloadExcel" color="success" size="sm" icon="heroicon-o-arrow-down-tray">
                    {{ __('Download Excel') }}
                </x-filament::button>
                <x-filament::button wire:click="downloadCsv" color="gray" size="sm" icon="heroicon-o-arrow-down-tray">
                    {{ __('Download CSV') }}
                </x-filament::button>
            </div>
        </div>

        @if(($range ?? '') === 'custom')
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Start Date') }}</label>
                    <input type="date" wire:model.live="start_date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('End Date') }}</label>
                    <input type="date" wire:model.live="end_date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm shadow-sm">
                </div>
            </div>
        @endif

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                <p class="text-xs text-gray-500 uppercase font-medium">{{ __('Total Collected') }}</p>
                <p class="text-2xl font-bold text-success-600 dark:text-success-400 mt-1">${{ number_format($total ?? 0, 2) }}</p>
            </div>
            <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                <p class="text-xs text-gray-500 uppercase font-medium">{{ __('Deposits') }}</p>
                <p class="text-2xl font-bold text-primary-600 dark:text-primary-400 mt-1">{{ number_format($payments_count ?? 0) }}</p>
            </div>
            <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                <p class="text-xs text-gray-500 uppercase font-medium">{{ __('Paying Students') }}</p>
                <p class="text-2xl font-bold text-primary-600 dark:text-primary-400 mt-1">{{ number_format($student_count ?? 0) }}</p>
            </div>
            <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                <p class="text-xs text-gray-500 uppercase font-medium">{{ __('Refunds') }}</p>
                <p class="text-2xl font-bold text-danger-600 dark:text-danger-400 mt-1">-${{ number_format($refunds ?? 0, 2) }}</p>
            </div>
        </div>

        <!-- Collected by Student -->
        <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white border-b pb-2 mb-3">
                {{ __('Collected by Student') }}
            </h3>
            @if(($students ?? collect())->isEmpty())
                <p class="text-sm text-gray-500 py-4">{{ __('No fee collections recorded in this period.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase text-gray-500 border-b border-gray-100 dark:border-gray-800">
                                <th class="py-2 pr-3">{{ __('Student') }}</th>
                                <th class="py-2 pr-3">{{ __('Admission No.') }}</th>
                                <th class="py-2 pr-3">{{ __('Class') }}</th>
                                <th class="py-2 pr-3 text-right">{{ __('Total Paid') }}</th>
                                <th class="py-2 pr-3 text-right">{{ __('Payments') }}</th>
                                <th class="py-2 pr-3 text-right">{{ __('Last Paid') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $entry)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-2 pr-3 font-medium text-gray-900 dark:text-white">{{ $entry->student?->full_name ?? __('Unknown Student') }}</td>
                                    <td class="py-2 pr-3 text-gray-600 dark:text-gray-400">{{ $entry->student?->admission_number ?? '—' }}</td>
                                    <td class="py-2 pr-3 text-gray-600 dark:text-gray-400">
                                        {{ trim(($entry->student?->currentEnrollment?->course?->name ?? '').' '.($entry->student?->currentEnrollment?->section?->name ?? '')) ?: 'Unassigned' }}
                                    </td>
                                    <td class="py-2 pr-3 text-right font-semibold text-success-600 dark:text-success-400">
                                        ${{ number_format((float) $entry->total, 2) }}
                                    </td>
                                    <td class="py-2 pr-3 text-right text-gray-600 dark:text-gray-400">{{ $entry->count }}</td>
                                    <td class="py-2 pr-3 text-right text-gray-600 dark:text-gray-400">{{ $entry->last_date }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Individual Transactions -->
        <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white border-b pb-2 mb-3">
                {{ __('Individual Transactions') }}
            </h3>
            @if(($payments ?? collect())->isEmpty())
                <p class="text-sm text-gray-500 py-4">{{ __('No deposits recorded in this period.') }}</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase text-gray-500 border-b border-gray-100 dark:border-gray-800">
                                <th class="py-2 pr-3">{{ __('Date') }}</th>
                                <th class="py-2 pr-3">{{ __('Student') }}</th>
                                <th class="py-2 pr-3">{{ __('Admission No.') }}</th>
                                <th class="py-2 pr-3 text-right">{{ __('Amount') }}</th>
                                <th class="py-2 pr-3">{{ __('Method') }}</th>
                                <th class="py-2 pr-3">{{ __('Receipt No.') }}</th>
                                <th class="py-2 pr-3">{{ __('Reference') }}</th>
                                <th class="py-2 pr-3">{{ __('Received By') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $payment)
                                <tr class="border-b border-gray-100 dark:border-gray-800">
                                    <td class="py-2 pr-3 text-gray-600 dark:text-gray-400">{{ $payment->payment_date?->toDateString() }}</td>
                                    <td class="py-2 pr-3 font-medium text-gray-900 dark:text-white">{{ $payment->invoice?->student?->full_name ?? __('Unknown Student') }}</td>
                                    <td class="py-2 pr-3 text-gray-600 dark:text-gray-400">{{ $payment->invoice?->student?->admission_number ?? '—' }}</td>
                                    <td class="py-2 pr-3 text-right font-semibold text-success-600 dark:text-success-400">${{ number_format((float) $payment->amount, 2) }}</td>
                                    <td class="py-2 pr-3 text-gray-600 dark:text-gray-400">{{ $payment->payment_method }}</td>
                                    <td class="py-2 pr-3 text-gray-600 dark:text-gray-400">{{ $payment->receipt_number }}</td>
                                    <td class="py-2 pr-3 text-gray-600 dark:text-gray-400">{{ $payment->reference_number }}</td>
                                    <td class="py-2 pr-3 text-gray-600 dark:text-gray-400">{{ $payment->receivedBy?->name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>