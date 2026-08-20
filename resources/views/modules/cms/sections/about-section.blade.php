@php
    $title = $block['title'] ?? __('Empowering Curiosity & Ethical Leadership');
    $imageUrl = $block['image_url'] ?? asset('images/School_repository_cover.jpeg');
@endphp

<div class="sc-split {{ $v['alignClass'] }}">
    <div style="display: flex; flex-direction: column; gap: 1.1rem; align-items: {{ $v['align'] === 'left' ? 'flex-start' : ($v['align'] === 'right' ? 'flex-end' : 'center') }};">
        <span class="sc-eyebrow">{{ __('Our Institutional Identity') }}</span>
        <h2 {!! 'style="' . $v['titleStyle'] . '"' !!}>{{ $title }}</h2>
        <div class="sc-muted" style="font-size: 1.02rem; line-height: 1.7;">
            {!! $rich($block['description']) ?: __('Founded with a clear commitment to academic rigor and moral integrity.') !!}
        </div>

        @if(!empty($block['mission']) || !empty($block['vision']))
            <div class="sc-grid sc-grid-2" style="width: 100%; gap: 1rem; margin-top: 0.4rem;">
                <div class="sc-card sc-fact">
                    <h4>{{ __('Our Mission') }}</h4>
                    <p>{!! $rich($block['mission'] ?? '') ?: __('Inspiring academic curiosity and leadership.') !!}</p>
                </div>
                <div class="sc-card sc-fact">
                    <h4 style="color: var(--sc-accent);">{{ __('Our Vision') }}</h4>
                    <p>{!! $rich($block['vision'] ?? '') ?: __('A premier institution setting global standards.') !!}</p>
                </div>
            </div>
        @endif
    </div>

    <div class="sc-media"@if(! empty($v['imgRadius'])) style="border-radius: {{ $v['imgRadius'] }};"@endif>
        <img src="{{ $imageUrl }}"
             alt="{{ $title }}"
             width="1000"
             height="750"
             loading="lazy"
             onerror="this.onerror=null; this.src='{{ asset('images/School_repository_cover.jpeg') }}';"
             style="aspect-ratio: var(--sc-img-ratio, 4 / 3); object-fit: var(--sc-img-fit, cover); object-position: var(--sc-img-pos, center);">
    </div>
</div>
