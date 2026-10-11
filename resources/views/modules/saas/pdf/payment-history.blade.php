<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $cfg = platform_document_config();
        $currency = $history['currency'] ?? 'USD';
        $school = $history['school'];
        $logoUri = document_logo_data_uri();
        $watermarkUri = document_watermark_data_uri();
    @endphp
    <meta charset="UTF-8">
    <title>Payment History</title>
    @include('modules.saas.pdf.partials.styles', ['cfg' => $cfg])
</head>
<body>
    @include('modules.saas.pdf.partials.watermark', ['cfg' => $cfg, 'watermarkUri' => $watermarkUri])

    @include('modules.saas.pdf.partials.header', [
        'cfg' => $cfg,
        'logoUri' => $logoUri,
        'documentTitle' => __('Payment History'),
        'documentMeta' => [
            __('Institution:') => $school?->name ?? __('Unknown'),
            __('Generated:') => document_date($history['generated_at'], 'd M Y H:i'),
        ],
    ])

    <table class="summary cols-4">
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
                <th>{{ __('Description') }}</th>
                <th class="num">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($history['entries'] ?? [] as $entry)
                <tr>
                    <td>{{ $entry['date'] }}</td>
                    <td style="text-transform: uppercase;">{{ $entry['type'] }}</td>
                    <td>{{ $entry['reference'] }}</td>
                    <td>{{ $entry['detail'] ?? '' }}</td>
                    <td class="num">${{ number_format($entry['amount'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="color:#666666;">{{ __('No activity in this period.') }}</td></tr>
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
                    <td>{{ document_date($invoice->issue_date) }}</td>
                    <td>{{ document_date($invoice->due_date) }}</td>
                    <td style="text-transform: uppercase;">{{ $invoice->status }}</td>
                    <td class="num">${{ number_format($invoice->total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="color:#666666;">{{ __('No invoices.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('modules.saas.pdf.partials.footer', ['cfg' => $cfg])
</body>
</html>
