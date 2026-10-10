<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $cfg = platform_document_config();
        $brand = email_branding();
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
    <title>SaaS Subscription Payment Receipt: {{ $receipt->receipt_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { position: relative; font-family: Arial, Helvetica, sans-serif; color: #222222; margin: 0; padding: 28px; line-height: 1.5; font-size: 12px; }
        .brand { margin: 0; }
        .brand img { height: 50px; width: auto; }
        .brand-sub { font-size: 10px; color: #666666; text-transform: uppercase; letter-spacing: 2px; margin-top: 3px; }
        .doc-title { font-size: 22px; font-weight: bold; color: {{ $primary }}; margin: 0; text-transform: uppercase; letter-spacing: 2px; text-align: right; }
        .doc-meta { font-size: 11px; color: #444444; text-align: right; margin-top: 6px; }
        .rule { height: 3px; background: {{ $dark }}; margin: 16px 0 22px; }
        .paid-banner { background: {{ $light }}; border-left: 4px solid {{ $primary }}; padding: 14px 18px; margin-bottom: 22px; }
        .paid-banner .h { font-weight: bold; color: {{ $dark }}; font-size: 14px; margin: 0; }
        .paid-banner p { margin: 4px 0 0; color: #666666; font-size: 11px; }
        .amount-box { border-top: 2px solid {{ $dark }}; border-bottom: 2px solid {{ $dark }}; padding: 18px 0; margin-bottom: 24px; text-align: center; }
        .amount-label { color: #666666; font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; }
        .amount-value { color: {{ $primary }}; font-size: 28px; font-weight: bold; margin-top: 4px; }
        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; color: {{ $dark }}; margin-bottom: 6px; }
        table.layout { width: 100%; border-collapse: collapse; }
        table.layout > tbody > tr > td { vertical-align: top; width: 50%; padding: 0 10px 0 0; }
        .details { font-size: 12px; line-height: 1.8; color: #444444; }
        .details strong { color: #222222; }
        .footer { border-top: 1px solid #E2E8F0; margin-top: 28px; padding-top: 14px; font-size: 10px; color: #666666; text-align: center; line-height: 1.7; }
        .footer .closing-title { font-size: {{ $closingFontSize }}px; font-weight: {{ $closingWeight }}; text-align: {{ $closingAlign }}; color: #222222; margin: 0 0 2px; }
        .footer .closing-sub { text-align: {{ $closingAlign }}; margin: 0 0 8px; }
        .footer .hash { color: #999999; font-size: 9px; word-break: break-all; }
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
            <td style="padding-right: 10px;">
                @if($logoUri !== '')
                    <p class="brand"><img src="{{ $logoUri }}" alt="{{ $cfg['business_name'] }}"></p>
                @else
                    <p class="brand" style="font-size:22px; font-weight:bold; color: {{ $dark }};">{{ strtoupper($cfg['business_name']) }}</p>
                @endif
                <div class="brand-sub">{{ __('Subscription Receipt') }}</div>
            </td>
            <td style="text-align: right; padding-right: 0;">
                <h1 class="doc-title">{{ __('Payment Receipt') }}</h1>
                <div class="doc-meta"># {{ $receipt->receipt_number }}</div>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    <div class="paid-banner">
        <p class="h">{{ __('Payment Confirmed & Settled') }}</p>
        <p>{{ __('Thank you for your payment. Your subscription status has been updated and remains active.') }}</p>
    </div>

    <div class="amount-box">
        <div class="amount-label">{{ __('Amount Paid') }}</div>
        <div class="amount-value">${{ number_format($receipt->amount_paid, 2) }} {{ $receipt->currency }}</div>
    </div>

    <table class="layout">
        <tr>
            <td style="padding-right: 10px;">
                <div class="section-title">{{ __('Payer Details') }}</div>
                <div class="details">
                    <strong>{{ $receipt->school->name }}</strong><br>
                    {{ __('School ID:') }} #SCH-{{ str_pad($receipt->school_id, 5, '0', STR_PAD_LEFT) }}
                </div>
            </td>
            <td style="padding-right: 0;">
                <div class="section-title">{{ __('Transaction Details') }}</div>
                <div class="details">
                    <strong>{{ __('Receipt Date:') }}</strong> {{ document_date($receipt->issued_at, 'd M Y H:i') }}<br>
                    <strong>{{ __('Reference:') }}</strong> {{ $receipt->transaction?->transaction_reference ?? '—' }}<br>
                    <strong>{{ __('Invoice Cleared:') }}</strong> {{ $receipt->invoice?->invoice_number ?? '—' }}
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p class="closing-title">{{ $cfg['closing_title'] }}</p>
        <p class="closing-sub">{{ $cfg['closing_subtitle'] }}</p>
        <p>{{ __('This is a system-generated receipt.') }}</p>
        <p class="hash">{{ __('Verification checksum:') }} {{ $receipt->verification_token }}</p>
        <p>&copy; {{ date('Y') }} {{ $cfg['business_name'] }}. {{ __('All rights reserved.') }}</p>
    </div>
</body>
</html>