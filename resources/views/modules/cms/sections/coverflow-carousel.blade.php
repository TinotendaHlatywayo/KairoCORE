@php
    use Modules\CMS\Services\ComponentRegistry;
    use Modules\CMS\Services\CmsTemplateService;

    // ── Slides: accept the reference component's shape (image.src + title),
    //    a plain string image, or the legacy gallery shape. ──
    $rawSlides = $block['slides'] ?? [];
    $list = [];
    foreach ($rawSlides as $slide) {
        if (is_string($slide)) {
            $list[] = ['image' => ['src' => $slide], 'title' => ''];
            continue;
        }
        $img = $slide['image'] ?? null;
        if (is_array($img)) {
            $src = $img['src'] ?? ($img['url'] ?? '');
        } else {
            $src = $slide['url'] ?? $img ?? '';
        }
        $list[] = [
            'image' => ['src' => $src, 'alt' => $slide['alt'] ?? ($slide['image']['alt'] ?? '')],
            'title' => $slide['title'] ?? '',
        ];
    }
    if (empty($list)) {
        $list = [
            ['image' => ['src' => ComponentRegistry::placeholderUrl('campus-exterior')], 'title' => 'Main Campus'],
            ['image' => ['src' => ComponentRegistry::placeholderUrl('library')], 'title' => 'Learning Commons'],
            ['image' => ['src' => ComponentRegistry::placeholderUrl('science-lab')], 'title' => 'Innovation Labs'],
            ['image' => ['src' => ComponentRegistry::placeholderUrl('sports-field')], 'title' => 'Sports Complex'],
            ['image' => ['src' => ComponentRegistry::placeholderUrl('assembly-hall')], 'title' => 'Assembly Hall'],
        ];
    }

    // ── Component props (block → reference component param names) ──
    $cardWidth = max(220, min(640, (int) ($block['card_width'] ?? 380)));
    $cardHeight = max(160, min(640, (int) ($block['card_height'] ?? 300)));
    $radius = max(0, min(60, (int) ($block['radius'] ?? 12)));
    $tilt = max(0, min(40, (int) ($block['tilt'] ?? 12)));
    $sideTilt = max(0, min(40, (int) ($block['side_tilt'] ?? 8)));
    $gap = max(2, min(24, (int) ($block['gap'] ?? 8)));
    $opacity = max(0, min(100, (int) ($block['opacity'] ?? 60)));
    $autoplay = (bool) ($block['autoplay'] ?? false);
    $autoplayDirection = in_array($block['autoplay_direction'] ?? 'rightToLeft', ['rightToLeft', 'leftToRight'], true) ? $block['autoplay_direction'] : 'rightToLeft';
    $transitionDuration = max(0.2, min(2.0, (float) ($block['transition_duration'] ?? 0.6)));
    $transitionDelay = max(0.5, min(8.0, (float) ($block['transition_delay'] ?? 2.5)));
    $showTitle = (bool) ($block['show_title'] ?? true);
    $titlePosition = in_array($block['title_position'] ?? 'bottomLeft', ['bottomLeft', 'bottomRight', 'topLeft', 'topRight'], true) ? $block['title_position'] : 'bottomLeft';
    $titleColor = CmsTemplateService::safeHex($block['title_color'] ?? '#ffffff', '#ffffff');
    $titlePadLeft = (int) ($block['title_padding_left'] ?? 22);
    $titlePadRight = (int) ($block['title_padding_right'] ?? 22);
    $titlePadTop = (int) ($block['title_padding_top'] ?? 24);
    $titlePadBottom = (int) ($block['title_padding_bottom'] ?? 24);

    $slideCount = count($list);
    $clampedRadius = max(0, min(20, (int) round($radius / 4)));
    $minDimension = min($cardWidth, $cardHeight);
    $effectiveRadius = ($clampedRadius / 20) * ($minDimension / 2);
    $dimOpacity = 1 - ($opacity / 100);
    $isTop = in_array($titlePosition, ['topLeft', 'topRight'], true);
    $isRight = in_array($titlePosition, ['topRight', 'bottomRight'], true);

    $heading = $block['title'] ?? 'Campus in Focus';
    $subtitle = $block['subtitle'] ?? 'Swipe, click or use the arrow keys to tour our facilities.';
@endphp
<section class="sc-coverflow-stage" aria-label="{{ $heading }}" x-data="scCoverflow({
    n: {{ $slideCount }},
    autoplay: {{ $autoplay ? 'true' : 'false' }},
    autoplayDir: '{{ $autoplayDirection }}',
    delay: {{ max(0.5, $transitionDelay) }},
    duration: {{ $transitionDuration }},
    gap: {{ $gap }},
    tilt: {{ $tilt }},
    sideTilt: {{ $sideTilt }},
    opacity: {{ $dimOpacity }},
    cardWidth: {{ $cardWidth }},
    cardHeight: {{ $cardHeight }},
    radius: {{ $effectiveRadius }},
    showTitle: {{ $showTitle ? 'true' : 'false' }},
    titlePosition: '{{ $titlePosition }}',
    titleColor: '{{ $titleColor }}',
    isTop: {{ $isTop ? 'true' : 'false' }},
    isRight: {{ $isRight ? 'true' : 'false' }},
    titlePadL: {{ $titlePadLeft }},
    titlePadR: {{ $titlePadRight }},
    titlePadT: {{ $titlePadTop }},
    titlePadB: {{ $titlePadBottom }},
})" x-init="init()" x-cloak
      data-sc-card-width="{{ $cardWidth }}"
      data-sc-card-height="{{ $cardHeight }}">
    <div class="sc-container">
        <div class="sc-section-head is-center">
            <span class="sc-eyebrow">{{ __('Explore') }}</span>
            <h2 class="sc-section-title" {!! $v['titleStyle'] ?? '' !!}>{!! e($heading) !!}</h2>
            <p>{{ $subtitle }}</p>
        </div>
    </div>

    <div class="sc-coverflow-wrap">
        <div class="sc-coverflow" tabindex="0" role="group" aria-roledescription="carousel"
             aria-label="{{ $heading }}"
             @keydown.right.prevent="step(1)"
             @keydown.left.prevent="step(-1)">
            <div class="sc-coverflow-slides" x-ref="stage">
                @foreach ($list as $index => $slide)
                    <div class="sc-coverflow-card" :style="cardStyle({{ $index }})"
                         @click="handleCardClick({{ $index }})"
                         role="group" aria-roledescription="slide"
                         :aria-label="'{{ $slide['title'] ? str_replace("'", "\'", $slide['title']) : 'Slide ' . ($index + 1) }}'">
                        @if (! empty($slide['image']['src']))
                            <img src="{{ $slide['image']['src'] }}"
                                 alt="{{ $slide['image']['alt'] ?? '' }}"
                                 loading="lazy" decoding="async" draggable="false"
                                 class="sc-coverflow-img">
                        @endif

                        @if ($showTitle && ! empty($slide['title']))
                            <div class="sc-coverflow-shade" :class="{ 'is-top': isTop }"></div>
                            <div class="sc-coverflow-title" :class="{ 'is-top': isTop, 'is-right': isRight }"
                                 style="left: {{ $titlePadLeft }}px; right: {{ $titlePadRight }}px;">
                                <span>{!! nl2br(e($slide['title'])) !!}</span>
                            </div>
                        @endif

                        <div class="sc-coverflow-dim" :style="dimStyle({{ $index }})"></div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="sc-coverflow-controls">
            <button type="button" class="sc-btn sc-btn-ghost sc-btn-sm" @click="step(-1)" :disabled="lock" aria-label="{{ __('Previous slide') }}">&larr;</button>
            <span class="sc-coverflow-counter"><span x-text="active + 1"></span> / {{ $slideCount }}</span>
            <button type="button" class="sc-btn sc-btn-ghost sc-btn-sm" @click="step(1)" :disabled="lock" aria-label="{{ __('Next slide') }}">&rarr;</button>
        </div>
    </div>
</section>

<script>
    function scCoverflow(cfg) {
        return {
            active: 0,
            n: cfg.n,
            autoplay: cfg.autoplay,
            autoplayDir: cfg.autoplayDir,
            delay: cfg.delay * 1000,
            duration: cfg.duration * 1000,
            gap: cfg.gap,
            tilt: cfg.tilt,
            sideTilt: cfg.sideTilt,
            dimOpacity: cfg.opacity,
            cardWidth: cfg.cardWidth,
            cardHeight: cfg.cardHeight,
            radius: cfg.radius,
            showTitle: cfg.showTitle,
            titlePosition: cfg.titlePosition,
            titleColor: cfg.titleColor,
            isTop: cfg.isTop,
            isRight: cfg.isRight,
            titlePadL: cfg.titlePadL,
            titlePadR: cfg.titlePadR,
            titlePadT: cfg.titlePadT,
            titlePadB: cfg.titlePadB,
            lock: false,
            interval: null,
            scale: 1,

            reducedMotion() {
                return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            },

            syncConfig() {
                var el = this.$el;
                if (!el) return;
                var w = parseInt(el.getAttribute('data-sc-card-width'), 10);
                var h = parseInt(el.getAttribute('data-sc-card-height'), 10);
                if (!isNaN(w) && w > 0) this.cardWidth = w;
                if (!isNaN(h) && h > 0) this.cardHeight = h;
            },

            init() {
                var reduced = this.reducedMotion();
                if (reduced) {
                    this.autoplay = false;
                    this.duration = 0;
                }
                if (this.autoplay && this.n > 1 && !reduced) {
                    var dir = this.autoplayDir === 'leftToRight' ? -1 : 1;
                    this.interval = setInterval(() => this.step(dir), this.delay);
                }
                this.resize();
                window.addEventListener('resize', () => this.resize(), { passive: true });
                this.$watch('active', () => {
                    if (this.interval && this.autoplay && this.n > 1 && !this.reducedMotion()) {
                        clearInterval(this.interval);
                        var dir = this.autoplayDir === 'leftToRight' ? -1 : 1;
                        this.interval = setInterval(() => this.step(dir), this.delay);
                    }
                });
            },

            resize() {
                this.syncConfig();
                var stage = this.$refs.stage;
                if (!stage) return;
                var avail = stage.parentElement ? stage.parentElement.clientWidth : window.innerWidth;
                var needed = this.cardWidth + 160;
                this.scale = Math.max(0.42, Math.min(1, avail / needed));
            },

            step(dir) {
                if (this.lock || this.n < 2) return;
                this.lock = true;
                setTimeout(() => { this.lock = false; }, Math.max(50, this.duration));
                this.active = (((this.active + dir) % this.n) + this.n) % this.n;
            },

            handleCardClick(index) {
                if (this.n < 2) return;
                if (this.autoplay) { this.step(1); return; }
                this.lock = true;
                setTimeout(() => { this.lock = false; }, Math.max(50, this.duration));
                this.active = (index === this.active) ? (this.active + 1) % this.n : index;
            },

            cardStyle(index) {
                var rel = index - this.active;
                if (rel > this.n / 2) rel -= this.n;
                if (rel < -this.n / 2) rel += this.n;

                var ax = Math.abs(rel);
                var visible = ax <= 2;
                var scale = Math.max(0.4, 1 - ax * 0.16);
                var tx = rel * this.gap * 30 * this.scale;
                var tz = -ax * 240;
                var ry = -rel * this.tilt;
                var rz = rel * this.sideTilt;

                return {
                    width: this.cardWidth * this.scale + 'px',
                    height: this.cardHeight * this.scale + 'px',
                    borderRadius: this.radius * this.scale + 'px',
                    opacity: visible ? 1 : 0,
                    pointerEvents: visible ? 'auto' : 'none',
                    cursor: (this.autoplay || index === this.active) ? 'default' : 'pointer',
                    transform: 'translate(' + (50 * this.scale) + '%, ' + (50 * this.scale) + '%) ' +
                        'translateX(' + tx + 'px) translateZ(' + tz + 'px) ' +
                        'rotateY(' + ry + 'deg) rotateZ(' + rz + 'deg) scale(' + scale * this.scale + ')',
                    transition: this.duration ? 'transform ' + (this.duration / 1000) + 's cubic-bezier(0.22, 1, 0.36, 1), opacity ' + (this.duration / 1000) + 's cubic-bezier(0.22, 1, 0.36, 1)' : 'none'
                };
            },

            dimStyle(index) {
                return {
                    backgroundColor: '#000000',
                    opacity: (this.active === index) ? 0 : this.dimOpacity,
                    transition: this.duration ? 'opacity ' + (this.duration / 1000) + 's cubic-bezier(0.22, 1, 0.36, 1)' : 'none'
                };
            }
        };
    }
</script>
