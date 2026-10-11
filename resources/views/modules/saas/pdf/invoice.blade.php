<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $cfg = platform_document_config();
        $brand = email_branding();
        $school = $invoice->school;

        // KairoCORE brand palette (fixed to match the approved invoice design)
        $primary = '#EF5F4D';   // coral
        $dark    = '#1F2E43';   // navy
        $light   = '#F3F5F8';   // soft grey-blue

        $closingFontSize = (int) ($cfg['closing_font_size'] ?? 15);
        $closingWeight   = (string) ($cfg['closing_font_weight'] ?? 'bold');
        $closingAlign    = (string) ($cfg['closing_align'] ?? 'center');
        $logoUri      = document_logo_data_uri();
        $watermarkUri = document_watermark_data_uri();
        $discount     = (float) ($invoice->discount ?? 0);
        $calloutBody  = $invoice->payment_instructions ?: ($cfg['callout_body'] ?? '');
    @endphp
    <meta charset="UTF-8">
    <title>SaaS Subscription Invoice: {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 0; }
        body { font-family: Helvetica, Arial, sans-serif; color: #222222; margin: 0; padding: 40px 44px; font-size: 11px; line-height: 1.5; }
        table { border-collapse: collapse; }
        td, th { vertical-align: top; }

        /* Header */
        .doc-title { font-size: 32px; font-weight: bold; color: {{ $dark }}; margin: 0; text-align: right; }
        .meta-line { font-size: 11px; color: #222222; text-align: right; line-height: 1.6; }
        .meta-lbl { color: {{ $primary }}; font-weight: bold; }
        .rule { height: 2px; background: {{ $primary }}; margin: 18px 0 22px; font-size: 1px; line-height: 1px; }

        /* Parties */
        .section-title { font-size: 9px; font-weight: bold; text-transform: uppercase; color: {{ $primary }}; margin-bottom: 5px; }
        .party { font-size: 11px; line-height: 1.6; color: #444444; }
        .party-name { font-size: 13px; font-weight: bold; color: {{ $dark }}; }

        /* Items */
        table.items { width: 100%; margin-top: 24px; }
        table.items th { background: {{ $dark }}; color: #ffffff; font-size: 9px; font-weight: bold; text-transform: uppercase; padding: 9px 12px; text-align: left; }
        table.items td { border-bottom: 1px solid #E2E8F0; padding: 11px 12px; font-size: 11px; color: #444444; }
        .item-title { font-weight: bold; color: {{ $dark }}; font-size: 11px; }
        .item-desc { font-size: 10px; color: #666666; margin-top: 2px; }

        /* Totals */
        table.totals { width: 100%; }
        table.totals td { padding: 7px 12px; font-size: 11px; font-weight: bold; color: {{ $dark }}; }
        table.totals td.value { text-align: right; }
        table.totals tr.grand td { background: {{ $primary }}; color: #ffffff; font-size: 11px; text-transform: uppercase; padding: 9px 12px; }

        /* Callout */
        .note-box { background: {{ $light }}; border-left: 4px solid {{ $primary }}; padding: 12px 16px; margin-top: 26px; font-size: 10px; color: #666666; }
        .note-title { font-weight: bold; font-size: 12px; color: {{ $dark }}; margin-bottom: 3px; }

        /* Payment details */
        .pay-title { font-size: 12px; font-weight: bold; text-transform: uppercase; color: {{ $primary }}; margin: 22px 0 6px; }
        table.pay { width: 100%; }
        table.pay td { padding: 8px 10px; font-size: 11px; color: #333333; border: 1px solid #D5DAE1; }
        table.pay td.method { font-weight: bold; color: #222222; width: 38%; background: {{ $light }}; }

        /* Footer */
        .notice { margin: 24px 0 0; font-size: 12px; font-weight: bold; color: {{ $dark }}; text-align: center; }
        .closing-title { font-size: {{ $closingFontSize }}px; font-weight: {{ $closingWeight }}; text-align: {{ $closingAlign }}; color: {{ $dark }}; margin: 36px 0 2px; }
        .closing-sub { text-align: {{ $closingAlign }}; margin: 0 0 18px; font-size: 10px; color: #666666; }
        .hash { color: #999999; font-size: 8px; text-align: center; margin: 0 0 2px; word-wrap: break-word; }
        .copy { text-align: center; font-size: 9px; color: #999999; margin: 0; }

        /* Watermark (sits behind content) */
        .watermark { position: absolute; left: 0; top: 300px; width: 100%; text-align: center; z-index: -1; }
        .watermark img { width: 480px; height: auto; }
    </style>
</head>
<body>
    @if(!empty($cfg['watermark_enabled']) && $watermarkUri !== '')
        <div class="watermark"><img src="{{ $watermarkUri }}" alt=""></div>
    @endif

    {{-- Header --}}
    <table width="100%">
        <tr>
            <td width="50%" style="vertical-align: middle;">
                @if($logoUri !== '')
                    <img src="{{ $logoUri }}" alt="{{ $cfg['business_name'] }}" width="150" height="66">
                @else
                    <div style="font-size: 26px; font-weight: bold; color: {{ $dark }};">{{ $cfg['business_name'] }}</div>
                @endif
            </td>
            <td width="50%">
                <h1 class="doc-title">{{ __('Invoice') }}</h1>
                <div class="meta-line" style="margin-top: 10px;"><span class="meta-lbl">{{ __('Invoice No:') }}</span> {{ $invoice->invoice_number }}</div>
                <div class="meta-line"><span class="meta-lbl">{{ __('Date:') }}</span> {{ document_date($invoice->issue_date) }}</div>
                <div class="meta-line"><span class="meta-lbl">{{ __('Due Date:') }}</span> {{ document_date($invoice->due_date) }}</div>
            </td>
        </tr>
    </table>

    <div class="rule">&nbsp;</div>

    {{-- Billed To / From --}}
    <table width="100%">
        <tr>
            <td width="50%">
                <div class="section-title">{{ __('Billed To') }}</div>
                <div class="party">
                    <span class="party-name">{{ $school->name }}</span><br>
                    @if ($school->physical_address){{ $school->physical_address }}<br>@endif
                    @if ($school->phone_number){{ __('Phone:') }} {{ $school->phone_number }}<br>@endif
                    @if ($school->email_address){{ __('Email:') }} {{ $school->email_address }}@endif
                </div>
            </td>
            <td width="50%">
                <div class="section-title">{{ __('From') }}</div>
                <div class="party">
                    <span class="party-name">{{ $cfg['business_name'] }}</span><br>
                    @if (!empty($cfg['from_line_1'])){{ $cfg['from_line_1'] }}<br>@endif
                    @if (!empty($cfg['from_line_2'])){{ $cfg['from_line_2'] }}<br>@endif
                    @if (!empty($cfg['from_phone'])){{ __('Phone:') }} {{ $cfg['from_phone'] }}<br>@endif
                    @if (!empty($cfg['from_email'])){{ __('Email:') }} {{ $cfg['from_email'] }}@endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Line items --}}
    <table class="items">
        <thead>
            <tr>
                <th width="52%">{{ __('Description') }}</th>
                <th width="10%" style="text-align: center;">{{ __('Qty') }}</th>
                <th width="19%" style="text-align: right;">{{ __('Unit Price') }}</th>
                <th width="19%" style="text-align: right;">{{ __('Amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->items as $item)
                @php
                    $raw = trim((string) $item->description);
                    $lines = preg_split('/\r\n|\r|\n/', $raw, 2);
                    $title = $lines[0];
                    $desc  = $lines[1] ?? '';
                    // "Title [details]" -> bold title + grey details underneath
                    if ($desc === '' && preg_match('/^(.*?)\s*\[(.+)\]\s*$/s', $title, $m)) {
                        $title = trim($m[1]);
                        $desc  = trim($m[2]);
                    }
                @endphp
                <tr>
                    <td>
                        <div class="item-title">{{ $title }}</div>
                        @if($desc !== '')<div class="item-desc">{!! nl2br(e($desc)) !!}</div>@endif
                    </td>
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td style="text-align: right;">${{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align: right; font-weight: bold; color: {{ $dark }};">${{ number_format($item->total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="color: #666666;">{{ __('Subscription charges for :plan', ['plan' => $invoice->subscription?->plan?->name ?? __('your plan')]) }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totals --}}
    <table width="100%" style="margin-top: 14px;">
        <tr>
            <td width="58%">&nbsp;</td>
            <td width="42%">
                <table class="totals">
                    <tr>
                        <td>{{ __('Subtotal') }}</td>
                        <td class="value">${{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    @if($discount > 0)
                        <tr>
                            <td>{{ __('Discount') }}</td>
                            <td class="value">-${{ number_format($discount, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="grand">
                        <td>{{ __('Total Due') }}</td>
                        <td class="value">${{ number_format($invoice->total, 2) }}@if(!empty($invoice->currency) && $invoice->currency !== 'USD') {{ $invoice->currency }}@endif</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Callout --}}
    @if(!empty($cfg['callout_title']) || $calloutBody !== '')
        <div class="note-box">
            @if(!empty($cfg['callout_title']))<div class="note-title">{{ $cfg['callout_title'] }}</div>@endif
            {{ $calloutBody }}
        </div>
    @endif

    {{-- Payment details --}}
    <div class="pay-title">{{ __('Payment Details') }}</div>
    <table class="pay">
        @forelse((array) ($cfg['payment_channels'] ?? []) as $channel)
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

    @if(!empty($cfg['cross_border_notice']))
        <p class="notice">{{ $cfg['cross_border_notice'] }}</p>
    @endif

    {{-- Footer --}}
    <p class="closing-title">{{ !empty($cfg['closing_title']) ? $cfg['closing_title'] : __('Thank you for your business!') }}</p>
    <p class="closing-sub">{{ !empty($cfg['closing_subtitle']) ? $cfg['closing_subtitle'] : __('Please use the invoice number as your payment reference.') }}</p>
    <p class="hash">{{ __('Security checksum:') }} {{ $invoice->integrity_hash }}</p>
    <p class="copy">&copy; {{ date('Y') }} {{ $cfg['business_name'] }}. {{ __('All rights reserved.') }}</p>
</body>
</html>
