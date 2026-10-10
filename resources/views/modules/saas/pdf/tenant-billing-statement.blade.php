<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $cfg = platform_document_config();
        $currency = $statement['currency'] ?? 'USD';
        $school = $statement['school'];
        $primary = $cfg['primary_color'];
        $dark = $cfg['dark_color'];
        $light = $cfg['light_fill'];
        $watermarkColor = document_watermark_color((string) $primary, (float) ($cfg['watermark_opacity'] ?? 0.06));
    @endphp
    <meta charset="UTF-8">
    <title>Tenant Billing Statement</title>
    <style>
        body { position: relative; font-family: Arial, Helvetica, sans-serif; color: #222222; margin: 0; padding: 26px; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 22px; margin: 0; color: {{ $dark }}; letter-spacing: 1px; }
        .muted { color: #666666; }
        .header { border-bottom: 3px solid {{ $dark }}; padding-bottom: 14px; margin-bottom: 20px; }
        .right { text-align: right; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .summary td { width: 33.33%; padding: 0 6px; }
        .box { border: 1px solid #E2E8F0; border-radius: 6px; padding: 14px; }
        .box .label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #666666; font-weight: bold; }
        .box .value { font-size: 17px; font-weight: bold; margin-top: 6px; color: {{ $dark }}; }
        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; color: {{ $dark }}; margin: 22px 0 8px; border-left: 5px solid {{ $primary }}; padding-left: 10px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.data th { background: {{ $dark }}; color: #ffffff; border-bottom: 2px solid {{ $dark }}; font-weight: bold; text-align: left; padding: 9px 10px; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; }
        table.data td { border-bottom: 1px solid #E8EBEF; padding: 9px 10px; color: #444444; }
        table.data tr:nth-child(even) td { background: {{ $light }}; }
        table.data td.num, table.data th.num { text-align: right; }
        .footer { border-top: 1px solid #E2E8F0; padding-top: 14px; margin-top: 28px; font-size: 10px; color: #666666; text-align: center; line-height: 1.7; }
        .outstanding { color: {{ $primary }}; font-weight: bold; }
        .watermark { position: absolute; left: 0; right: 0; top: 33%; text-align: center; font-size: 88px; font-weight: bold; letter-spacing: 24px; color: {{ $watermarkColor }}; white-space: nowrap; }
    </style>
</head>
<body>
    @if($cfg['watermark_enabled'])
        <div class="watermark">KAIRO CORE</div>
    @endif

    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <h1>{{ strtoupper($cfg['business_name']) }}</h1>
                    <div class="muted" style="margin-top: 4px;">{{ __('Tenant Billing Statement') }}</div>
                </td>
                <td class="right muted">
                    <div><strong style="color:#444444;">{{ __('Institution:') }}</strong> {{ $school?->name ?? __('Unknown') }}</div>
                    <div><strong style="color:#444444;">{{ __('Period:') }}</strong> {{ document_date($statement['start']) }} &ndash; {{ document_date($statement['end']) }}</div>
                    <div><strong style="color:#444444;">{{ __('Generated:') }}</strong> {{ document_date(now(), 'd M Y H:i') }}</div>
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
                    <td>{{ document_date($invoice->issue_date) }}</td>
                    <td>{{ document_date($invoice->due_date) }}</td>
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
                    <td>{{ document_date($receipt->issued_at) }}</td>
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
                    <td>{{ document_date($payment->processed_at) }}</td>
                    <td class="num">${{ number_format($payment->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">{{ __('No payments in this period.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p><span class="outstanding">{{ __('Outstanding balance: $:amount :currency', ['amount' => number_format($statement['outstanding_total'] ?? 0, 2), 'currency' => $currency]) }}</span></p>
        <p>{{ $cfg['cross_border_notice'] }}</p>
        <p>{{ __('This is a system-generated statement. &copy; :year :company', ['year' => now()->year, 'company' => $cfg['business_name']]) }}</p>
    </div>
</body>
</html>