@props(['url'])
@php
    $brand = email_branding();
    $logoUrl = $brand['logo_url'];
    $brandName = $brand['company_name'];
@endphp
<tr>
<td class="header" align="center">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
@if (!empty($logoUrl))
<img src="{{ $logoUrl }}" class="logo" alt="{{ $brandName }} logo" width="140" style="display: block; margin: 0 auto; height: auto; width: auto; max-width: 180px; max-height: 64px;">
@endif
<div class="brand-name" style="display: block; color: #059669; font-size: 13px; font-weight: 700; letter-spacing: 0.14em; margin-top: 12px; text-transform: uppercase;">{{ $brandName }}</div>
</a>
</td>
</tr>
