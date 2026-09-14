<x-filament-panels::page>
    <div class="space-y-6">

        <!-- Student selector -->
        <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
            {{ $this->form }}
        </div>

        @if(!$student)
            {{-- ============================================================ --}}
            {{--  WHOLE-SCHOOL FINANCIAL SUMMARY VIEW (no student selected) --}}
            {{-- ============================================================ --}}

            <!-- Filters -->
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800 space-y-4">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Filters') }}</h3>
                    <div class="flex items-center gap-2">
                        <x-filament::button wire:click="restoreDefaults" color="gray" size="sm">
                            {{ __('Reset') }}
                        </x-filament::button>
                        <x-filament::button wire:click="downloadBulkCsv" color="success" size="sm" icon="heroicon-o-arrow-down-tray">
                            {{ __('Download CSV') }}
                        </x-filament::button>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <!-- Year -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Year') }}</label>
                        <select wire:model.change="year_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm shadow-sm">
                            <option value="">—</option>
                            @foreach($years as $year)
                                <option value="{{ $year->id }}">{{ $year->name }} @if($year->is_active)({{ __('active') }})@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Term -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Term') }}</label>
                        <select wire:model.change="term_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm shadow-sm">
                            <option value="">—</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}">{{ ucwords(strtolower($term->name)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Level / Course -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Level') }}</label>
                        <select wire:model.change="filter_course_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm shadow-sm">
                            <option value="">{{ __('All Levels') }}</option>
                            @foreach($courses as $cid => $cname)
                                <option value="{{ $cid }}">{{ $cname }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Class / Section -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Class / Stream') }}</label>
                        <select wire:model.change="filter_section_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm shadow-sm">
                            <option value="">{{ __('All Classes') }}</option>
                            @foreach($sections as $sid => $sname)
                                <option value="{{ $sid }}">{{ $sname }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Payment Status -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Payment Status') }}</label>
                        <select wire:model.change="filter_payment_status" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm shadow-sm">
                            <option value="all">{{ __('All') }}</option>
                            <option value="paid">{{ __('Paid') }}</option>
                            <option value="unpaid">{{ __('Unpaid / Partial') }}</option>
                        </select>
                    </div>
                </div>
                <!-- Gender filter -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Gender') }}</label>
                        <select wire:model.change="filter_gender" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm shadow-sm">
                            <option value="">{{ __('All') }}</option>
                            <option value="male">{{ __('Male') }}</option>
                            <option value="female">{{ __('Female') }}</option>
                        </select>
                    </div>
                    <div class="flex items-end text-xs text-gray-400">
                        <span>{{ $count ?? 0 }} {{ __('student(s) found') }}</span>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                    <p class="text-xs text-gray-500 uppercase font-medium">{{ __('Total Billed') }}</p>
                    <p class="text-2xl font-bold text-danger-600 dark:text-danger-400 mt-1">${{ number_format($total_billed ?? 0, 2) }}</p>
                </div>
                <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                    <p class="text-xs text-gray-500 uppercase font-medium">{{ __('Total Paid') }}</p>
                    <p class="text-2xl font-bold text-success-600 dark:text-success-400 mt-1">${{ number_format($total_paid ?? 0, 2) }}</p>
                </div>
                <div class="p-5 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                    <p class="text-xs text-gray-500 uppercase font-medium">{{ __('Total Outstanding') }}</p>
                    <p class="text-2xl font-bold text-primary-600 dark:text-primary-400 mt-1">${{ number_format($total_balance ?? 0, 2) }}</p>
                </div>
            </div>

            <!-- Student Summary Table -->
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800">
                <div class="flex items-center justify-between flex-wrap gap-3 border-b pb-2 mb-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ __('Students Financial Summary') }}
                        <span class="text-sm font-normal text-gray-500 ml-2">({{ $label }})</span>
                    </h3>
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Per page') }}</label>
                        <select wire:model.change="per_page" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-xs shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            @foreach([25, 50, 100, 250, 500] as $n)
                                <option value="{{ $n }}" @selected(($per_page ?? 25) == $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @if(empty($summaries))
                    <p class="text-sm text-gray-500 py-4">{{ __('No students found matching the selected filters.') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-xs uppercase text-gray-500 border-b border-gray-100 dark:border-gray-800">
                                    <th class="py-2 pr-3">{{ __('Student') }}</th>
                                    <th class="py-2 pr-3">{{ __('Student ID') }}</th>
                                    <th class="py-2 pr-3">{{ __('Admission No.') }}</th>
                                    <th class="py-2 pr-3">{{ __('Class') }}</th>
                                    <th class="py-2 pr-3">{{ __('Gender') }}</th>
                                    <th class="py-2 pr-3 text-right">{{ __('Billed') }}</th>
                                    <th class="py-2 pr-3 text-right">{{ __('Paid') }}</th>
                                    <th class="py-2 pr-3 text-right">{{ __('Balance') }}</th>
                                    <th class="py-2 pr-3">{{ __('Status') }}</th>
                                    <th class="py-2 pr-3 text-right">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($summaries as $s)
                                    <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="py-2 pr-3 font-medium text-gray-900 dark:text-white">{{ trim($s['student']->full_name) }}</td>
                                        <td class="py-2 pr-3 text-gray-600 dark:text-gray-400 font-mono text-xs">{{ $s['student_id'] ?? '—' }}</td>
                                        <td class="py-2 pr-3 text-gray-600 dark:text-gray-400">{{ $s['admission_number'] ?? '—' }}</td>
                                        <td class="py-2 pr-3 text-gray-600 dark:text-gray-400">{{ $s['class'] }}</td>
                                        <td class="py-2 pr-3 text-gray-600 dark:text-gray-400">{{ ucfirst($s['gender'] ?? '—') }}</td>
                                        <td class="py-2 pr-3 text-right text-danger-600 dark:text-danger-400">${{ number_format($s['billed'], 2) }}</td>
                                        <td class="py-2 pr-3 text-right text-success-600 dark:text-success-400">${{ number_format($s['paid'], 2) }}</td>
                                        <td class="py-2 pr-3 text-right font-semibold {{ $s['balance'] > 0.01 ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}">
                                            ${{ number_format($s['balance'], 2) }}
                                        </td>
                                        <td class="py-2 pr-3">
                                            @if($s['status'] === 'paid')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400">{{ __('Paid') }}</span>
                                            @elseif($s['status'] === 'partial')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400">{{ __('Partial') }}</span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400">{{ __('Unpaid') }}</span>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-3 text-right">
                                            <button wire:click="selectStudent({{ $s['student']->id }})"
                                                    class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                                <x-filament::icon icon="heroicon-o-eye" class="w-4 h-4" />
                                                {{ __('View') }}
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        @else
            {{-- ============================================================ --}}
            {{--  INDIVIDUAL STUDENT LEDGER VIEW --}}
            {{-- ============================================================ --}}

            <!-- Student header -->
            <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <button wire:click="backToSchoolSummary" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" title="{{ __('Back to all students') }}">
                            <x-filament::icon icon="heroicon-o-arrow-left" class="w-5 h-5" />
                        </button>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $student->full_name }}</h3>
                    </div>
                    <p class="text-sm text-gray-500 mt-1 ml-7">
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
                        'year_id' => ($scope ?? 'active_year') !== 'full' && ($scope ?? 'active_year') !== 'term' ? $year_id : null,
                        'term_id' => ($scope ?? 'active_year') === 'term' ? $term_id : null,
                        'start' => ($scope ?? 'active_year') === 'custom' ? $start_date : null,
                        'end' => ($scope ?? 'active_year') === 'custom' ? $end_date : null,
                    ])) }}" target="_blank"
                       class="inline-flex items-center gap-1 text-sm font-medium text-white bg-primary-600 hover:bg-primary-500 rounded-lg px-3 py-1.5">
                        <x-filament::icon icon="heroicon-o-printer" class="w-4 h-4" />
                        {{ __('Download PDF') }}
                    </a>
                </div>
            </div>

            <!-- Scope Filters -->
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
                        <select wire:model.change="year_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm shadow-sm">
                            <option value="">—</option>
                            @foreach($years as $year)
                                <option value="{{ $year->id }}">{{ $year->name }} @if($year->is_active)({{ __('active') }})@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Term') }}</label>
                        <select wire:model.change="term_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm shadow-sm">
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

                    @if(($total_pages ?? 1) > 1)
                        <div class="flex items-center justify-between pt-4 mt-4 border-t border-gray-100 dark:border-gray-800">
                            <button wire:click="goToPage({{ ($current_page ?? 1) - 1 }})" @disabled(($current_page ?? 1) <= 1)
                                    class="inline-flex items-center gap-1 text-xs font-medium px-3 py-1.5 rounded-lg border {{ ($current_page ?? 1) <= 1 ? 'text-gray-300 dark:text-gray-600 border-gray-200 dark:border-gray-800 cursor-not-allowed' : 'text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                                <x-filament::icon icon="heroicon-o-chevron-left" class="w-3.5 h-3.5" />
                                {{ __('Previous') }}
                            </button>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('Page') }} {{ $current_page ?? 1 }} {{ __('of') }} {{ $total_pages ?? 1 }}
                                <span class="text-gray-400 dark:text-gray-500">({{ $count ?? 0 }} {{ __('students') }})</span>
                            </span>
                            <button wire:click="goToPage({{ ($current_page ?? 1) + 1 }})" @disabled(($current_page ?? 1) >= ($total_pages ?? 1))
                                    class="inline-flex items-center gap-1 text-xs font-medium px-3 py-1.5 rounded-lg border {{ ($current_page ?? 1) >= ($total_pages ?? 1) ? 'text-gray-300 dark:text-gray-600 border-gray-200 dark:border-gray-800 cursor-not-allowed' : 'text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800' }}">
                                {{ __('Next') }}
                                <x-filament::icon icon="heroicon-o-chevron-right" class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    @endif
                @endif
            </div>
</x-filament-panels::page>
