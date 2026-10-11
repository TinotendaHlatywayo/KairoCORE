@php
    [$logoW, $logoH] = document_logo_dimensions();
    $logoW = (int) max(1, round(92 * ($logoW / max(1, $logoH))));
@endphp
@if(!empty($logoUri))
    <img src="{{ $logoUri }}" alt="{{ $cfg['business_name'] ?? 'Kairo CORE' }}" width="{{ $logoW }}" height="92" style="display: block;">
@else
    <div class="wordmark" style="padding-top: 8px;">Kairo<br><span>CORE</span></div>
@endif