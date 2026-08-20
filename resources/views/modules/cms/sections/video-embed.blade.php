@php
    $title = $block['title'] ?? __('Virtual Campus Video Tour');
@endphp

<div style="display: flex; flex-direction: column; gap: 1.5rem; text-align: center;">
    <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>

    <div class="sc-video" style="aspect-ratio: 16 / 9; max-width: 48rem; margin-inline: auto;">
        @if(!empty($block['video_url']))
            <iframe src="{{ $block['video_url'] }}" title="{{ $title }}" loading="lazy" allowfullscreen style="position: absolute; inset: 0;"></iframe>
        @else
            <div class="sc-video-placeholder">{{ __('Video player preview') }}</div>
        @endif
    </div>
</div>
