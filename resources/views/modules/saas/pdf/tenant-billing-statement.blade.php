<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tenant Billing Statement</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1e293b; margin: 0; padding: 24px; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 20px; margin: 0; color: #4f46e5; }
        .muted { color: #64748b; }
        .header { border-bottom: 3px solid #4f46e5; padding-bottom: 14px; margin-bottom: 20px; }
        .right { text-align: right; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .summary td { width: 33.33%; padding: 0 6px; }
        .box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; }
        .box .label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; font-weight: 700; }
        .box .value { font-size: 17px; font-weight: bold; margin-top: 6px; color: #0f172a; }
        .section-title { font-size: 13px; font-weight: 700; color: #0f172a; margin: 22px 0 8px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.data th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569; font-weight: 700; text-align: left; padding: 8px 10px; font-size: 10px; text-transform: uppercase; }
        table.data td { border-bottom: 1px solid #e2e8f0; padding: 8px 10px; color: #334155; }
        table.data td.num, table.data th.num { text-align: right; }
        .footer { border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 30px; font-size: 10px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    @php
        $currency = $statement['currency'] ?? 'USD';
        $school = $statement['school'];
    @endphp

    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <h1>{{ __('KairoCORE') }}</h1>
                    <div class="muted" style="margin-top: 4px;">{{ __('Tenant Billing Statement') }}</div>
                </td>
                <td class="right muted">
                    <div><strong>{{ __('Institution:') }}</strong> {{ $school?->name ?? __('Unknown') }}</div>
                    <div><strong>{{ __('Period:') }}</strong> {{ $statement['start']->format('M d, Y') }} &ndash; {{ $statement['end']->format('M d, Y') }}</div>
                    <div><strong>{{ __('Generated:') }}</strong> {{ now()->format('M d, Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary">
        <tr>
            <td><div class="box"><div class="label">{{ __('Invoiced') }}</div><div class="value">${{ number_format($statement['invoiced_total'] ?? 0, 2) }}</div></div></td>
            <td><div class="box"><div class="label">{{ __('Receipts Issued') }}</div><div class="value">${{ number_format($statement['receipts_total'] ?? 0, 2) }}</div></div></td>
            <td><div class="box"><div class="label">{{ __('Payments Received') }}</div><div class="value">${{ number_format($statement['payments_total'] ?? 0, 2) }}</div></div></td>
        </tr>
    </table>

    <p class="section-title">{{ __('Invoices') }}</p>
    <table class="data">
        <thead>
            <tr>
                <th>{{ __('Invoice #') }}</th>
                <th>{{ __('Issued') }}</th>
                <th>{{ __('Due') }}</th>
                <th>{{ __('Status') }}</th>
                <th class="num">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($statement['invoices'] as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->issue_date?->format('M d, Y') }}</td>
                    <td>{{ $invoice->due_date?->format('M d, Y') }}</td>
                    <td style="text-transform: uppercase;">{{ $invoice->status }}</td>
                    <td class="num">${{ number_format($invoice->total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">{{ __('No invoices in this period.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="section-title">{{ __('Receipts') }}</p>
    <table class="data">
        <thead>
            <tr>
                <th>{{ __('Receipt #') }}</th>
                <th>{{ __('Invoice #') }}</th>
                <th>{{ __('Issued') }}</th>
                <th class="num">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($statement['receipts'] as $receipt)
                <tr>
                    <td>{{ $receipt->receipt_number }}</td>
                    <td>{{ $receipt->invoice?->invoice_number ?? '—' }}</td>
                    <td>{{ $receipt->issued_at?->format('M d, Y') }}</td>
                    <td class="num">${{ number_format($receipt->amount_paid, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">{{ __('No receipts in this period.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="section-title">{{ __('Payments') }}</p>
    <table class="data">
        <thead>
            <tr>
                <th>{{ __('Reference') }}</th>
                <th>{{ __('Gateway') }}</th>
                <th>{{ __('Processed') }}</th>
                <th class="num">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($statement['payments'] as $payment)
                <tr>
                    <td>{{ $payment->transaction_reference ?: $payment->uuid }}</td>
                    <td style="text-transform: uppercase;">{{ $payment->payment_gateway_key }}</td>
                    <td>{{ $payment->processed_at?->format('M d, Y') }}</td>
                    <td class="num">${{ number_format($payment->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">{{ __('No payments in this period.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>{{ __('Outstanding balance: $:amount :currency', ['amount' => number_format($statement['outstanding_total'] ?? 0, 2), 'currency' => $currency]) }}</p>
        <p>{{ __('This is a system-generated statement. &copy; :year Kairo CORE Software Inc.', ['year' => now()->year]) }}</p>
    </div>
</body>
</html>
