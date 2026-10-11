@php
    $primary = (string) ($cfg['primary_color'] ?? '#EF5F4D');
    $dark    = (string) ($cfg['dark_color'] ?? '#1F2E43');
    $light   = (string) ($cfg['light_fill'] ?? '#F3F5F8');

    $closingFontSize = (int) ($cfg['closing_font_size'] ?? 13);
    $closingWeight   = (string) ($cfg['closing_font_weight'] ?? 'bold');
    $closingAlign    = (string) ($cfg['closing_align'] ?? 'center');
@endphp
<style>
    @page { margin: 0; }
    body { font-family: Helvetica, Arial, sans-serif; color: #222222; margin: 0; padding: 40px 44px; font-size: 11px; line-height: 1.5; }
    table { border-collapse: collapse; }
    td, th { vertical-align: top; }

    /* Header */
    .doc-title { font-size: 32px; font-weight: bold; color: {{ $dark }}; margin: 0; text-align: right; line-height: 1.1; }
    .meta-line { font-size: 11px; color: #222222; text-align: right; line-height: 1.6; }
    .meta-lbl { color: {{ $primary }}; font-weight: bold; }
    .rule { height: 2px; background: {{ $primary }}; margin: 18px 0 22px; font-size: 1px; line-height: 1px; }
    .wordmark { font-size: 34px; font-weight: bold; color: {{ $dark }}; line-height: 1; }
    .wordmark span { color: {{ $primary }}; }

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

    /* Payment details: plain aligned rows, no table lines */
    .pay-title { font-size: 12px; font-weight: bold; text-transform: uppercase; color: {{ $primary }}; margin: 22px 0 6px; }
    table.pay { width: 100%; }
    table.pay td { padding: 5px 0; font-size: 11px; color: #333333; border: none; }
    table.pay td.method { font-weight: bold; color: {{ $dark }}; width: 38%; }

    /* Statement summary boxes + data tables */
    .statement-intro { font-size: 9px; font-weight: bold; text-transform: uppercase; color: {{ $primary }}; margin: 0 0 2px; letter-spacing: 0.5px; }
    table.summary { width: 100%; margin-top: 22px; }
    table.summary td { width: 33.33%; padding: 0 10px 0 0; }
    .box { background: {{ $light }}; border-left: 4px solid {{ $primary }}; padding: 12px 14px; }
    .box .label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: {{ $primary }}; font-weight: bold; }
    .box .value { font-size: 17px; font-weight: bold; margin-top: 6px; color: {{ $dark }}; }
    table.data { width: 100%; margin-top: 16px; }
    table.data th { background: {{ $dark }}; color: #ffffff; font-size: 9px; font-weight: bold; text-transform: uppercase; padding: 9px 12px; text-align: left; }
    table.data td { border-bottom: 1px solid #E2E8F0; padding: 9px 12px; font-size: 11px; color: #444444; }
    table.data td.num, table.data th.num { text-align: right; }
    .outstanding { color: {{ $primary }}; font-weight: bold; }

    /* Receipt */
    .paid-banner { background: {{ $light }}; border-left: 4px solid {{ $primary }}; padding: 14px 18px; margin-bottom: 22px; }
    .paid-banner .h { font-weight: bold; color: {{ $dark }}; font-size: 14px; margin: 0; }
    .paid-banner p { margin: 4px 0 0; color: #666666; font-size: 11px; }
    .amount-box { border-top: 2px solid {{ $dark }}; border-bottom: 2px solid {{ $dark }}; padding: 18px 0; margin-bottom: 24px; text-align: center; }
    .amount-label { color: #666666; font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; }
    .amount-value { color: {{ $primary }}; font-size: 28px; font-weight: bold; margin-top: 4px; }

    /* Footer */
    .notice { margin: 24px 0 0; font-size: 12px; font-weight: bold; color: {{ $dark }}; text-align: center; }
    .closing-title { font-size: {{ $closingFontSize }}px; font-weight: {{ $closingWeight }}; text-align: {{ $closingAlign }}; color: {{ $dark }}; margin: 36px 0 2px; }
    .closing-sub { text-align: {{ $closingAlign }}; margin: 0 0 18px; font-size: 10px; color: #666666; }
    .hash { color: #999999; font-size: 8px; text-align: center; margin: 0 0 2px; word-wrap: break-word; }
    .copy { text-align: center; font-size: 9px; color: #999999; margin: 0; }

    /* Watermark: full logo, centred behind the content (image is already faded) */
    .watermark { position: absolute; left: 0; top: 320px; width: 100%; text-align: center; z-index: -1; }
</style>