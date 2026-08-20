@php
    use Modules\CMS\Services\ComponentRegistry;

    $title = $block['title'] ?? __('What Parents & Alumni Say');
    $testimonials = $block['testimonials'] ?? [];
    $variant = ComponentRegistry::safeVariant('testimonials', $block['variant'] ?? null);
    $count = count($testimonials);
    $first = $testimonials[0] ?? null;
@endphp

@if ($variant === 'large-quote' && $first)
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <div class="sc-section-head is-center">
            <span class="sc-eyebrow">{{ __('Testimonials') }}</span>
            <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
        </div>
        <div class="sc-quote-feature">
            <span class="sc-quote-mark-lg" aria-hidden="true">&ldquo;</span>
            <blockquote class="sc-quote-body-lg">{!! $rich($first['quote'] ?? '') !!}</blockquote>
            <figcaption class="sc-attribution" style="justify-content: center;">
                <span class="sc-avatar" aria-hidden="true" style="width: 2.75rem; height: 2.75rem; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; color: var(--sc-text); background: color-mix(in srgb, var(--sc-text) 14%, transparent);">
                    {{ substr($first['name'] ?? 'U', 0, 1) }}
                </span>
                <span>
                    <span class="sc-attribution-name">{{ $first['name'] ?? __('Parent') }}</span>
                    <span class="sc-attribution-role">{{ $first['role'] ?? '' }}</span>
                </span>
            </figcaption>
        </div>
    </div>
@elseif ($variant === 'split-editorial')
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <div class="sc-section-head is-center">
            <span class="sc-eyebrow">{{ __('Testimonials') }}</span>
            <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
        </div>
        <div>
            @foreach($testimonials as $t)
                <div class="sc-quote-split">
                    <blockquote class="sc-quote-body">{!! $rich($t['quote'] ?? '') !!}</blockquote>
                    <figcaption class="sc-attribution">
                        <span class="sc-avatar" aria-hidden="true" style="width: 2.75rem; height: 2.75rem; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; color: var(--sc-text); background: color-mix(in srgb, var(--sc-text) 14%, transparent);">
                            {{ substr($t['name'] ?? 'U', 0, 1) }}
                        </span>
                        <span>
                            <span class="sc-attribution-name">{{ $t['name'] ?? __('Parent') }}</span>
                            <span class="sc-attribution-role">{{ $t['role'] ?? '' }}</span>
                        </span>
                    </figcaption>
                </div>
            @endforeach
        </div>
    </div>
@elseif ($variant === 'carousel' && $count)
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <div class="sc-section-head is-center">
            <span class="sc-eyebrow">{{ __('Testimonials') }}</span>
            <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
        </div>
        <div class="sc-quote-carousel" x-data="scQuotes({ n: {{ $count }} })">
            @foreach($testimonials as $i => $t)
                <div class="sc-quote-slide" x-show="active === {{ $i }}" x-transition.opacity.duration.500ms>
                    <span class="sc-quote-mark" aria-hidden="true" style="font-size: 4rem;">&ldquo;</span>
                    <blockquote class="sc-quote-body-lg" style="margin-block: 1rem;">{!! $rich($t['quote'] ?? '') !!}</blockquote>
                    <figcaption class="sc-attribution" style="justify-content: center;">
                        <span class="sc-avatar" aria-hidden="true" style="width: 2.75rem; height: 2.75rem; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; color: var(--sc-text); background: color-mix(in srgb, var(--sc-text) 14%, transparent);">
                            {{ substr($t['name'] ?? 'U', 0, 1) }}
                        </span>
                        <span>
                            <span class="sc-attribution-name">{{ $t['name'] ?? __('Parent') }}</span>
                            <span class="sc-attribution-role">{{ $t['role'] ?? '' }}</span>
                        </span>
                    </figcaption>
                </div>
            @endforeach
            <div class="sc-quote-controls">
                <button type="button" class="sc-btn sc-btn-ghost sc-btn-sm" @click="step(-1)" aria-label="{{ __('Previous testimonial') }}">&larr;</button>
                @foreach($testimonials as $i => $t)
                    <button type="button" class="sc-quote-dot" :class="{ 'is-active': active === {{ $i }} }"
                            @click="active = {{ $i }}" :aria-label="'{{ __('Go to testimonial') }} ' + {{ $i + 1 }}"></button>
                @endforeach
                <button type="button" class="sc-btn sc-btn-ghost sc-btn-sm" @click="step(1)" aria-label="{{ __('Next testimonial') }}">&rarr;</button>
            </div>
        </div>
    </div>
@elseif ($variant === 'image-led')
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <div class="sc-section-head is-center">
            <span class="sc-eyebrow">{{ __('Testimonials') }}</span>
            <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
        </div>
        <div class="sc-grid sc-grid-2">
            @foreach($testimonials as $t)
                <figure class="sc-card sc-card-hover sc-quote">
                    <img class="sc-quote-img" src="{{ $t['image'] ?? ComponentRegistry::placeholderUrl('staff-silhouette') }}"
                         alt="" loading="lazy" width="52" height="52">
                    <blockquote class="sc-quote-body">{!! $rich($t['quote'] ?? '') !!}</blockquote>
                    <figcaption class="sc-attribution">
                        <span class="sc-attribution-name">{{ $t['name'] ?? __('Parent') }}</span>
                        <span class="sc-attribution-role">{{ $t['role'] ?? '' }}</span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
@else
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        <div class="sc-section-head is-center">
            <span class="sc-eyebrow">{{ __('Testimonials') }}</span>
            <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
        </div>
        <div class="sc-grid sc-grid-2">
            @foreach($testimonials as $t)
                <figure class="sc-card sc-card-hover sc-quote">
                    <span class="sc-quote-mark" aria-hidden="true">&ldquo;</span>
                    <blockquote class="sc-quote-body">{!! $rich($t['quote'] ?? '') !!}</blockquote>
                    <figcaption class="sc-attribution">
                        <span class="sc-avatar" aria-hidden="true" style="width: 2.75rem; height: 2.75rem; display: inline-flex; align-items: center; justify-content: center; font-weight: 800; color: var(--sc-text); background: color-mix(in srgb, var(--sc-text) 14%, transparent);">
                            {{ substr($t['name'] ?? 'U', 0, 1) }}
                        </span>
                        <span>
                            <span class="sc-attribution-name">{{ $t['name'] ?? __('Parent') }}</span>
                            <span class="sc-attribution-role">{{ $t['role'] ?? '' }}</span>
                        </span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
@endif

@if ($variant === 'carousel' && $count)
<script>
    function scQuotes(cfg) {
        return {
            active: 0,
            n: cfg.n,
            step(dir) {
                this.active = (((this.active + dir) % this.n) + this.n) % this.n;
            }
        };
    }
</script>
@endif
