<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $cfg = platform_document_config();
        $currency = $report['currency'] ?? 'USD';
        $net = $report['net_profit'] ?? 0;
        $primary = $cfg['primary_color'];
        $dark = $cfg['dark_color'];
        $light = $cfg['light_fill'];
        $watermarkUri = document_watermark_data_uri();
    @endphp
    <meta charset="UTF-8">
    <title>KairoCORE Financial Statement</title>
    <style>
        body { position: relative; font-family: Arial, Helvetica, sans-serif; color: #222222; margin: 0; padding: 26px; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 22px; margin: 0; color: {{ $dark }}; letter-spacing: 1px; }
        .muted { color: #666666; }
        .header { border-bottom: 3px solid {{ $dark }}; padding-bottom: 14px; margin-bottom: 22px; }
        .right { text-align: right; }
        .kpis { width: 100%; border-collapse: collapse; margin-bottom: 26px; }
        .kpis td { width: 25%; padding: 0 6px; }
        .kpi { border: 1px solid #E2E8F0; border-radius: 6px; padding: 14px; }
        .kpi .label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #666666; font-weight: bold; }
        .kpi .value { font-size: 18px; font-weight: bold; margin-top: 6px; color: {{ $dark }}; }
        .kpi .value.negative { color: {{ $primary }}; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 26px; }
        table.data th { background: {{ $dark }}; color: #ffffff; border-bottom: 2px solid {{ $dark }}; font-weight: bold; text-align: left; padding: 9px 10px; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; }
        table.data td { border-bottom: 1px solid #E8EBEF; padding: 9px 10px; color: #444444; }
        table.data tr:nth-child(even) td { background: {{ $light }}; }
        table.data td.num, table.data th.num { text-align: right; }
        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; color: {{ $dark }}; margin: 0 0 10px; border-left: 5px solid {{ $primary }}; padding-left: 10px; }
        .pos { color: #15803d; }
        .neg { color: #b91c1c; }
        .footer { border-top: 1px solid #E2E8F0; padding-top: 14px; margin-top: 34px; font-size: 10px; color: #666666; text-align: center; }
        .watermark { position: absolute; left: 0; right: 0; top: 34%; text-align: center; }
        .watermark img { width: 55%; max-width: 620px; }
    </style>
</head>
<body>
    @if($cfg['watermark_enabled'] && $watermarkUri !== '')
        <div class="watermark"><img src="{{ $watermarkUri }}" alt=""></div>
    @endif

    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <h1>{{ strtoupper($cfg['business_name']) }}</h1>
                    <div class="muted" style="margin-top: 4px;">{{ __('Platform Financial Statement') }}</div>
                </td>
                <td class="right muted">
                    <div><strong>{{ __('Period:') }}</strong> {{ document_date($report['start']) }} &ndash; {{ document_date($report['end']) }}</div>
                    <div><strong>{{ __('Generated:') }}</strong> {{ document_date(now(), 'd M Y H:i') }}</div>
                    <div><strong>{{ __('Currency:') }}</strong> {{ $currency }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="kpis">
        <tr>
            <td>
                <div class="kpi">
                    <div class="label">{{ __('Revenue') }}</div>
                    <div class="value">${{ number_format($report['revenue'] ?? 0, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="kpi">
                    <div class="label">{{ __('Operating Expenses') }}</div>
                    <div class="value">${{ number_format($report['expenses'] ?? 0, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="kpi">
                    <div class="label">{{ __('Net Profit') }}</div>
                    <div class="value {{ $net < 0 ? 'negative' : '' }}">${{ number_format($net, 2) }}</div>
                </div>
            </td>
            <td>
                <div class="kpi">
                    <div class="label">{{ __('Outstanding') }}</div>
                    <div class="value">${{ number_format($report['outstanding_total'] ?? 0, 2) }}</div>
                </div>
            </td>
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
                <tr><td colspan="4" class="muted">{{ __('No activity in this period.') }}</td></tr>
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
                <tr><td colspan="3" class="muted">{{ __('No expenses recorded in this period.') }}</td></tr>
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
                <tr><td colspan="3" class="muted">{{ __('No payments received in this period.') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <p>{{ __('This is a system-generated statement for :company platform operations.', ['company' => $cfg['business_name']]) }}</p>
        <p>{{ __('&copy; :year :company. All rights reserved.', ['year' => now()->year, 'company' => $cfg['business_name']]) }}</p>
    </div>
</body>
</html>