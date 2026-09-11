<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; color: #111827; margin: 24px; font-size: 12px; }
        .letterhead { text-align: center; border-bottom: 2px solid #4f46e5; padding-bottom: 12px; margin-bottom: 16px; }
        .letterhead h1 { font-size: 20px; margin: 0 0 2px; color: #1e1b4b; }
        .letterhead p { margin: 2px 0; color: #6b7280; font-size: 10px; }
        .title { font-size: 15px; font-weight: bold; text-align: center; margin: 14px 0 4px; }
        .period { text-align: center; font-size: 11px; color: #374151; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #e5e7eb; padding: 7px 10px; text-align: left; font-size: 11px; }
        th { background: #eef2ff; color: #312e81; font-weight: 600; }
        .amount { text-align: right; font-variant-numeric: tabular-nums; }
        .positive { color: #047857; font-weight: 600; }
        .negative { color: #b91c1c; font-weight: 600; }
        .total td { background: #f5f3ff; font-weight: 700; }
        .footer { margin-top: 18px; font-size: 9px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <div class="letterhead">
        <h1>{{ $data['school'] }}</h1>
        @if(!empty($data['companyTagline']))<p>{{ $data['companyTagline'] }}</p>@endif
        @php($infos = array_filter([$data['companyAddress'] ?? '', $data['companyPhone'] ?? '', $data['companyEmail'] ?? '']))
        @if($infos)
            <p>{{ implode(' | ', $infos) }}</p>
        @endif
    </div>

    <div class="title">Official Financial Statement & Cash Flow</div>
    <div class="period">Period: {{ $data['startDate'] }} to {{ $data['endDate'] }}</div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="amount">Amount (USD)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total Revenue / Inflows</td>
                <td class="amount positive">${{ number_format($data['totalRevenue'], 2) }}</td>
            </tr>
            <tr>
                <td>Total Refunds</td>
                <td class="amount negative">-${{ number_format($data['totalRefunds'], 2) }}</td>
            </tr>
            <tr>
                <td>Total Expenses & Outflows</td>
                <td class="amount negative">-${{ number_format($data['totalExpenses'], 2) }}</td>
            </tr>
            <tr class="total">
                <td>Net Cash Flow Balance</td>
                <td class="amount">${{ number_format($data['netCashFlow'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Generated: {{ $data['generatedAt'] }} &middot; This statement reflects cash movements recorded in the school accounting system.
    </div>
</body>
</html>