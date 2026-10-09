<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment History</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #222222; margin: 0; padding: 26px; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 22px; margin: 0; color: #1F2E43; letter-spacing: 1px; }
        .muted { color: #666666; }
        .header { border-bottom: 3px solid #1F2E43; padding-bottom: 14px; margin-bottom: 20px; }
        .right { text-align: right; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .summary td { width: 25%; padding: 0 6px; }
        .box { border: 1px solid #E2E8F0; border-radius: 6px; padding: 14px; }
        .box .label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #666666; font-weight: bold; }
        .box .value { font-size: 17px; font-weight: bold; margin-top: 6px; color: #1F2E43; }
        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; color: #1F2E43; margin: 22px 0 8px; border-left: 5px solid #EF5F4D; padding-left: 10px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.data th { background: #1F2E43; color: #ffffff; border-bottom: 2px solid #1F2E43; font-weight: bold; text-align: left; padding: 9px 10px; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; }
        table.data td { border-bottom: 1px solid #E8EBEF; padding: 9px 10px; color: #444444; }
        table.data tr:nth-child(even) td { background: #F6F7F9; }
        table.data td.num, table.data th.num { text-align: right; }
        .footer { border-top: 1px solid #E2E8F0; padding-top: 14px; margin-top: 28px; font-size: 10px; color: #666666; text-align: center; line-height: 1.7; }
    </style>
</head>
<body>
    @php
        $currency = $history['currency'] ?? 'USD';
        $school = $history['school'];
    @endphp

    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <h1>{{ __('KairoCORE') }}</h1>
                    <div class="muted" style="margin-top: 4px;">{{ __('Tenant Payment History') }}</div>
                </td>
                <td class="right muted">
                    <div><strong>{{ __('Institution:') }}</strong> {{ $school?->name ?? __('Unknown') }}</div>
                    <div><strong>{{ __('Generated:') }}</strong> {{ $history['generated_at']->format('M d, Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary">
        <tr>
            <td><div class="box"><div class="label">{{ __('Invoiced') }}</div><div class="value">${{ number_format($history['invoiced_total'] ?? 0, 2) }}</div></div></td>
            <td><div class="box"><div class="label">{{ __('Receipts Issued') }}</div><div class="value">${{ number_format($history['receipts_total'] ?? 0, 2) }}</div></div></td>
            <td><div class="box"><div class="label">{{ __('Payments Received') }}</div><div class="value">${{ number_format($history['payments_total'] ?? 0, 2) }}</div></div></td>
            <td><div class="box"><div class="label">{{ __('Outstanding') }}</div><div class="value">${{ number_format($history['outstanding_total'] ?? 0, 2) }}</div></div></td>
        </tr>
    </table>

    <p class="section-title">{{ __('Payment History') }}</p>
    <table class="data">
        <thead>
            <tr>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Type') }}</th>
                <th>{{ __('Reference') }}</th>
                <th>{{ __('Details') }}</th>
                <th class="num">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($history['entries'] ?? [] as $entry)
                <tr>
                    <td>{{ $entry['date'] }}</td>
                    <td style="text-transform: uppercase; font-size: 10px; font-weight: 700;">{{ $entry['type'] }}</td>
                    <td>{{ $entry['reference'] }}</td>
                    <td>{{ $entry['detail'] }}</td>
                    <td class="num">${{ number_format($entry['amount'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">{{ __('No billing activity recorded for this institution.') }}</td></tr>
            @endforelse
        </tbody>
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
            @forelse($history['invoices'] as $invoice)
                <tr>
                    <td>{{ $invoice->invoice_number }}</td>
                    <td>{{ $invoice->issue_date?->format('M d, Y') }}</td>
                    <td>{{ $invoice->due_date?->format('M d, Y') }}</td>
                    <td style="text-transform: uppercase;">{{ $invoice->status }}</td>
                    <td class="num">${{ number_format($invoice->total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">{{ __('No invoices.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>{{ __('This is a system-generated payment history. &copy; :year Kairo CORE Software Inc.', ['year' => now()->year]) }}</p>
    </div>
</body>
</html>
