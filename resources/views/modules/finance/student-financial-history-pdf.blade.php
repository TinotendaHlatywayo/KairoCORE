<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Student Financial History') }}</title>
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
    @endphp
    @include('modules.finance.partials.document-styles', [
        'financeTheme' => $financeTheme,
        'h' => $h,
        't' => $t,
        'm' => $m,
        'tb' => $tb,
        'f' => $f,
        'bodyFontSize' => 11,
    ])
</head>
<body class="style-{{ $structure }}">
<div class="doc-page">

    @include('modules.finance.partials.document-header', [
        'financeTheme' => $financeTheme,
        'h' => $h,
        't' => $t,
        'profile' => $profile,
        'logoPath' => $logoPath,
        'logoSize' => $logoSize,
        'title' => __('STUDENT FINANCIAL HISTORY'),
        'refs' => [],
    ])

    <!-- Student + period metadata -->
    <table style="width: 100%; margin-bottom: 12px;">
        <tr>
            <td style="width: 50%; line-height: 1.4; vertical-align: top; font-size: {{ $m['font_size'] }}px; color: {{ $m['color'] }}; font-weight: {{ $m['bold'] ? 'bold' : 'normal' }}; font-style: {{ $m['italic'] ? 'italic' : 'normal' }};">
                <strong>{{ __('Student Name:') }}</strong> {{ $student->full_name }}<br/>
                <strong>{{ __('Admission Number:') }}</strong> {{ $student->admission_number }}<br/>
                <strong>{{ __('Class Placement:') }}</strong> {{ $student->currentEnrollment?->section?->full_name ?? 'N/A' }}<br/>
                <strong>{{ __('Date Enrolled:') }}</strong> {{ $student->admission_date?->format('d-M-Y') ?? 'N/A' }}
            </td>
            <td style="width: 50%; line-height: 1.4; text-align: right; vertical-align: top; font-size: {{ $m['font_size'] }}px; color: {{ $m['color'] }}; font-weight: {{ $m['bold'] ? 'bold' : 'normal' }}; font-style: {{ $m['italic'] ? 'italic' : 'normal' }};">
                <strong>{{ __('Report Date:') }}</strong> {{ date('d-M-Y H:i') }}<br/>
                <strong>{{ __('Period:') }}</strong> {{ $scopeLabel }}<br/>
                <strong>{{ __('Ledger Standard:') }}</strong> {{ __('Base USD Currency') }}
            </td>
        </tr>
    </table>

    <!-- Ledger table -->
    <table class="results-table">
        <thead>
            <tr>
                <th style="width: 12%;">{{ __('Date') }}</th>
                <th style="text-align: left; width: 36%;">{{ __('Transaction Description') }}</th>
                <th style="text-align: left; width: 14%;">{{ __('Receipt / Ref') }}</th>
                <th style="width: 9%;">{{ __('Method') }}</th>
                <th style="width: 9%;">Debit (+)</th>
                <th style="width: 9%;">Credit (-)</th>
                <th style="width: 11%;">Balance ($)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ledger['rows'] as $row)
                @php
                    $isOpening = ! empty($row['is_opening']);
                    $isPayment = ! empty($row['type']) && in_array($row['type'], ['payment', 'credit'], true);
                    $isRefund = ! empty($row['type']) && $row['type'] === 'refund';
                    $isWaiver = ! empty($row['type']) && $row['type'] === 'waiver';
                @endphp
                <tr @if($loop->even) class="alt" @endif
                    style="{{ ($isOpening || $isWaiver) ? 'background-color: ' . $financeTheme['blue_tint'] . ';' : '' }}">
                    <td>{{ $row['date'] instanceof \Carbon\Carbon ? $row['date']->format('d-M-Y') : '-' }}</td>
                    <td style="text-align: left;">
                        {{ $row['description'] }}
                        @if($isRefund)<span style="font-size: 8px; font-weight: bold;"> (REFUND)</span>@endif
                    </td>
                    <td style="text-align: left; font-size: 9px;">
                        {{ $row['receipt'] ? $row['receipt'] : ($row['reference'] ?? '') }}
                    </td>
                    <td style="font-size: 9px;">{{ $row['method'] ?? ucfirst($row['type'] ?? '') }}</td>
                    <td>{{ $row['debit'] > 0 ? '$'.number_format($row['debit'], 2) : '-' }}</td>
                    <td>{{ $row['credit'] != 0 ? '$'.number_format($row['credit'], 2) : '-' }}</td>
                    <td style="font-weight: bold;">${{ number_format($row['running_balance'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Period summary -->
    <table style="width: 100%; margin-top: 10px; font-size: 11px; border-collapse: collapse;">
        <tr>
            <td style="width: 50%; padding: 4px 6px;"><strong>{{ __('Opening Balance:') }}</strong> ${{ number_format($ledger['opening_balance'], 2) }}</td>
            <td style="width: 50%; padding: 4px 6px; text-align: right;"><strong>{{ __('Closing Balance:') }}</strong> ${{ number_format($ledger['closing_balance'], 2) }}</td>
        </tr>
        <tr>
            <td style="padding: 4px 6px;"><strong>{{ __('Total Billed:') }}</strong> ${{ number_format($ledger['total_billed'], 2) }}</td>
            <td style="padding: 4px 6px; text-align: right;"><strong>{{ __('Total Paid:') }}</strong> ${{ number_format($ledger['total_paid'], 2) }}</td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 4px 6px;"><strong>{{ __('Total Refunded:') }}</strong> ${{ number_format($ledger['total_refunded'], 2) }}</td>
        </tr>
    </table>

    @include('modules.finance.partials.document-footer', [
        'f' => $f,
        'financeTheme' => $financeTheme,
        'signatureLeft' => '',
        'signatureRight' => '',
        'qrUrl' => null,
        'fallbackFooter' => $config['statement_footer'] ?? '',
    ])

</div>

<div class="powered-by">Powered by Kairo CORE</div>
</body>
</html>