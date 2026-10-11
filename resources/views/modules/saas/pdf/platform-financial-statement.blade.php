<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $cfg = platform_document_config();
        $currency = $report['currency'] ?? 'USD';
        $net = $report['net_profit'] ?? 0;
        $logoUri = document_logo_data_uri();
        $watermarkUri = document_watermark_data_uri();
    @endphp
    <meta charset="UTF-8">
    <title>KairoCORE Financial Statement</title>
    @include('modules.saas.pdf.partials.styles', ['cfg' => $cfg])
</head>
<body>
    @include('modules.saas.pdf.partials.watermark', ['cfg' => $cfg, 'watermarkUri' => $watermarkUri])

    @include('modules.saas.pdf.partials.header', [
        'cfg' => $cfg,
        'logoUri' => $logoUri,
        'documentTitle' => __('Financial Statement'),
        'documentMeta' => [
            __('Period:') => document_date($report['start']).' – '.document_date($report['end']),
            __('Generated:') => document_date(now(), 'd M Y H:i'),
            __('Currency:') => $currency,
        ],
    ])

    <table class="summary cols-4">
        <tr>
            <td><div class="box"><div class="label">{{ __('Revenue') }}</div><div class="value">${{ number_format($report['revenue'] ?? 0, 2) }}</div></div></td>
            <td><div class="box"><div class="label">{{ __('Operating Expenses') }}</div><div class="value">${{ number_format($report['expenses'] ?? 0, 2) }}</div></div></td>
            <td><div class="box"><div class="label">{{ __('Net Profit') }}</div><div class="value {{ $net < 0 ? 'outstanding' : '' }}">${{ number_format($net, 2) }}</div></div></td>
            <td><div class="box"><div class="label">{{ __('Outstanding') }}</div><div class="value">${{ number_format($report['outstanding_total'] ?? 0, 2) }}</div></div></td>
        </tr>
    </table>

    <p class="section-title">{{ __('Monthly Breakdown') }}</p>
    <table class="data">
        <thead>
            <tr>
                <th>{{ __('Month') }}</th>
                <th class="num">{{ __('Revenue') }}</th>
                <th class="num">{{ __('Expenses') }}</th>
                <th class="num">{{ __('Net') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['months'] ?? [] as $month)
                <tr>
                    <td>{{ $month['label'] }}</td>
                    <td class="num pos">${{ number_format($month['revenue'], 2) }}</td>
                    <td class="num neg">${{ number_format($month['expenses'], 2) }}</td>
                    <td class="num {{ $month['net'] < 0 ? 'neg' : '' }}"><strong>${{ number_format($month['net'], 2) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="4" style="color:#666666;">{{ __('No activity in this period.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="section-title">{{ __('Expenses by Category') }}</p>
    <table class="data">
        <thead>
            <tr>
                <th>{{ __('Category') }}</th>
                <th class="num">{{ __('Total') }}</th>
                <th class="num">{{ __('Entries') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['by_category'] ?? [] as $row)
                <tr>
                    <td>{{ $row['category'] }}</td>
                    <td class="num">${{ number_format($row['total'], 2) }}</td>
                    <td class="num">{{ $row['count'] }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="color:#666666;">{{ __('No expenses recorded in this period.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="section-title">{{ __('Revenue by Tenant') }}</p>
    <table class="data">
        <thead>
            <tr>
                <th>{{ __('Institution') }}</th>
                <th class="num">{{ __('Payments') }}</th>
                <th class="num">{{ __('Revenue') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['by_tenant'] ?? [] as $row)
                <tr>
                    <td>{{ $row['school'] }}</td>
                    <td class="num">{{ $row['count'] }}</td>
                    <td class="num pos">${{ number_format($row['total'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="color:#666666;">{{ __('No payments received in this period.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('modules.saas.pdf.partials.footer', ['cfg' => $cfg])
</body>
</html>
