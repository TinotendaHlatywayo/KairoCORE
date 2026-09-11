<x-filament-panels::page>
    <div class="space-y-6">
        <div class="p-4 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800 flex justify-between items-center">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ $this->record?->name }} — {{ __('Payroll Ledger & Breakdown') }}
                </h2>
                <p class="text-sm text-gray-500">
                    {{ __('Period: ') }} {{ $this->record?->start_date?->format('Y-m-d') ?? $this->record?->start_date }} {{ __('to') }} {{ $this->record?->end_date?->format('Y-m-d') ?? $this->record?->end_date }}
                </p>
            </div>
            <div>
                <span class="px-3.5 py-1 text-xs font-semibold rounded-full bg-primary-100 text-primary-800 uppercase">
                    {{ $this->record?->status }}
                </span>
            </div>
        </div>

        <!-- 5 Summary Cards forced into the exact same horizontal row with responsive horizontal scrolling -->
        <div class="flex flex-nowrap gap-4 overflow-x-auto pb-2 w-full">
            <div class="flex-1 min-w-[220px] p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800 flex flex-col justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Base Salary') }}</span>
                <span class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white mt-2">${{ number_format($totalBaseSalary ?? 0, 2) }}</span>
            </div>

            <div class="flex-1 min-w-[220px] p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800 flex flex-col justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total After Allowances (Gross)') }}</span>
                <span class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white mt-2">${{ number_format($totalGross ?? 0, 2) }}</span>
            </div>

            <div class="flex-1 min-w-[220px] p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800 flex flex-col justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Paid from School Account (Net)') }}</span>
                <span class="text-2xl font-bold tracking-tight text-success-600 dark:text-success-400 mt-2">${{ number_format($totalNet ?? 0, 2) }}</span>
            </div>

            <div class="flex-1 min-w-[220px] p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800 flex flex-col justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Deductions (Taxes & Loans)') }}</span>
                <span class="text-2xl font-bold tracking-tight text-danger-600 dark:text-danger-400 mt-2">${{ number_format($totalDeductions ?? 0, 2) }}</span>
            </div>

            <div class="flex-1 min-w-[220px] p-6 bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-800 flex flex-col justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Total Money Deducted Going to Loans') }}</span>
                <span class="text-2xl font-bold tracking-tight text-warning-600 dark:text-warning-400 mt-2">${{ number_format($totalLoanDeductions ?? 0, 2) }}</span>
            </div>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
