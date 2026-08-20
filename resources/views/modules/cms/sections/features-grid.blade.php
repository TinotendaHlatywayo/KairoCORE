@php
    use Modules\CMS\Services\ComponentRegistry;

    $title = $block['title'] ?? __('Why Choose Our School');
    $features = $block['features'] ?? [];
    $variant = ComponentRegistry::safeVariant('features_grid', $block['variant'] ?? null);
    $icons = ['shield', 'users', 'book', 'heart', 'trophy', 'star', 'bulb', 'check'];
@endphp

@if ($variant === 'icon-list')
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <div class="sc-section-head is-center">
            <span class="sc-eyebrow">{{ __('Our Strengths') }}</span>
            <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
        </div>
        <div class="sc-features-list">
            @foreach($features as $i => $feat)
                <div class="sc-feature-row">
                    <span class="sc-feature-idx" aria-hidden="true">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <h3>{{ $feat['title'] }}</h3>
                    <p>{!! $rich($feat['desc'] ?? '') !!}</p>
                </div>
            @endforeach
        </div>
    </div>
@elseif ($variant === 'split-feature')
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <div class="sc-section-head is-center">
            <span class="sc-eyebrow">{{ __('Our Strengths') }}</span>
            <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
        </div>
        <div class="sc-feature-split">
            <div class="sc-feature-media"@if(! empty($v['imgRadius'])) style="border-radius: {{ $v['imgRadius'] }};"@endif>
                <img src="{{ $block['image_url'] ?? ComponentRegistry::placeholderUrl('campus-quad') }}"
                     alt="{{ $title }}" loading="lazy" decoding="async"
                     style="aspect-ratio: var(--sc-img-ratio, 4 / 3); object-fit: var(--sc-img-fit, cover); object-position: var(--sc-img-pos, center);">
            </div>
            <div class="sc-features-list">
                @foreach($features as $feat)
                    <div class="sc-feature-row">
                        <span class="sc-feature-idx" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <h3>{{ $feat['title'] }}</h3>
                        <p>{!! $rich($feat['desc'] ?? '') !!}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@else
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <div class="sc-section-head is-center">
            <span class="sc-eyebrow">{{ __('Our Strengths') }}</span>
            <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
        </div>
        <div class="sc-grid sc-grid-3">
            @foreach($features as $feat)
                <div class="sc-card sc-card-hover sc-tile">
                    @if(! empty($feat['image']))
                        <span class="sc-tile-media"@if(! empty($v['cardImageRadius'])) style="border-radius: {{ $v['cardImageRadius'] }};"@endif>
                            <img src="{{ $feat['image'] }}" alt="{{ $feat['title'] }}" loading="lazy" decoding="async">
                        </span>
                    @else
                        <span class="sc-tile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 2l8 4v6c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6l8-4z"/>
                            </svg>
                        </span>
                    @endif
                    <h3 style="color: var(--sc-text);">{{ $feat['title'] }}</h3>
                    <p>{!! $rich($feat['desc'] ?? '') !!}</p>
                </div>
            @endforeach
        </div>
    </div>
@endif
