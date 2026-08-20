<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Fee Structure') }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #1f2937; margin: 0; padding: 0; }
        .header { text-align: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 8px; margin-bottom: 15px; }
        .school-name { font-size: 20px; font-weight: bold; color: #1e3a8a; text-transform: uppercase; }
        .results-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .results-table th { background: #1e3a8a; color: white; padding: 6px 8px; border: 1px solid #1e3a8a; }
        .results-table td { padding: 6px 8px; border: 1px solid #e5e7eb; text-align: center; }
    </style>
</head>
<body>

    <div class="header">
        <div class="school-name">{{ $school->name }}</div>
        <div style="font-weight: bold; font-size: 12px; margin-top: 5px;">FEE STRUCTURE: {{ ucwords(strtolower($term->name)) }} ({{ $term->academicYear->name }})</div>
    </div>

    @foreach($structures as $className => $fees)
        <h3 style="color: #1e3a8a; border-bottom: 1px solid #ddd; padding-bottom: 2px; margin-bottom: 8px;">Grade / Level: {{ $className }}</h3>
        <table class="results-table">
            <thead>
                <tr>
                    <th style="text-align: left; width: 50%;">{{ __('Category') }}</th>
                    <th style="width: 25%;">{{ __('Currency') }}</th>
                    <th style="width: 25%;">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @php $total = 0; @endphp
                @foreach($fees as $fee)
                    @php $total += $fee->amount; @endphp
                    <tr>
                        <td style="text-align: left;">{{ $fee->feeCategory->name }}</td>
                        <td>{{ $fee->currency }}</td>
                        <td>${{ number_format($fee->amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr style="font-weight: bold; background: #f3f4f6;">
                    <td colspan="2" style="text-align: right;">{{ __('Total Term Fees:') }}</td>
                    <td>${{ number_format($total, 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach

</body>
</html>