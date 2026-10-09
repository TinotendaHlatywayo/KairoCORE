<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>KairoCORE Financial Statement</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #1e293b; margin: 0; padding: 24px; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 22px; margin: 0; color: #4f46e5; }
        .muted { color: #64748b; }
        .header { border-bottom: 3px solid #4f46e5; padding-bottom: 14px; margin-bottom: 22px; }
        .right { text-align: right; }
        .kpis { width: 100%; border-collapse: collapse; margin-bottom: 26px; }
        .kpis td { width: 25%; padding: 0 6px; }
        .kpi { border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; }
        .kpi .label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; font-weight: 700; }
        .kpi .value { font-size: 18px; font-weight: bold; margin-top: 6px; color: #0f172a; }
        .kpi .value.negative { color: #b91c1c; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 26px; }
        table.data th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #475569; font-weight: 700; text-align: left; padding: 9px 10px; font-size: 10px; text-transform: uppercase; }
        table.data td { border-bottom: 1px solid #e2e8f0; padding: 9px 10px; color: #334155; }
        table.data td.num, table.data th.num { text-align: right; }
        .section-title { font-size: 13px; font-weight: 700; color: #0f172a; margin: 0 0 10px; }
        .pos { color: #15803d; }
        .neg { color: #b91c1c; }
        .footer { border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 34px; font-size: 10px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
    @php
        $currency = $report['currency'] ?? 'USD';
        $net = $report['net_profit'] ?? 0;
    @endphp

    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <h1>{{ __('KairoCORE') }}</h1>
                    <div class="muted" style="margin-top: 4px;">{{ __('Platform Financial Statement') }}</div>
                </td>
                <td class="right muted">
                    <div><strong>{{ __('Period:') }}</strong> {{ $report['start']->format('M d, Y') }} &ndash; {{ $report['end']->format('M d, Y') }}</div>
                    <div><strong>{{ __('Generated:') }}</strong> {{ now()->format('M d, Y H:i') }}</div>
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
        <p>{{ __('This is a system-generated statement for KairoCORE platform operations.') }}</p>
        <p>{{ __('&copy; :year Kairo CORE Software Inc. All rights reserved.', ['year' => now()->year]) }}</p>
    </div>
</body>
</html>
