<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Account Statement') }}</title>
    @php
        $financeTheme = finance_document_theme($template ?? null, 'statement', $school);
        $h = $financeTheme['sections']['header'];
        $t = $financeTheme['sections']['title'];
        $m = $financeTheme['sections']['metadata'];
        $tb = $financeTheme['sections']['table'];
        $f = $financeTheme['sections']['footer'];
        $structure = $financeTheme['structure'] ?? 'classic';
        $logoSize = (int) $h['logo_size'];
        $profile = document_school_profile($school, $config);
        $logoPath = finance_document_logo_path($h, $config);
        $verifyHash = $verify_hash ?? null;
    @endphp
    @include('modules.finance.partials.document-styles', [
        'financeTheme' => $financeTheme,
        'h' => $h,
        't' => $t,
        'm' => $m,
        'tb' => $tb,
        'f' => $f,
        'bodyFontSize' => 12,
    ])
</head>
<body class="style-{{ $structure }}">
<div class="doc-page">

    <!-- 1. School Header + title -->
    @include('modules.finance.partials.document-header', [
        'financeTheme' => $financeTheme,
        'h' => $h,
        't' => $t,
        'profile' => $profile,
        'logoPath' => $logoPath,
        'logoSize' => $logoSize,
        'title' => __('OFFICIAL STATEMENT OF ACCOUNT'),
        'refs' => [],
    ])

    <!-- 2. Student + statement metadata -->
    <table style="width: 100%; margin-bottom: 15px;">
        <tr>
            <td style="width: 50%; line-height: 1.4; vertical-align: top; font-size: {{ $m['font_size'] }}px; color: {{ $m['color'] }}; font-weight: {{ $m['bold'] ? 'bold' : 'normal' }}; font-style: {{ $m['italic'] ? 'italic' : 'normal' }};">
                <strong>{{ __('Student Name:') }}</strong> {{ $student->full_name }}<br/>
                <strong>{{ __('Admission Number:') }}</strong> {{ $student->admission_number }}<br/>
                <strong>{{ __('Class Placement:') }}</strong> {{ $student->currentEnrollment?->section?->full_name ?? 'N/A' }}
            </td>
            <td style="width: 50%; line-height: 1.4; text-align: right; vertical-align: top; font-size: {{ $m['font_size'] }}px; color: {{ $m['color'] }}; font-weight: {{ $m['bold'] ? 'bold' : 'normal' }}; font-style: {{ $m['italic'] ? 'italic' : 'normal' }};">
                <strong>{{ __('Statement Date:') }}</strong> {{ date('d-M-Y H:i') }}<br/>
                <strong>{{ __('Ledger Standard:') }}</strong> {{ __('Base USD Currency') }}
            </td>
        </tr>
    </table>

    <!-- 3. Ledger table -->
    <table class="results-table">
        <thead>
            <tr>
                <th style="width: 15%;">{{ __('Date') }}</th>
                <th style="text-align: left; width: 45%;">{{ __('Transaction Description') }}</th>
                <th style="width: 13%;">Debit (+)</th>
                <th style="width: 13%;">Credit (-)</th>
                <th style="width: 14%;">Balance ($)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ledger as $row)
                @php
                    // Highlight waiver and payment credits differently for audit clarity
                    $isWaiver = strpos($row['type'], 'Waiver Applied') !== false;
                @endphp
                <tr @if($loop->even) class="alt" @endif style="{{ $isWaiver ? 'background-color: ' . $financeTheme['green_tint'] . '; color: ' . $financeTheme['light_green'] . ';' : '' }}">
                    <td>{{ \Carbon\Carbon::parse($row['date'])->format('d-M-Y') }}</td>
                    <td style="text-align: left; font-weight: {{ $isWaiver ? 'bold' : 'normal' }};">
                        {{ $row['type'] }}
                    </td>
                    <td>{{ $row['debit'] > 0 ? '$' . number_format($row['debit'], 2) : '-' }}</td>
                    <td>{{ $row['credit'] > 0 ? '$' . number_format($row['credit'], 2) : '-' }}</td>
                    <td style="font-weight: bold;">${{ number_format($row['running_balance'], 2) }}</td>
                </tr>
            @endforeach
            <tr style="font-weight: bold; background: {{ $financeTheme['blue_tint'] }}; font-size: 15px;">
                <td colspan="4" style="text-align: right; color: {{ $financeTheme['light_blue'] }}; padding: 8px;">{{ __('Net Outstanding Balance Due:') }}</td>
                <td style="color: {{ $financeTheme['light_blue'] }}; padding: 8px;">${{ number_format($current_balance, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- 4. Signatures, QR & footer -->
    @include('modules.finance.partials.document-footer', [
        'f' => $f,
        'financeTheme' => $financeTheme,
        'signatureLeft' => '',
        'signatureRight' => '',
        'qrUrl' => $verifyHash ? route('finance.verify', ['hash' => $verifyHash, 'type' => 'statement']) : null,
        'fallbackFooter' => $config['statement_footer'] ?? '',
    ])

</div>

<div class="powered-by">Powered by Tinway Technologies</div>
</body>
</html>
