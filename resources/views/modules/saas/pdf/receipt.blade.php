<!DOCTYPE html>
<html lang="en">
<head>
    @php
        $cfg = platform_document_config(null, 'receipt');
        $brand = email_branding();
        $logoUri = document_logo_data_uri();
        $watermarkUri = document_watermark_data_uri();
    @endphp
    <meta charset="UTF-8">
    <title>SaaS Subscription Payment Receipt: {{ $receipt->receipt_number }}</title>
    @include('modules.saas.pdf.partials.styles', ['cfg' => $cfg])
</head>
<body>
    @include('modules.saas.pdf.partials.watermark', ['cfg' => $cfg, 'watermarkUri' => $watermarkUri])

    @include('modules.saas.pdf.partials.header', [
        'cfg' => $cfg,
        'logoUri' => $logoUri,
        'documentTitle' => __('Payment Receipt'),
        'documentMeta' => [
            __('Receipt No:') => $receipt->receipt_number,
            __('Date:') => document_date($receipt->issued_at, 'd M Y H:i'),
        ],
    ])

    <div class="paid-banner">
        <p class="h">{{ __('Payment Confirmed & Settled') }}</p>
        <p>{{ __('Thank you for your payment. Your subscription status has been updated and remains active.') }}</p>
    </div>

    <div class="amount-box">
        <div class="amount-label">{{ __('Amount Paid') }}</div>
        <div class="amount-value">${{ number_format($receipt->amount_paid, 2) }} {{ $receipt->currency }}</div>
    </div>

    <table width="100%">
        <tr>
            <td width="50%" style="padding-right: 18px;">
                <div class="section-title">{{ __('Payer Details') }}</div>
                <div class="party">
                    <span class="party-name">{{ $receipt->school->name ?? '' }}</span><br>
                    {{ __('School ID:') }} #SCH-{{ str_pad($receipt->school_id, 5, '0', STR_PAD_LEFT) }}
                </div>
            </td>
            <td width="50%">
                <div class="section-title">{{ __('Transaction Details') }}</div>
                <div class="party">
                    <strong>{{ __('Receipt Date:') }}</strong> {{ document_date($receipt->issued_at, 'd M Y H:i') }}<br>
                    <strong>{{ __('Transaction Ref:') }}</strong> {{ $receipt->transaction?->transaction_reference ?? '—' }}<br>
                    <strong>{{ __('Invoice #:') }}</strong> {{ $receipt->invoice?->invoice_number ?? '—' }}
                </div>
            </td>
        </tr>
    </table>

    @if(!empty($cfg['callout_title']) || !empty($cfg['callout_body']))
        <div class="note-box">
            @if(!empty($cfg['callout_title']))<div class="note-title">{{ $cfg['callout_title'] }}</div>@endif
            {!! nl2br(e($cfg['callout_body'] ?? '')) !!}
        </div>
    @endif

    @include('modules.saas.pdf.partials.footer', [
        'cfg' => $cfg,
        'checksumLabel' => __('Security checksum:'),
        'checksumValue' => $receipt->verification_token,
    ])
</body>
</html>
