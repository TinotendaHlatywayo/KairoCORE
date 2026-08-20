<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice - {{ $invoice->invoice_number }}</title>
    @php
        $financeTheme = finance_document_theme($template ?? null, 'invoice', $school);
        $h = $financeTheme['sections']['header'];
        $t = $financeTheme['sections']['title'];
        $m = $financeTheme['sections']['metadata'];
        $tb = $financeTheme['sections']['table'];
        $in = $financeTheme['sections']['instructions'];
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

    <!-- 1. School Header & Contact Details -->
    @include('modules.finance.partials.document-header', [
        'financeTheme' => $financeTheme,
        'h' => $h,
        't' => $t,
        'profile' => $profile,
        'logoPath' => $logoPath,
        'logoSize' => $logoSize,
        'title' => __('INVOICE'),
        'refs' => [
            [__('Invoice No:'), $invoice->invoice_number],
            [__('Date Issued:'), $invoice->created_at->format('d-M-Y')],
            [__('Due Date:'), $invoice->due_date->format('d-M-Y')],
        ],
    ])

    <!-- 2. Dual Student Profile & Parent Metadata Card -->
    <table class="metadata-container">
        <tr>
            <td colspan="2" class="section-title">{{ __('Student Information') }}</td>
            @if($config['show_parent_address'])
            <td colspan="2" class="section-title">{{ __('Billing & Parent Information') }}</td>
            @endif
        </tr>
        <tr>
            <!-- Student Detail Block -->
            <td style="width: 15%; text-align: center;">
                <img src="{{ student_photo_src($student) }}" style="width: 65px; height: 70px; border: 1px solid #ddd; object-fit: cover;">
            </td>
            <td style="width: 35%; line-height: 1.4; padding-left: 4px;">
                <strong>{{ __('Name:') }}</strong> {{ $student->full_name }}<br/>
                <strong>{{ __('Adm No:') }}</strong> {{ $student->admission_number }}<br/>
                <strong>{{ __('Class:') }}</strong> {{ $invoice->student->currentEnrollment?->section?->full_name ?? 'N/A' }}<br/>
                @if($config['show_boarding_status'])
                <strong>{{ __('Status:') }}</strong> {{ ucwords(str_replace('_', ' ', $student->boarding_status ?? 'day scholar')) }}<br/>
                @endif
                @if($student->house) <strong>{{ __('House:') }}</strong> {{ $student->house }} @endif
            </td>

            <!-- Parent Details Block -->
            @if($config['show_parent_address'])
            <td colspan="2" style="width: 50%; line-height: 1.4;">
                <strong>{{ __('Term Billing Period:') }}</strong> {{ ucwords(strtolower($invoice->term->name)) }} ({{ $invoice->term->academicYear->name }})<br/>
                <strong>{{ __('Base Currency:') }}</strong> {{ __('USD Ledger Standard') }}<br/>
                <strong>{{ __('Parent Name:') }}</strong> {{ $student->emergency_contact_name ?? 'Parent / Guardian' }}<br/>
                <strong>{{ __('Parent Phone:') }}</strong> {{ $student->emergency_contact_phone ?? 'N/A' }}<br/>
                <strong>{{ __('Emergency Details:') }}</strong> {{ $student->medical_notes ?? 'No recorded medical issues' }}
            </td>
            @endif
        </tr>
    </table>

    <!-- 3. Invoice Items Breakdown -->
    <table class="results-table">
        <thead>
            <tr>
                <th style="text-align: left; width: 50%;">{{ __('Fee Description') }}</th>
                <th style="width: 25%;">{{ __('Quantity') }}</th>
                <th style="width: 25%;">Amount (USD)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $item)
                <tr @if($loop->even) class="alt" @endif>
                    <td style="text-align: left; font-weight: bold;">{{ $item->name }}</td>
                    <td>{{ __('1') }}</td>
                    <td>${{ number_format($item->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="color: #9ca3af; padding: 15px;">{{ __('No fee line items recorded for this invoice.') }}</td>
                </tr>
            @endforelse

            <!-- Financial Totals Summary -->
            <tr style="font-weight: bold; background: #f9fafb;">
                <td colspan="2" style="text-align: right;">{{ __('Subtotal Amount:') }}</td>
                <td>${{ number_format($invoice->subtotal_amount, 2) }}</td>
            </tr>

            <!-- Dynamic Waiver deductions with bracket styling -->
            @if($invoice->discount_amount > 0)
                <tr style="font-weight: bold; color: {{ $financeTheme['success_color'] }}; background: {{ $financeTheme['green_tint'] }};">
                    <td colspan="2" style="text-align: right;">
                        Waiver / Scholarship Applied: {{ $invoice->waiver_details ?? 'Sibling Discount' }}
                    </td>
                    <td>-${{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
            @endif

            <tr style="font-weight: bold; background: {{ $financeTheme['blue_tint'] }};">
                <td colspan="2" style="text-align: right; color: {{ $financeTheme['light_blue'] }};">{{ __('Total Invoice Due:') }}</td>
                <td style="color: {{ $financeTheme['light_blue'] }};">${{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
            <tr style="font-weight: bold; background: {{ $financeTheme['green_tint'] }};">
                <td colspan="2" style="text-align: right; color: {{ $financeTheme['light_green'] }};">{{ __('Amount Paid to Date:') }}</td>
                <td style="color: {{ $financeTheme['light_green'] }};">${{ number_format($invoice->paid_amount, 2) }}</td>
            </tr>
            <tr style="font-weight: bold; background: {{ $financeTheme['red_tint'] }}; font-size: 16px;">
                <td colspan="2" style="text-align: right; color: {{ $financeTheme['light_red'] }};">{{ __('Outstanding Balance:') }}</td>
                <td style="color: {{ $financeTheme['light_red'] }};">${{ number_format($invoice->balance_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- 4. Payment instructions -->
    @if($in['show'])
    @php($referenceNotice = \Modules\Finance\Services\BillingDocumentSettingsService::fillTemplate($config['reference_notice'], ['ADMISSION_NUMBER' => $student->admission_number, 'REGISTRATION_NUMBER' => $config['registration_number'] ?? '']))
    <div class="instructions">
        <strong style="color: {{ $financeTheme['accent_color'] }};">{{ __('PAYMENT CHANNELS & REF INSTRUCTIONS') }}</strong>
        <table style="width: 100%; border-collapse: collapse; margin-top: 6px; font-size: {{ $in['font_size'] }}px; line-height: 1.5; color: {{ $in['color'] }};">
            @foreach($config['banks'] as $index => $bank)
            <tr>
                <td style="width: 22%; font-weight: bold; padding: 2px 4px; vertical-align: top;">
                    @if(count($config['banks']) > 1)Option {{ $index + 1 }} — @endif Bank Deposit
                </td>
                <td style="padding: 2px 4px;">
                    <strong>{{ $bank['bank_name'] }}</strong> — Account No: {{ $bank['account_number'] }}@if(!empty($bank['branch_code'])) (Branch Code: {{ $bank['branch_code'] }})@endif
                </td>
            </tr>
            @endforeach
            @if(!empty($config['ecocash_merchant']))
            <tr>
                <td style="width: 22%; font-weight: bold; padding: 2px 4px; vertical-align: top;">{{ __('EcoCash') }}</td>
                <td style="padding: 2px 4px;">{{ __('Merchant Pin Code:') }} <strong>{{ $config['ecocash_merchant'] }}</strong></td>
            </tr>
            @endif
        </table>
        <p style="font-size: {{ $in['font_size'] }}px; line-height: 1.4; margin: 5px 0 0 0; color: {{ $in['color'] }};">
            <strong>{{ __('Reference Notice:') }}</strong> {!! $referenceNotice !!}
        </p>
    </div>
    @endif

    <!-- 5. Stamp, Signatures & QR block + footer -->
    @include('modules.finance.partials.document-footer', [
        'f' => $f,
        'financeTheme' => $financeTheme,
        'signatureLeft' => $config['invoice_signature_left'] ?? '',
        'signatureRight' => $config['invoice_signature_right'] ?? '',
        'qrUrl' => $invoice->integrity_hash ? route('finance.verify', ['hash' => $invoice->integrity_hash, 'type' => 'invoice']) : null,
        'fallbackFooter' => '',
    ])

</div>

<div class="powered-by">Powered by Tinway Technologies</div>
</body>
</html>
