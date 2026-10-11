<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $cfg = platform_document_config();
        $currency = $statement['currency'] ?? 'USD';
        $school = $statement['school'];
        $logoUri = document_logo_data_uri();
        $watermarkUri = document_watermark_data_uri();
    @endphp
    <meta charset="UTF-8">
    <title>Tenant Billing Statement</title>
    @include('modules.saas.pdf.partials.styles', ['cfg' => $cfg])
</head>
<body>
    @include('modules.saas.pdf.partials.watermark', ['cfg' => $cfg, 'watermarkUri' => $watermarkUri])

    @include('modules.saas.pdf.partials.header', [
        'cfg' => $cfg,
        'logoUri' => $logoUri,
        'documentTitle' => __('Statement'),
        'documentMeta' => [
            __('Institution:') => $school?->name ?? __('Unknown'),
            __('Period:') => document_date($statement['start']).' – '.document_date($statement['end']),
            __('Generated:') => document_date(now(), 'd M Y H:i'),
        ],
    ])

    <table class="summary" style="margin-top: 4px;">
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
                    <td>{{ document_date($invoice->issue_date) }}</td>
                    <td>{{ document_date($invoice->due_date) }}</td>
                    <td style="text-transform: uppercase;">{{ $invoice->status }}</td>
                    <td class="num">${{ number_format($invoice->total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="color:#666666;">{{ __('No invoices in this period.') }}</td></tr>
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
                    <td>{{ document_date($receipt->issued_at) }}</td>
                    <td class="num">${{ number_format($receipt->amount_paid, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="color:#666666;">{{ __('No receipts in this period.') }}</td></tr>
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
                    <td>{{ document_date($payment->processed_at) }}</td>
                    <td class="num">${{ number_format($payment->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="color:#666666;">{{ __('No payments in this period.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="notice"><span class="outstanding">{{ __('Outstanding balance: $:amount :currency', ['amount' => number_format($statement['outstanding_total'] ?? 0, 2), 'currency' => $currency]) }}</span></p>

    @include('modules.saas.pdf.partials.footer', ['cfg' => $cfg])
</body>
</html>
