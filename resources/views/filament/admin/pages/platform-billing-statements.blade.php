<x-filament-panels::page>
    <div class="space-y-8">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Billing statement') }}</h3>
                <p class="text-xs text-slate-500">{{ __('Select an institution and a date range to build a combined invoice, receipt and payment statement.') }}</p>
            </div>
            {{ $this->form }}
        </div>

        @if(empty($statement))
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center dark:border-slate-700 dark:bg-slate-900">
                <x-heroicon-o-document-text class="mx-auto h-10 w-10 text-slate-300" />
                <p class="mt-3 text-sm text-slate-500">{{ __('Choose an institution above to generate its statement.') }}</p>
            </div>
        @else
            @php
                $currency = $statement['currency'] ?? 'USD';
            @endphp

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $statement['school']?->name ?? __('Tenant') }}</h3>
                        <p class="text-xs text-slate-500">{{ $statement['start']->format('M d, Y') }} &ndash; {{ $statement['end']->format('M d, Y') }} &middot; {{ $currency }}</p>
                    </div>
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400">
                        {{ __('Outstanding: $:amount', ['amount' => number_format($statement['outstanding_total'] ?? 0, 2)]) }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Invoiced') }}</span>
                    <h3 class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white">${{ number_format($statement['invoiced_total'] ?? 0, 2) }}</h3>
                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ __(':count invoice(s)', ['count' => $statement['invoices']->count()]) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Receipts Issued') }}</span>
                    <h3 class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white">${{ number_format($statement['receipts_total'] ?? 0, 2) }}</h3>
                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ __(':count receipt(s)', ['count' => $statement['receipts']->count()]) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ __('Payments Received') }}</span>
                    <h3 class="mt-2 text-2xl font-extrabold text-emerald-600 dark:text-emerald-400">${{ number_format($statement['payments_total'] ?? 0, 2) }}</h3>
                    <p class="mt-1 text-xs font-semibold text-slate-500">{{ __(':count payment(s)', ['count' => $statement['payments']->count()]) }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Invoices') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:bg-slate-950">
                            <tr>
                                <th class="px-6 py-3.5">{{ __('Invoice #') }}</th>
                                <th class="px-6 py-3.5">{{ __('Issued') }}</th>
                                <th class="px-6 py-3.5">{{ __('Due') }}</th>
                                <th class="px-6 py-3.5">{{ __('Status') }}</th>
                                <th class="px-6 py-3.5 text-right">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600 dark:divide-slate-800 dark:text-slate-400">
                            @forelse($statement['invoices'] as $invoice)
                                <tr>
                                    <td class="px-6 py-3.5 font-bold text-slate-900 dark:text-white">{{ $invoice->invoice_number }}</td>
                                    <td class="px-6 py-3.5">{{ $invoice->issue_date?->format('M d, Y') }}</td>
                                    <td class="px-6 py-3.5">{{ $invoice->due_date?->format('M d, Y') }}</td>
                                    <td class="px-6 py-3.5 uppercase text-[10px] font-bold">{{ $invoice->status }}</td>
                                    <td class="px-6 py-3.5 text-right font-bold">${{ number_format($invoice->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-6 text-center text-slate-400">{{ __('No invoices in this period.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Receipts') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:bg-slate-950">
                            <tr>
                                <th class="px-6 py-3.5">{{ __('Receipt #') }}</th>
                                <th class="px-6 py-3.5">{{ __('Invoice #') }}</th>
                                <th class="px-6 py-3.5">{{ __('Issued') }}</th>
                                <th class="px-6 py-3.5 text-right">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600 dark:divide-slate-800 dark:text-slate-400">
                            @forelse($statement['receipts'] as $receipt)
                                <tr>
                                    <td class="px-6 py-3.5 font-bold text-slate-900 dark:text-white">{{ $receipt->receipt_number }}</td>
                                    <td class="px-6 py-3.5">{{ $receipt->invoice?->invoice_number ?? '—' }}</td>
                                    <td class="px-6 py-3.5">{{ $receipt->issued_at?->format('M d, Y H:i') }}</td>
                                    <td class="px-6 py-3.5 text-right font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($receipt->amount_paid, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-6 text-center text-slate-400">{{ __('No receipts in this period.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Payments') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left text-xs">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:bg-slate-950">
                            <tr>
                                <th class="px-6 py-3.5">{{ __('Reference') }}</th>
                                <th class="px-6 py-3.5">{{ __('Gateway') }}</th>
                                <th class="px-6 py-3.5">{{ __('Processed') }}</th>
                                <th class="px-6 py-3.5 text-right">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600 dark:divide-slate-800 dark:text-slate-400">
                            @forelse($statement['payments'] as $payment)
                                <tr>
                                    <td class="px-6 py-3.5 font-bold text-slate-900 dark:text-white">{{ $payment->transaction_reference ?: $payment->uuid }}</td>
                                    <td class="px-6 py-3.5 uppercase text-[10px] font-bold">{{ $payment->payment_gateway_key }}</td>
                                    <td class="px-6 py-3.5">{{ $payment->processed_at?->format('M d, Y H:i') }}</td>
                                    <td class="px-6 py-3.5 text-right font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($payment->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-6 py-6 text-center text-slate-400">{{ __('No payments in this period.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
