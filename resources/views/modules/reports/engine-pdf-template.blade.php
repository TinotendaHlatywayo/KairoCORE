<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            color: #334155;
            margin: 0;
            padding: 0;
        }
        .header-table { width: 100%; padding-bottom: 12px; margin-bottom: 20px; border-bottom: 2px solid {{ $primaryColor }}; }
        .logo-box { width: 80px; vertical-align: middle; }
        .logo-box img { max-height: 65px; max-width: 65px; }
        .school-info { vertical-align: middle; padding-left: 10px; }
        .school-name { font-size: 16px; font-weight: bold; color: #0f172a; text-transform: uppercase; }
        .report-subtitle { font-size: 9px; color: #64748b; margin-top: 3px; }
        .report-title { font-size: 17px; font-weight: bold; margin-bottom: 6px; text-transform: uppercase; text-align: center; color: {{ $primaryColor }}; }
        .meta-row { text-align: center; font-size: 9px; color: #64748b; margin-bottom: 16px; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .data-table th { color: #ffffff; font-weight: bold; text-align: left; padding: 7px; border: 1px solid #e2e8f0; text-transform: uppercase; font-size: 9px; background-color: {{ $primaryColor }}; }
        .data-table td { padding: 7px; border: 1px solid #cbd5e1; vertical-align: middle; }
        .data-table tr:nth-child(even) { background-color: #f8fafc; }
        .summary-box { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .summary-box td { border: 1px solid #e2e8f0; padding: 8px 12px; }
        .summary-label { font-size: 8px; text-transform: uppercase; color: #64748b; }
        .summary-value { font-size: 14px; font-weight: bold; color: #0f172a; }
        .footer-table { width: 100%; border-top: 1px solid #cbd5e1; padding-top: 10px; margin-top: 30px; font-size: 8px; color: #94a3b8; }
        .signature-section { width: 100%; margin-top: 50px; margin-bottom: 30px; }
        .signature-line { width: 200px; border-bottom: 1px dashed #64748b; margin-bottom: 5px; }
        .signature-label { font-weight: bold; color: #0f172a; }
        .signature-role { color: #64748b; font-size: 9px; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            @if(!empty($settings['show_logo'] ?? true) && $school && !empty($school->logo_path))
                <td class="logo-box">
                    <img src="data:image/png;base64,{{ base64_encode(@file_get_contents(storage_path('app/public/' . $school->logo_path))) }}" alt="Logo">
                </td>
            @endif
            <td class="school-info">
                <div class="school-name">{{ $school->name ?? 'SCHOOLCORE ACADEMY' }}</div>
                @if(!empty($settings['header_text']))
                    <div class="report-subtitle">{{ $settings['header_text'] }}</div>
                @endif
            </td>
            <td style="text-align: right; vertical-align: bottom; font-size: 8px; color: #64748b;">
                Date Run: {{ now()->format('Y-m-d H:i') }}<br>
                Records: {{ $recordCount ?? 0 }}
            </td>
        </tr>
    </table>

    <div class="report-title">{{ $title }}</div>
    @if(!empty($filtersSummary))
        <div class="meta-row">Applied filters: {{ $filtersSummary }}</div>
    @endif

    @if(!empty($summary))
        <table class="summary-box">
            <tr>
                @foreach($summary as $label => $value)
                    <td>
                        <div class="summary-label">{{ $label }}</div>
                        <div class="summary-value">{{ $value }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                @foreach($columns as $key)
                    <th>{{ $headings[$key] ?? ucfirst(str_replace('_', ' ', $key)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $key)
                        <td>{{ $row->{Str::afterLast($key, '.')} ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" style="text-align: center; color: #94a3b8; padding: 20px;">
                        {{ __('No records matched the configured filters for this execution run.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(!empty($settings['show_signature_block'] ?? true))
        <table class="signature-section">
            <tr>
                <td>
                    <div class="signature-line"></div>
                    <div class="signature-label">{{ __('Compiled By') }}</div>
                    <div class="signature-role">{{ __('Office Registrar & Records Officer') }}</div>
                </td>
                <td style="text-align: right;">
                    <div class="signature-line" style="margin-left: auto;"></div>
                    <div class="signature-label">{{ __('Verified By') }}</div>
                    <div class="signature-role">{{ __('Principal / Institutional Administrator') }}</div>
                </td>
            </tr>
        </table>
    @endif

    <table class="footer-table">
        <tr>
            <td>{{ $settings['footer_text'] ?? 'Confidential - SchoolCore ERP Secured Ledger Record.' }}</td>
            <td style="text-align: right;">{{ __('System Stamp Signature: SECURED ELECTRONIC LOG') }}</td>
        </tr>
    </table>

</body>
</html>
