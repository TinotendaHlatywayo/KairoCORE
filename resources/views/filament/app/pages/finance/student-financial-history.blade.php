<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Student selector -->
        <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('Select Student') }}</label>
            <div class="max-w-xl">
                <select wire:model.live="student_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">— {{ __('Select a student') }} —</option>
                    @foreach($students as $sid => $sname)
                        <option value="{{ $sid }}">{{ $sname }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($student)
            <!-- Student header -->
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $student->full_name }}</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ __('Admission:') }} <span class="font-semibold">{{ $student->admission_number }}</span>
                        <span class="mx-1 text-gray-300">|</span>
                        {{ __('Class:') }} <span class="font-semibold">{{ trim(($student->currentEnrollment?->course?->name ?? '').' '.($student->currentEnrollment?->section?->name ?? '')) ?: 'Unassigned' }}</span>
                        <span class="mx-1 text-gray-300">|</span>
                        {{ __('Enrolled:') }} <span class="font-semibold">{{ $student->admission_date?->toDateString() ?? '—' }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <x-filament::button wire:click="downloadCsv" color="success" size="sm" icon="heroicon-o-arrow-down-tray">
                        {{ __('Download CSV') }}
                    </x-filament::button>
                    <a href="{{ route('finance.student.history.pdf', ['student' => $student->id]).'?'.http_build_query(array_filter([
                        'year_id' => ($scope !== 'full' && $scope !== 'term') ? $year_id : null,
                        'term_id' => $scope === 'term' ? $term_id : null,
                        'start' => $scope === 'custom' ? $start_date : null,
                        'end' => $scope === 'custom' ? $end_date : null,
                    ])) }}" target="_blank"
                       class="inline-flex items-center gap-1 text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 rounded-lg px-3 py-1.5">
                        <x-filament::icon icon="heroicon-o-printer" class="w-4 h-4" />
                        {{ __('Download PDF') }}
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800 space-y-4">
                <div class="flex items-center gap-2 flex-wrap">
                    <x-filament::button wire:click="restoreDefaults" color="{{ ($scope ?? 'active_year') === 'active_year' ? 'primary' : 'gray' }}" size="sm">
                        {{ __('Current Active Year') }}
                    </x-filament::button>
                    <x-filament::button wire:click="$set('scope', 'term')" color="{{ ($scope ?? '') === 'term' ? 'primary' : 'gray' }}" size="sm">
                        {{ __('By Term') }}
                    </x-filament::button>
                    <x-filament::button wire:click="$set('scope', 'custom')" color="{{ ($scope ?? '') === 'custom' ? 'primary' : 'gray' }}" size="sm">
                        {{ __('Custom Date Range') }}
                    </x-filament::button>
                    <x-filament::button wire:click="$set('scope', 'full')" color="{{ ($scope ?? '') === 'full' ? 'primary' : 'gray' }}" size="sm">
                        {{ __('Whole History (From Enrolment)') }}
                    </x-filament::button>
                    <span class="text-xs text-gray-400 ml-1">{{ __('Showing:') }} <span class="font-semibold text-gray-600 dark:text-gray-200">{{ $label }}</span></span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Academic Year') }}</label>
                        <select wire:model.live="year_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm shadow-sm">
                            <option value="">—</option>
                            @foreach($years as $year)
                                <option value="{{ $year->id }}">{{ $year->name }} @if($year->is_active)({{ __('active') }})@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Term') }}</label>
                        <select wire:model.live="term_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm shadow-sm">
                            <option value="">—</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}">{{ ucwords(strtolower($term->name)) }} @if($term->academicYear)({{ $term->academicYear->name }})@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('From') }}</label>
                            <input type="date" wire:model.live="start_date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('To') }}</label>
                            <input type="date" wire:model.live="end_date" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm shadow-sm">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                    <p class="text-xs text-gray-500 uppercase font-medium">{{ __('Opening Balance') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${{ number_format($ledger['opening_balance'], 2) }}</p>
                </div>
                <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                    <p class="text-xs text-gray-500 uppercase font-medium">{{ __('Billed') }}</p>
                    <p class="text-2xl font-bold text-danger-600 dark:text-danger-400 mt-1">${{ number_format($ledger['total_billed'], 2) }}</p>
                </div>
                <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                    <p class="text-xs text-gray-500 uppercase font-medium">{{ __('Paid') }}</p>
                    <p class="text-2xl font-bold text-success-600 dark:text-success-400 mt-1">${{ number_format($ledger['total_paid'], 2) }}</p>
                </div>
                <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                    <p class="text-xs text-gray-500 uppercase font-medium">{{ __('Refunded') }}</p>
                    <p class="text-2xl font-bold text-warning-600 dark:text-warning-400 mt-1">${{ number_format($ledger['total_refunded'], 2) }}</p>
                </div>
                <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                    <p class="text-xs text-gray-500 uppercase font-medium">{{ __('Closing Balance') }}</p>
                    <p class="text-2xl font-bold text-primary-600 dark:text-primary-400 mt-1">${{ number_format($ledger['closing_balance'], 2) }}</p>
                </div>
            </div>

            <!-- Ledger table -->
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white border-b pb-2 mb-3">
                    {{ __('Financial History') }} <span class="text-sm font-normal text-gray-500">({{ $label }})</span>
                </h3>
                @if(empty($ledger['rows']))
                    <p class="text-sm text-gray-500 py-4">{{ __('No transactions recorded for this student in the selected period.') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase text-gray-500 border-b border-gray-100 dark:border-gray-800">
                                    <th class="py-2 pr-3">{{ __('Date') }}</th>
                                    <th class="py-2 pr-3">{{ __('Description') }}</th>
                                    <th class="py-2 pr-3">{{ __('Receipt / Reference') }}</th>
                                    <th class="py-2 pr-3">{{ __('Method') }}</th>
                                    <th class="py-2 pr-3 text-right">{{ __('Debit (+)') }}</th>
                                    <th class="py-2 pr-3 text-right">{{ __('Credit (-)') }}</th>
                                    <th class="py-2 pr-3 text-right">{{ __('Balance ($)') }}</th>
                                    <th class="py-2 pr-3">{{ __('Received By') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ledger['rows'] as $row)
                                    @php
                                        $isOpening = ! empty($row['is_opening']);
                                        $isPayment = ! empty($row['type']) && in_array($row['type'], ['payment', 'credit'], true);
                                        $isRefund = ! empty($row['type']) && $row['type'] === 'refund';
                                        $isWaiver = ! empty($row['type']) && $row['type'] === 'waiver';
                                    @endphp
                                    <tr @class(['border-b border-gray-100 dark:border-gray-800', 'bg-gray-50 dark:bg-gray-800/50' => $isOpening])>
                                        <td class="py-2 pr-3 text-gray-600 dark:text-gray-400">
                                            {{ $row['date'] instanceof \Carbon\Carbon ? $row['date']->format('d M Y') : '-' }}
                                        </td>
                                        <td class="py-2 pr-3 font-medium text-gray-900 dark:text-white">
                                            {{ $row['description'] }}
                                            @if($isRefund)<span class="ml-1 inline-flex text-[10px] uppercase font-bold text-danger-600 bg-danger-50 dark:bg-danger-900/30 rounded px-1.5 py-0.5">{{ __('Refund') }}</span>@endif
                                        </td>
                                        <td class="py-2 pr-3 text-gray-600 dark:text-gray-400">
                                            {{ $row['receipt'] ? $row['receipt'].($row['reference'] ? ' / '.$row['reference'] : '') : ($row['reference'] ?? '') }}
                                        </td>
                                        <td class="py-2 pr-3 text-gray-600 dark:text-gray-400">
                                            {{ $isOpening ? '—' : ($row['method'] ?? ucfirst($row['type'] ?? '')) }}
                                        </td>
                                        <td class="py-2 pr-3 text-right text-danger-600 dark:text-danger-400">
                                            {{ $row['debit'] > 0 ? '$'.number_format($row['debit'], 2) : '-' }}
                                        </td>
                                        <td class="py-2 pr-3 text-right text-success-600 dark:text-success-400">
                                            {{ $row['credit'] > 0 ? '$'.number_format($row['credit'], 2) : ($row['credit'] < 0 ? '($'.number_format(abs($row['credit']), 2).')' : '-') }}
                                        </td>
                                        <td class="py-2 pr-3 text-right font-semibold text-gray-900 dark:text-white">
                                            ${{ number_format($row['running_balance'], 2) }}
                                        </td>
                                        <td class="py-2 pr-3 text-gray-600 dark:text-gray-400">
                                            @if($isPayment && $row['received_by'])
                                                {{ $row['received_by'] }}
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @else
            <div class="p-10 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800 text-center text-sm text-gray-500 dark:text-gray-400">
                {{ __('Select a student above to view their complete financial history.') }}
            </div>
        @endif
    </div>
</x-filament-panels::page>