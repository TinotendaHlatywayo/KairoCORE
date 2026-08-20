@php
    $title = $block['title'] ?? __('Academics & Curricula');
    $items = $block['items'] ?? [];
@endphp

<div style="display: flex; flex-direction: column; gap: 2rem;">
    <div class="sc-section-head is-center">
        <span class="sc-eyebrow">{{ __('Academic Pathways') }}</span>
        <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
        <p class="sc-section-lead">{!! $rich($block['description']) ?: __('Comprehensive learning pathways designed for intellectual excellence.') !!}</p>
    </div>

    <div class="sc-grid sc-grid-3">
        @foreach($items as $item)
            <div class="sc-card sc-card-hover sc-tile sc-tile-numbered">
                <span class="sc-tile-index" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                <span class="sc-tile-icon" aria-hidden="true">🎓</span>
                <h3 style="color: var(--sc-text);">{{ $item['title'] }}</h3>
                <p>{!! $rich($item['desc'] ?? '') !!}</p>
            </div>
        @endforeach
    </div>
</div>
