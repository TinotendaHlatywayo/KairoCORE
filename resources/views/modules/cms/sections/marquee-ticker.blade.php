@php
    $items = $block['items'] ?? [
        __('Excellence in Academics'),
        __('Global Alumni Network'),
        __('State-of-the-art Facilities'),
        __('Holistic Student Development'),
        __('Award-winning Faculty'),
    ];
    $items = array_values(array_filter(array_map(function ($item) {
        if (is_array($item)) {
            $item = (string) ($item['label'] ?? ($item['text'] ?? ''));
        }

        return trim((string) $item);
    }, $items), fn ($i) => $i !== ''));

    $speed = max(6, min(90, (int) ($block['speed'] ?? 25)));
    $fade = max(0, min(45, (int) ($block['fade_intensity'] ?? 18)));
    $separator = (string) ($block['separator'] ?? '✦');
    $direction = (($block['direction'] ?? 'rightToLeft') === 'leftToRight') ? 'reverse' : 'normal';
    $variant = (($block['variant'] ?? 'two-row') === 'single-row') ? 'single-row' : 'two-row';
    $title = $block['title'] ?? '';
@endphp
@if (! empty($items))
    <section class="sc-marquee-section" aria-label="{{ $title ?: __('Announcements') }}"
             style="--sc-marquee-speed: {{ $speed }}s; --sc-fade: {{ $fade }}%;">
        @if ($title !== '')
            <div class="sc-container" style="padding-top: clamp(2.5rem, 5vw, 3.5rem);">
                <div class="sc-section-head is-center">
                    <h2 class="sc-section-title">{!! e($title) !!}</h2>
                </div>
            </div>
        @endif

        <div class="sc-marquee">
            @for ($row = 1; $row <= ($variant === 'two-row' ? 2 : 1); $row++)
                <div class="sc-marquee-track {{ ($row === 2 || $direction === 'reverse') ? 'is-reverse' : '' }}"
                     @if ($row === 2) style="animation-delay: calc(var(--sc-marquee-speed) * -0.5);" @endif>
                    @foreach(array_merge($items, $items) as $item)
                        <span class="sc-marquee-item">
                            <span class="sc-marquee-dot" aria-hidden="true">{{ $separator }}</span>
                            <span>{{ e($item) }}</span>
                        </span>
                    @endforeach
                </div>
            @endfor
        </div>
    </section>
@endif
