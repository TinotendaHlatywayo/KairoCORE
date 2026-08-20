@php
    use Modules\CMS\Services\ComponentRegistry;

    $title = $block['title'] ?? __('Campus Life Gallery');
    $images = $block['images'] ?? [];
    $columns = min(6, max(1, (int) ($block['columns'] ?? 3)));
    $variant = ComponentRegistry::safeVariant('gallery', $block['variant'] ?? null);

    $imageUrl = function ($img) {
        return $img['url'] ?? ($img['image']['src'] ?? ($img['src'] ?? ComponentRegistry::placeholderUrl('campus-exterior')));
    };
    $caption = fn ($img) => $img['caption'] ?? '';

    $tileStyle = '';
    if (! empty($v['galleryRatio'])) { $tileStyle .= 'aspect-ratio: '.$v['galleryRatio'].';'; }
    if (! empty($v['galleryRadius'])) { $tileStyle .= 'border-radius: '.$v['galleryRadius'].';'; }
    $tileStyleAttr = $tileStyle !== '' ? ' style="'.$tileStyle.'"' : '';
@endphp

@if ($variant === 'horizontal-scroll')
    <div style="display: flex; flex-direction: column; gap: 1.75rem;">
        <div class="sc-section-head is-center">
            <span class="sc-eyebrow">{{ __('Campus Life') }}</span>
            <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
        </div>
        <div class="sc-gallery-hscroll">
            @foreach($images as $img)
                <figure class="sc-gallery-item"{!! $tileStyleAttr !!}>
                    <img src="{{ $imageUrl($img) }}"
                         alt="{{ $caption($img) ?: __('Campus photo') }}"
                         loading="lazy" decoding="async">
                    @if(! empty($caption($img)))
                        <figcaption class="sc-gallery-caption">{{ $caption($img) }}</figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </div>
@elseif ($variant === 'immersive-grid')
    <div style="display: flex; flex-direction: column; gap: 1.75rem;">
        <div class="sc-section-head is-center">
            <span class="sc-eyebrow">{{ __('Campus Life') }}</span>
            <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
        </div>
        <div class="sc-gallery sc-gallery-immersive">
            @foreach($images as $img)
                <figure class="sc-gallery-item"{!! $tileStyleAttr !!}>
                    <img src="{{ $imageUrl($img) }}"
                         alt="{{ $caption($img) ?: __('Campus photo') }}"
                         loading="lazy" decoding="async">
                    @if(! empty($caption($img)))
                        <figcaption class="sc-gallery-caption">{{ $caption($img) }}</figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </div>
@elseif ($variant === 'featured-image')
    <div style="display: flex; flex-direction: column; gap: 1.75rem;">
        <div class="sc-section-head is-center">
            <span class="sc-eyebrow">{{ __('Campus Life') }}</span>
            <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
        </div>
        <div class="sc-gallery sc-gallery-featured">
            @foreach($images as $img)
                <figure class="sc-gallery-item"{!! $tileStyleAttr !!}>
                    <img src="{{ $imageUrl($img) }}"
                         alt="{{ $caption($img) ?: __('Campus photo') }}"
                         loading="lazy" decoding="async">
                    @if(! empty($caption($img)))
                        <figcaption class="sc-gallery-caption">{{ $caption($img) }}</figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </div>
@else
    <div style="display: flex; flex-direction: column; gap: 1.75rem;">
        <div class="sc-section-head is-center">
            <span class="sc-eyebrow">{{ __('Campus Life') }}</span>
            <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
        </div>
        <div class="sc-gallery" style="grid-template-columns: repeat({{ $columns }}, minmax(0, 1fr));">
            @foreach($images as $img)
                <figure class="sc-gallery-item"{!! $tileStyleAttr !!}>
                    <img src="{{ $imageUrl($img) }}"
                         alt="{{ $caption($img) ?: __('Campus photo') }}"
                         loading="lazy" decoding="async">
                    @if(! empty($caption($img)))
                        <figcaption class="sc-gallery-caption">{{ $caption($img) }}</figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </div>
@endif
