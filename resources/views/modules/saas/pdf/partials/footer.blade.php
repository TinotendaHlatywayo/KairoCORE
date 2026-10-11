@if(!empty($cfg['cross_border_notice']))
    <p class="notice">{{ $cfg['cross_border_notice'] }}</p>
@endif

<p class="closing-title">{{ !empty($cfg['closing_title']) ? $cfg['closing_title'] : __('Thank you for your business!') }}</p>
<p class="closing-sub">{{ !empty($cfg['closing_subtitle']) ? $cfg['closing_subtitle'] : __('Please use the invoice number as your payment reference.') }}</p>
@if(!empty($checksumValue))
    <p class="hash">{{ $checksumLabel ?? __('Security checksum:') }} {{ $checksumValue }}</p>
@endif
<p class="copy">&copy; {{ date('Y') }} {{ $cfg['business_name'] ?? '' }}. {{ __('All rights reserved.') }}</p>