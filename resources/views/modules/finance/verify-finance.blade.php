<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Verify Finance Document - SchoolCore') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">

    <div class="max-w-lg w-full bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden">
        <!-- Secure Header -->
        <div class="bg-emerald-600 p-6 text-center text-white">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 text-emerald-800 mb-3">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold tracking-wide">{{ __('VERIFIED GENUINE') }}</h2>
            <p class="text-emerald-100 text-xs mt-1">
                @if($type === 'receipt')
                    {{ __('Authentic Payment Receipt Secured by SchoolCore') }}
                @elseif($type === 'statement')
                    {{ __('Authentic Statement of Account Secured by SchoolCore') }}
                @else
                    {{ __('Authentic Invoice Secured by SchoolCore') }}
                @endif
            </p>
        </div>

        <!-- School Identity -->
        <div class="p-6 border-b border-slate-100">
            <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('School Name') }}</span>
            <span class="text-sm font-bold text-slate-800">{{ $school->name }}</span>
        </div>

        <!-- Student Metadata -->
        <div class="p-6 space-y-4 border-b border-slate-100">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('Student') }}</span>
                    <span class="text-sm font-semibold text-slate-700">{{ $student->full_name }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('Admission No.') }}</span>
                    <span class="text-sm font-mono text-slate-700">{{ $student->admission_number }}</span>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('Class / Form') }}</span>
                    <span class="text-sm font-semibold text-slate-700">{{ $student->currentEnrollment?->section?->full_name ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('Term / Period') }}</span>
                    <span class="text-sm font-semibold text-slate-700">
                        @if($invoice->term)
                            {{ ucwords(strtolower($invoice->term->name)) }} ({{ $invoice->term->academicYear?->name ?? '' }})
                        @else
                            N/A
                        @endif
                    </span>
                </div>
            </div>
        </div>

        @if($type === 'receipt' && $payment)
            <!-- Receipt Financial Details -->
            <div class="p-6 space-y-4 border-b border-slate-100">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('Receipt No.') }}</span>
                        <span class="text-sm font-bold text-slate-800">{{ $payment->receipt_number }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('Payment Date') }}</span>
                        <span class="text-sm font-semibold text-slate-700">{{ $payment->payment_date->format('d-M-Y H:i') }}</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('Payment Method') }}</span>
                        <span class="text-sm font-semibold text-slate-700">{{ strtoupper($payment->payment_method) }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('TXN Reference') }}</span>
                        <span class="text-sm font-mono text-slate-700">{{ $payment->reference_number ?? 'N/A' }}</span>
                    </div>
                </div>
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 text-center">
                    <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('Amount Paid') }}</span>
                    <span class="text-3xl font-extrabold text-emerald-700">${{ number_format($payment->amount, 2) }}</span>
                </div>
            </div>
        @elseif($type === 'statement')
            <!-- Statement Ledger -->
            <div class="p-6 space-y-3 border-b border-slate-100">
                <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('Statement Ledger') }}</span>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
                                <th class="py-1 pr-2">{{ __('Date') }}</th>
                                <th class="py-1 pr-2">{{ __('Description') }}</th>
                                <th class="py-1 pr-2 text-right">{{ __('Debit') }}</th>
                                <th class="py-1 pr-2 text-right">{{ __('Credit') }}</th>
                                <th class="py-1 text-right">{{ __('Balance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ledger as $row)
                                <tr class="border-b border-slate-50">
                                    <td class="py-1 pr-2 whitespace-nowrap">{{ \Carbon\Carbon::parse($row['date'])->format('d-M-y') }}</td>
                                    <td class="py-1 pr-2">{{ $row['type'] }}</td>
                                    <td class="py-1 pr-2 text-right">{{ $row['debit'] > 0 ? '$'.number_format($row['debit'], 2) : '-' }}</td>
                                    <td class="py-1 pr-2 text-right">{{ $row['credit'] > 0 ? '$'.number_format($row['credit'], 2) : '-' }}</td>
                                    <td class="py-1 text-right font-semibold">${{ number_format($row['running_balance'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 text-center">
                    <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('Net Outstanding Balance Due') }}</span>
                    <span class="text-3xl font-extrabold text-indigo-700">${{ number_format($current_balance, 2) }}</span>
                </div>
            </div>
        @else
            <!-- Invoice Financial Details -->
            <div class="p-6 space-y-4 border-b border-slate-100">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('Invoice No.') }}</span>
                        <span class="text-sm font-bold text-slate-800">{{ $invoice->invoice_number }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 block uppercase font-semibold">{{ __('Due Date') }}</span>
                        <span class="text-sm font-semibold text-slate-700">{{ $invoice->due_date->format('d-M-Y') }}</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-slate-400 uppercase border-b border-slate-200">
                                <th class="py-1 pr-2">{{ __('Fee Description') }}</th>
                                <th class="py-1 text-right">{{ __('Amount (USD)') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->items as $item)
                                <tr class="border-b border-slate-50">
                                    <td class="py-1 pr-2">{{ $item->name }}</td>
                                    <td class="py-1 text-right">${{ number_format($item->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="py-1 text-slate-400">No fee line items recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">{{ __('Subtotal') }}</span>
                        <span>${{ number_format($invoice->subtotal_amount, 2) }}</span>
                    </div>
                    @if($invoice->discount_amount > 0)
                        <div class="flex justify-between text-emerald-600">
                            <span>{{ __('Waiver / Scholarship') }}</span>
                            <span>-${{ number_format($invoice->discount_amount, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between font-semibold">
                        <span>{{ __('Total Invoice Due') }}</span>
                        <span>${{ number_format($invoice->total_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-emerald-600">
                        <span>{{ __('Amount Paid to Date') }}</span>
                        <span>${{ number_format($invoice->paid_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-red-600">
                        <span>{{ __('Outstanding Balance') }}</span>
                        <span>${{ number_format($invoice->balance_amount, 2) }}</span>
                    </div>
                </div>
            </div>
        @endif

        <!-- Audit Fingerprint -->
        <div class="p-6 bg-slate-50/80 space-y-4 text-center">
            <div class="text-left bg-white p-3 rounded-lg border border-slate-100">
                <span class="text-xs text-slate-400 block uppercase font-semibold mb-1">{{ __('Audit Fingerprint Hash') }}</span>
                <span class="text-[10px] font-mono text-slate-500 break-all select-all">{{ $invoice->integrity_hash }}</span>
            </div>
        </div>

        <!-- Bottom Certification Footer -->
        <div class="bg-slate-100 px-6 py-4 text-center text-xs text-slate-500">
            Document verified on {{ now()->format('d-M-Y H:i') }} · {{ $school->name }}
        </div>
    </div>

</body>
</html>