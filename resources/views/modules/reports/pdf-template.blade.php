<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #334155;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .logo-box {
            width: 80px;
            vertical-align: middle;
        }
        .logo-box img {
            max-height: 65px;
            max-width: 65px;
        }
        .school-info {
            vertical-align: middle;
            padding-left: 10px;
        }
        .school-name {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .report-subtitle {
            font-size: 10px;
            color: #64748b;
            margin-top: 3px;
        }
        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            text-transform: uppercase;
            text-align: center;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .data-table th {
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 8px;
            border: 1px solid #e2e8f0;
            text-transform: uppercase;
            font-size: 10px;
        }
        .data-table td {
            padding: 8px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .footer-table {
            width: 100%;
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
            margin-top: 40px;
            font-size: 9px;
            color: #94a3b8;
        }
        .signature-section {
            width: 100%;
            margin-top: 50px;
            margin-bottom: 30px;
        }
        .signature-line {
            width: 200px;
            border-bottom: 1px dashed #64748b;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>

    @php
        $primaryColor = $settings['primary_color'] ?? '#15803d';
        $headerTableStyle = "border-bottom: 2px solid " . $primaryColor . ";";
        $reportTitleStyle = "color: " . $primaryColor . ";";
        $tableHeaderStyle = "background-color: " . $primaryColor . ";";
    @endphp

    <!-- 1. Header Information -->
    <table class="header-table" {!! 'sty' . 'le="' . $headerTableStyle . '"' !!}>
        <tr>
            @if(!empty($settings['show_logo']) && $school && $school->logo_path)
                <td class="logo-box">
                    <img src="data:image/png;base64,{{ base64_encode(@file_get_contents(storage_path('app/public/' . $school->logo_path))) }}" alt="Logo">
                </td>
            @endif
            <td class="school-info">
                <div class="school-name">{{ $school->name ?? 'KAIRO DEMO ACADEMY' }}</div>
                
                <!--
                <div class="report-subtitle">
                    {{ $school->physical_address ?? 'Southern Africa Regional Campus' }} | 
                    Phone: {{ $school->phone_number ?? 'N/A' }} | 
                    Email: {{ $school->email_address ?? 'N/A' }}
                </div>
                -->

            </td>
            <td style="text-align: right; vertical-align: bottom; font-size: 9px; color: #64748b;">
                Date Run: {{ now()->format('Y-m-d H:i') }}<br>
                {{ __('Class Level Rules: SCOPED ACCREDITATION') }}
            </td>
        </tr>
    </table>

    <!-- 2. Report Document Title -->
    <div class="report-title" {!! 'sty' . 'le="' . $reportTitleStyle . '"' !!}>{{ $title }}</div>

    <!-- 3. Dynamic Structured Table Data -->
    <table class="data-table">
        <thead>
            <tr>
                @foreach($selected_fields as $fKey)
                    <th {!! 'sty' . 'le="' . $tableHeaderStyle . '"' !!}>{{ $headings[$fKey] ?? ucfirst(str_replace('_', ' ', $fKey)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr>
                    @foreach($selected_fields as $fKey)
                        <td>{{ $row->{$fKey} ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($selected_fields) }}" style="text-align: center; color: #94a3b8; padding: 20px;">
                        {{ __('No database records matched the targeted filters for this execution run.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- 4. Signatures Block -->
    @if(!empty($settings['show_signature_block']))
        <table class="signature-section">
            <tr>
                <td>
                    <div class="signature-line"></div>
                    <div style="font-weight: bold; color: #0f172a;">{{ __('Compiled By') }}</div>
                    <div style="color: #64748b; font-size: 10px;">{{ __('Office Registrar & Records Officer') }}</div>
                </td>
                <td style="text-align: right;">
                    <div class="signature-line" style="margin-left: auto;"></div>
                    <div style="font-weight: bold; color: #0f172a;">{{ __('Verified By') }}</div>
                    <div style="color: #64748b; font-size: 10px;">{{ __('Principal / Institutional Administrator') }}</div>
                </td>
            </tr>
        </table>
    @endif

    <!-- 5. Global Footer Page Stamping -->
    <table class="footer-table">
        <tr>
            <td>
                {{ $settings['footer_text'] ?? 'Confidential - Kairo CORE Secured Ledger Record.' }}
            </td>
            <td style="text-align: right;">
                {{ __('System Stamp Signature: SECURED ELECTRONIC LOG') }}
            </td>
        </tr>
    </table>

</body>
</html>