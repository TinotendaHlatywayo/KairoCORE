{{-- Shared print footer for finance documents.
     Expects: $f, $financeTheme, $signatureLeft, $signatureRight, $qrUrl, $fallbackFooter. --}}
@php
    $qrPosition = $f['qr_position'] ?? 'right';
    $qrSize = (int) ($f['qr_size'] ?? 70);
    $qrAlign = $qrPosition === 'left' ? 'left' : ($qrPosition === 'center' ? 'center' : 'right');
    $showSignatures = ($f['show_signatures'] ?? false) && ($signatureLeft || $signatureRight);
    $showQr = ($f['show_qr'] ?? false) && $qrUrl;
@endphp
@if($showSignatures || $showQr)
<div class="doc-footer">
    @if($showSignatures)
        @if($signatureLeft)
            <div class="signature-line" style="float: left; width: 45%; margin-right: 5%; margin-top: 8px;">{{ $signatureLeft }}</div>
        @endif
        @if($signatureRight)
            <div class="signature-line" style="float: left; width: 45%; margin-top: 8px;">{{ $signatureRight }}</div>
        @endif
    @endif
    @if($showQr)
        <div class="qr-block" style="{{ $qrPosition === 'center' ? 'float: none; margin: 0 auto;' : 'float: ' . $qrAlign . ';' }} text-align: {{ $qrAlign }}; width: {{ $qrSize + 24 }}px;">
            <img class="qr-image" src="https://api.qrserver.com/v1/create-qr-code/?size={{ $qrSize }}x{{ $qrSize }}&data={{ urlencode($qrUrl) }}" style="width: {{ $qrSize }}px; height: {{ $qrSize }}px; display: block; margin: 0 auto;">
            <div class="qr-caption">{{ __('Scan to Verify') }}</div>
        </div>
    @endif
</div>
@endif

@if(! empty($f['extra_text']))
    <div class="doc-extra-text">{!! nl2br(e($f['extra_text'])) !!}</div>
@elseif($fallbackFooter)
    <div class="doc-extra-text">{{ $fallbackFooter }}</div>
@endif