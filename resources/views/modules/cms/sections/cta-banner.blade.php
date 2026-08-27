@php
    $title = $block['title'] ?? __('Ready to Begin Your Educational Journey?');
@endphp

<div class="sc-cta-band">
    <span class="sc-cta-orb sc-cta-orb-1" aria-hidden="true"></span>
    <span class="sc-cta-orb sc-cta-orb-2" aria-hidden="true"></span>

    <div class="sc-section-head is-center" style="margin-bottom: 0;">
        <h2 style="font-weight: 800; {{ $v['titleStyle'] ?? '' }}">{{ $title }}</h2>
        <p>{!! $rich($block['description']) ?: __('Enroll online today to secure an academic placement.') !!}</p>
        <a href="{{ $block['cta_url'] ?? '/apply-online' }}" class="sc-btn sc-btn-light sc-btn-lg">
            <span>{{ $block['cta_text'] ?? __('Apply Online') }}</span>
            <span class="sc-btn-arrow" aria-hidden="true">→</span>
        </a>
    </div>
</div>
