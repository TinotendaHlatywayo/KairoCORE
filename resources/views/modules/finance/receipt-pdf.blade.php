<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Receipt - {{ $payment->receipt_number }}</title>
    @php
        $financeTheme = finance_document_theme($template ?? null, 'receipt', $school);
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
        'title' => __('OFFICIAL CASH RECEIPT'),
        'refs' => [],
    ])

    <!-- 2. Transaction Summary Header -->
    <div class="receipt-summary" style="background: {{ $financeTheme['green_tint'] }}; border: 1px solid {{ $financeTheme['soft_border'] }}; padding: 12px; text-align: center; font-size: 14px; margin-bottom: 15px; border-radius: 4px;">
        <strong>TOTAL TRANSACTION PAYMENT: ${{ number_format($payment->amount, 2) }} USD</strong>
        <p style="font-size: 8px; color: {{ $financeTheme['success_color'] }}; margin: 3px 0 0 0;">Receipt Matching Code: {{ $payment->receipt_number }}</p>
    </div>

    <!-- 3. Receipt Metadata Table -->
    <table class="metadata-table">
        <tr>
            <td class="label">{{ __('Receipt No:') }}</td>
            <td><strong>{{ $payment->receipt_number }}</strong></td>
            <td class="label">{{ __('Payment Date:') }}</td>
            <td>{{ $payment->payment_date->format('d-M-Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Student Name:') }}</td>
            <td>{{ $invoice->student->full_name }}</td>
            <td class="label">{{ __('Admission No:') }}</td>
            <td>{{ $invoice->student->admission_number }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Class/Form:') }}</td>
            <td>{{ $invoice->student->currentEnrollment?->section?->full_name ?? 'N/A' }}</td>
            <td class="label">{{ __('Academic Term:') }}</td>
            <td>{{ ucwords(strtolower($invoice->term->name)) }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Payment Method:') }}</td>
            <td>{{ strtoupper($payment->payment_method) }}</td>
            <td class="label">{{ __('TXN Reference:') }}</td>
            <td><strong>{{ $payment->reference_number ?? 'N/A' }}</strong></td>
        </tr>
    </table>

    <!-- 4. Reconciled Outstanding Balance Breakdown Table -->
    <h3 style="font-size: 11px; color: {{ $financeTheme['accent_color'] }}; border-bottom: 1px solid #e5e7eb; padding-bottom: 2px; margin-bottom: 6px; text-transform: uppercase;">{{ __('Financial Reconciliation Breakdown') }}</h3>
    <table class="breakdown-table">
        <thead>
            <tr>
                <th>{{ __('Original Gross Fees') }}</th>
                <th>{{ __('Waiver / Scholarship Credit') }}</th>
                <th>{{ __('Net Term Fees Due') }}</th>
                <th>{{ __('This Transaction Payment') }}</th>
                <th>{{ __('Remaining Outstanding Due') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>${{ number_format($invoice->subtotal_amount, 2) }}</td>
                <td style="color: {{ $financeTheme['light_green'] }}; font-weight: bold;">
                    @if($invoice->discount_amount > 0)
                        -${{ number_format($invoice->discount_amount, 2) }}
                        <div style="font-size: 7px; color: #6b7280; font-weight: normal; margin-top: 2px;">
                            {{ $invoice->waiver_details }}
                        </div>
                    @else
                        $0.00 (No Waiver)
                    @endif
                </td>
                <td style="font-weight: bold; color: {{ $financeTheme['accent_color'] }};">${{ number_format($invoice->total_amount, 2) }}</td>
                <td style="font-weight: bold; color: {{ $financeTheme['success_color'] }}; background: {{ $financeTheme['green_tint'] }};">${{ number_format($payment->amount, 2) }}</td>
                <td style="font-weight: bold; color: {{ $financeTheme['light_red'] }}; background: {{ $financeTheme['red_tint'] }}; font-size: 16px;">${{ number_format($invoice->balance_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- 5. Signatures, QR & certification footer -->
    @include('modules.finance.partials.document-footer', [
        'f' => $f,
        'financeTheme' => $financeTheme,
        'signatureLeft' => $config['receipt_signature_left'] ?? '',
        'signatureRight' => $config['receipt_signature_right'] ?? '',
        'qrUrl' => $invoice->integrity_hash ? route('finance.verify', ['hash' => $invoice->integrity_hash, 'type' => 'receipt']) : null,
        'fallbackFooter' => $config['receipt_footer'] ?? '',
    ])

</div>

<div class="powered-by">Powered by Tinway Technologies</div>
</body>
</html>
