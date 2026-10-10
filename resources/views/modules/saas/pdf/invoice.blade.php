<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $cfg = platform_document_config();
        $brand = email_branding();
        $school = $invoice->school;
        $primary = $cfg['primary_color'];
        $dark = $cfg['dark_color'];
        $light = $cfg['light_fill'];
        $closingFontSize = (int) ($cfg['closing_font_size'] ?? 13);
        $closingWeight = (string) ($cfg['closing_font_weight'] ?? 'bold');
        $closingAlign = (string) ($cfg['closing_align'] ?? 'center');
        $logoUri = document_logo_data_uri();
        $watermarkUri = document_watermark_data_uri();
    @endphp
    <meta charset="UTF-8">
    <title>SaaS Subscription Invoice: {{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #222222; margin: 0; padding: 28px; line-height: 1.5; font-size: 12px; }
        .brand { margin: 0; }
        .brand img { height: 58px; width: auto; }
        .doc-title { font-size: 26px; font-weight: bold; color: {{ $primary }}; margin: 0; text-transform: uppercase; letter-spacing: 2px; text-align: right; }
        .doc-meta { font-size: 11px; color: #444444; text-align: right; margin-top: 6px; line-height: 1.7; }
        .rule { height: 3px; background: {{ $primary }}; margin: 16px 0 22px; }
        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; color: {{ $primary }}; margin-bottom: 6px; }
        .party { font-size: 12px; line-height: 1.7; color: #444444; }
        .party strong { color: #222222; }
        table.layout { width: 100%; border-collapse: collapse; }
        table.layout > tbody > tr > td { vertical-align: top; width: 50%; padding: 0 10px; }
        table.items { width: 100%; border-collapse: collapse; margin: 20px 0 8px; }
        table.items th { background: {{ $dark }}; color: #ffffff; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; text-align: left; padding: 10px 12px; }
        table.items td { border-bottom: 1px solid #E2E8F0; padding: 11px 12px; font-size: 12px; color: #333333; }
        table.items tr:nth-child(even) td { background: {{ $light }}; }
        table.totals { width: 45%; margin-left: auto; border-collapse: collapse; margin-top: 8px; }
        table.totals td { padding: 7px 12px; font-size: 12px; }
        table.totals td.label { color: #666666; text-align: right; }
        table.totals td.value { font-weight: bold; text-align: right; color: #222222; }
        .total-due { background: {{ $primary }}; color: #ffffff; padding: 13px 18px; margin: 14px 0 0 auto; width: 45%; text-align: right; }
        .total-due .td-label { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; opacity: 0.9; }
        .total-due .td-value { font-size: 20px; font-weight: bold; margin-top: 3px; }
        .note-box { background: {{ $light }}; border-left: 4px solid {{ $primary }}; padding: 12px 16px; margin: 22px 0; font-size: 11px; color: #444444; }
        .note-box .note-title { font-weight: bold; text-transform: uppercase; letter-spacing: 1px; font-size: 10px; color: {{ $primary }}; margin-bottom: 5px; }
        table.pay { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.pay td { padding: 5px 10px; font-size: 11px; color: #444444; border-bottom: 1px solid #E2E8F0; }
        table.pay td.method { font-weight: bold; color: {{ $dark }}; width: 45%; }
        .footer { border-top: 1px solid #E2E8F0; margin-top: 28px; padding-top: 14px; font-size: 10px; color: #666666; line-height: 1.7; }
        .footer .closing-title { font-size: {{ $closingFontSize }}px; font-weight: {{ $closingWeight }}; text-align: {{ $closingAlign }}; color: #222222; margin: 0 0 2px; }
        .footer .closing-sub { text-align: {{ $closingAlign }}; margin: 0 0 8px; }
        .footer .note { text-align: {{ $closingAlign }}; font-weight: bold; }
        .footer .hash { color: #999999; font-size: 9px; word-break: break-all; text-align: center; }
        .watermark { position: absolute; left: 0; right: 0; top: 34%; text-align: center; }
        .watermark img { width: 55%; max-width: 620px; }
    </style>
</head>
<body>
    @if($cfg['watermark_enabled'] && $watermarkUri !== '')
        <div class="watermark"><img src="{{ $watermarkUri }}" alt=""></div>
    @endif

    <table class="layout">
        <tr>
            <td style="padding-left: 0;">
                @if($logoUri !== '')
                    <p class="brand"><img src="{{ $logoUri }}" alt="{{ $cfg['business_name'] }}"></p>
                @else
                    <p class="brand" style="font-size:24px; font-weight:bold; color: {{ $dark }};">{{ strtoupper($cfg['business_name']) }}</p>
                @endif
                <div class="brand-sub" style="font-size:10px; color:#666666; text-transform:uppercase; letter-spacing:2px; margin-top:4px;">{{ __('Enterprise Software Infrastructure') }}</div>
            </td>
            <td style="text-align: right; padding-right: 0;">
                <h1 class="doc-title">{{ __('Invoice') }}</h1>
                <div class="doc-meta">
                    <strong>{{ __('Invoice No:') }}</strong> {{ $invoice->invoice_number }}<br>
                    <strong>{{ __('Date:') }}</strong> {{ document_date($invoice->issue_date) }}<br>
                    <strong>{{ __('Due Date:') }}</strong> {{ document_date($invoice->due_date) }}
                </div>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    <table class="layout">
        <tr>
            <td style="padding-left: 0;">
                <div class="section-title">{{ __('Billed To') }}</div>
                <div class="party">
                    <strong>{{ $school->name }}</strong><br>
                    @if ($school->physical_address){{ $school->physical_address }}<br>@endif
                    @if ($school->phone_number){{ __('Phone:') }} {{ $school->phone_number }}<br>@endif
                    @if ($school->email_address){{ __('Email:') }} {{ $school->email_address }}@endif
                </div>
            </td>
            <td style="padding-right: 0;">
                <div class="section-title">{{ __('From') }}</div>
                <div class="party">
                    <strong>{{ $cfg['business_name'] }}</strong><br>
                    @if ($cfg['from_line_1']){{ $cfg['from_line_1'] }}<br>@endif
                    @if ($cfg['from_line_2']){{ $cfg['from_line_2'] }}<br>@endif
                    @if ($cfg['from_phone']){{ __('Phone:') }} {{ $cfg['from_phone'] }}<br>@endif
                    @if ($cfg['from_email']){{ __('Email:') }} {{ $cfg['from_email'] }}@endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 60%;">{{ __('Description') }}</th>
                <th style="width: 10%; text-align: center;">{{ __('Qty') }}</th>
                <th style="width: 15%; text-align: right;">{{ __('Unit Price') }}</th>
                <th style="width: 15%; text-align: right;">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td style="text-align: right;">${{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align: right;">${{ number_format($item->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="color: #666666;">{{ __('Subscription charges for :plan', ['plan' => $invoice->subscription?->plan?->name ?? __('your plan')]) }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">{{ __('Subtotal') }}</td>
            <td class="value">${{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('Discount') }}</td>
            <td class="value">${{ number_format($invoice->discount, 2) }}</td>
        </tr>
    </table>

    <div class="total-due">
        <div class="td-label">{{ __('Total Due') }}</div>
        <div class="td-value">${{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</div>
    </div>

    <div class="note-box">
        <div class="note-title">{{ $cfg['callout_title'] }}</div>
        {{ $invoice->payment_instructions ?: ($cfg['callout_body'] ?: __('Please use the invoice number as your payment reference.')) }}
        <table class="pay">
            @forelse((array) $cfg['payment_channels'] as $channel)
                <tr>
                    <td class="method">{{ $channel['method'] ?? '' }}</td>
                    <td>{{ $channel['number'] ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td class="method">{{ __('EcoCash') }}</td>
                    <td>0785556855</td>
                </tr>
                <tr>
                    <td class="method">{{ __('Bank (CABS USD Account)') }}</td>
                    <td>1149411511</td>
                </tr>
            @endforelse
        </table>
    </div>

    <div class="footer">
        <p class="note">{{ $cfg['cross_border_notice'] }}</p>
        <p class="closing-title">{{ $cfg['closing_title'] }}</p>
        <p class="closing-sub">{{ $cfg['closing_subtitle'] }}</p>
        <p class="hash">{{ __('Security checksum:') }} {{ $invoice->integrity_hash }}</p>
        <p style="text-align: center;">&copy; {{ date('Y') }} {{ $cfg['business_name'] }}. {{ __('All rights reserved.') }}</p>
    </div>
</body>
</html>