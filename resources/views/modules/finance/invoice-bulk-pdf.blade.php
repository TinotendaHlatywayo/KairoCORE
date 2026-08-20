<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Bulk Invoices') }}</title>
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
    <style>
        .page-break { page-break-after: always; }
    </style>
</head>
<body class="style-{{ $structure }}">

    @foreach($invoices as $index => $data)
        <div class="{{ $index < count($invoices) - 1 ? 'page-break' : '' }}">
        <div class="doc-page">

            @php
                $profile = document_school_profile($school, $data['config']);
                $logoPath = finance_document_logo_path($h, $data['config']);
            @endphp

            <!-- School header + title -->
            @include('modules.finance.partials.document-header', [
                'financeTheme' => $financeTheme,
                'h' => $h,
                't' => $t,
                'profile' => $profile,
                'logoPath' => $logoPath,
                'logoSize' => $logoSize,
                'title' => __('OFFICIAL STUDENT INVOICE'),
                'refs' => [],
            ])

            <table class="metadata-table">
                <tr>
                    <td class="label">{{ __('Invoice No:') }}</td>
                    <td><strong>{{ $data['invoice']->invoice_number }}</strong></td>
                    <td class="label">{{ __('Due Date:') }}</td>
                    <td>{{ $data['invoice']->due_date->format('d-M-Y') }}</td>
                </tr>
                <tr>
                    <td class="label">{{ __('Student Name:') }}</td>
                    <td>{{ $data['student']->full_name }}</td>
                    <td class="label">{{ __('Adm No:') }}</td>
                    <td>{{ $data['student']->admission_number }}</td>
                </tr>
                @if($data['config']['show_boarding_status'])
                <tr>
                    <td class="label">{{ __('Residence:') }}</td>
                    <td>{{ ucfirst($data['student']->boarding_status ?? 'Day Scholar') }}</td>
                    <td class="label">{{ __('Term:') }}</td>
                    <td>{{ ucwords(strtolower($data['invoice']->term->name)) }}</td>
                </tr>
                @endif
            </table>

            <table class="results-table">
                <thead>
                    <tr>
                        <th style="text-align: left; width: 60%;">{{ __('Description') }}</th>
                        <th style="width: 20%;">{{ __('Qty') }}</th>
                        <th style="width: 20%;">{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['invoice']->items as $item)
                        <tr @if($loop->even) class="alt" @endif>
                            <td style="text-align: left;">{{ $item->name }}</td>
                            <td>{{ __('1') }}</td>
                            <td>${{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr style="font-weight: bold; background: #f3f4f6;">
                        <td colspan="2" style="text-align: right;">{{ __('Subtotal:') }}</td>
                        <td>${{ number_format($data['invoice']->subtotal_amount, 2) }}</td>
                    </tr>
                    @if($data['invoice']->discount_amount > 0)
                    <tr style="font-weight: bold; color: {{ $financeTheme['success_color'] }}; background: {{ $financeTheme['green_tint'] }};">
                        <td colspan="2" style="text-align: right;">
                            Waiver Applied: {{ $data['invoice']->waiver_details ?? 'Scholarship' }}
                        </td>
                        <td>-${{ number_format($data['invoice']->discount_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr style="font-weight: bold; background: #e5e7eb;">
                        <td colspan="2" style="text-align: right; font-size: 11px; color: {{ $financeTheme['accent_color'] }};">Total Due (Base USD):</td>
                        <td style="font-size: 11px; color: {{ $financeTheme['accent_color'] }};">${{ number_format($data['invoice']->total_amount, 2) }}</td>
                    </tr>
                    <tr style="font-weight: bold; background: {{ $financeTheme['red_tint'] }}; font-size: 15px;">
                        <td colspan="2" style="text-align: right; color: {{ $financeTheme['light_red'] }};">{{ __('Outstanding Balance:') }}</td>
                        <td style="color: {{ $financeTheme['light_red'] }};">${{ number_format($data['invoice']->balance_amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            @if($in['show'])
            @php($referenceNotice = \Modules\Finance\Services\BillingDocumentSettingsService::fillTemplate($data['config']['reference_notice'], ['ADMISSION_NUMBER' => $data['student']->admission_number, 'REGISTRATION_NUMBER' => $data['config']['registration_number'] ?? '']))
            <div class="instructions">
                <strong style="color: {{ $financeTheme['accent_color'] }};">PAYMENT INSTRUCTIONS (ECOCASH / ZIPIT / SWIPE / CASH)</strong>
                <table style="width: 100%; border-collapse: collapse; margin-top: 6px; font-size: {{ $in['font_size'] }}px; line-height: 1.5; color: {{ $in['color'] }};">
                    @foreach($data['config']['banks'] as $index => $bank)
                    <tr>
                        <td style="width: 25%; font-weight: bold; padding: 2px 4px; vertical-align: top;">
                            @if(count($data['config']['banks']) > 1)Option {{ $index + 1 }} — @endif Bank Deposit
                        </td>
                        <td style="padding: 2px 4px;">
                            <strong>{{ $bank['bank_name'] }}</strong> — Account No: {{ $bank['account_number'] }}@if(!empty($bank['branch_code'])) (Branch Code: {{ $bank['branch_code'] }})@endif
                        </td>
                    </tr>
                    @endforeach
                    @if(!empty($data['config']['ecocash_merchant']))
                    <tr>
                        <td style="width: 25%; font-weight: bold; padding: 2px 4px; vertical-align: top;">{{ __('EcoCash') }}</td>
                        <td style="padding: 2px 4px;">{{ __('Merchant Pin Code:') }} <strong>{{ $data['config']['ecocash_merchant'] }}</strong></td>
                    </tr>
                    @endif
                </table>
                <p style="font-size: {{ $in['font_size'] }}px; line-height: 1.4; margin: 5px 0 0 0; color: {{ $in['color'] }};">
                    <strong>{{ __('Reference Instruction:') }}</strong> {!! $referenceNotice !!}
                </p>
            </div>
            @endif

            <!-- Signatures, QR & footer -->
            @include('modules.finance.partials.document-footer', [
                'f' => $f,
                'financeTheme' => $financeTheme,
                'signatureLeft' => $data['config']['invoice_signature_left'] ?? '',
                'signatureRight' => $data['config']['invoice_signature_right'] ?? '',
                'qrUrl' => $data['invoice']->integrity_hash ? route('finance.verify', ['hash' => $data['invoice']->integrity_hash, 'type' => 'invoice']) : null,
                'fallbackFooter' => '',
            ])

        </div>
        </div>
    @endforeach

<div class="powered-by">Powered by Tinway Technologies</div>
</body>
</html>
