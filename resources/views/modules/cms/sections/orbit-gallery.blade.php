@php
    use Modules\CMS\Services\ComponentRegistry;

    // ── Images: accept {image.src + label}, {url + label}, or a plain string. ──
    $images = [];
    foreach (($block['images'] ?? []) as $item) {
        if (is_string($item)) {
            $images[] = ['src' => $item, 'label' => ''];
            continue;
        }
        $img = $item['image'] ?? null;
        $src = is_array($img) ? ($img['src'] ?? ($img['url'] ?? '')) : ($item['url'] ?? $img ?? '');
        $images[] = ['src' => (string) $src, 'label' => $item['label'] ?? ''];
    }
    if (empty($images)) {
        $images = [
            ['src' => ComponentRegistry::placeholderUrl('campus-exterior'), 'label' => __('Campus')],
            ['src' => ComponentRegistry::placeholderUrl('science-lab'), 'label' => __('Science')],
            ['src' => ComponentRegistry::placeholderUrl('library'), 'label' => __('Library')],
            ['src' => ComponentRegistry::placeholderUrl('arts-studio'), 'label' => __('Arts')],
            ['src' => ComponentRegistry::placeholderUrl('sports-field'), 'label' => __('Sports')],
        ];
    }

    $count = count($images);
    $itemSize = max(48, min(160, (int) ($block['item_size'] ?? 84)));
    $radiusX = max(80, min(420, (int) ($block['orbit_radius_x'] ?? 180)));
    $radiusY = max(30, min(200, (int) ($block['orbit_radius_y'] ?? 70)));
    $speed = max(0, min(30, (float) ($block['rotation_speed'] ?? 6)));
    $direction = (($block['direction'] ?? 'clockwise') === 'counter_clockwise') ? -1 : 1;
    $tilt = max(0, min(40, (int) ($block['tilt'] ?? 18)));
    $variant = (($block['variant'] ?? 'ellipse') === 'circle') ? 'circle' : 'ellipse';
    if ($variant === 'circle') {
        $radiusY = $radiusX;
    }

    // Stage must contain the widest/tallest node positions without clipping.
    $stageWidth = max(280, min(1000, $radiusX * 2 + $itemSize + 40));
    $stageHeight = max(220, min(720, $radiusY * 2 + $itemSize + 60));

    $heading = $block['title'] ?? __('Our Vibrant Community in Orbit');
    $subtitle = $block['subtitle'] ?? __('A living constellation of the people and places that shape our school.');
    $centerLabel = $block['center_label'] ?? __('Pillars');
@endphp
<section class="sc-orbit-section" aria-label="{{ $heading }}"
         x-data="scOrbit({ n: {{ $count }}, speed: {{ $speed }}, dir: {{ $direction }}, rx: {{ $radiusX }}, ry: {{ $radiusY }}, tilt: {{ $tilt }}, size: {{ $itemSize }} })"
         x-init="init()" x-cloak>
    <div class="sc-container">
        <div class="sc-section-head is-center">
            <span class="sc-eyebrow">{{ __('Community') }}</span>
            <h2 class="sc-section-title" {!! $v['titleStyle'] ?? '' !!}>{!! e($heading) !!}</h2>
            <p>{{ $subtitle }}</p>
        </div>

        <div class="sc-orbit-stage" :style="stageStyle()" style="width: {{ $stageWidth }}px; height: {{ $stageHeight }}px;">
            <div class="sc-orbit-center">{{ e($centerLabel) }}</div>
            @foreach ($images as $i => $item)
                <figure class="sc-orbit-node" :style="nodeStyle({{ $i }})" aria-hidden="true">
                    @if (! empty($item['src']))
                        <img src="{{ $item['src'] }}"
                             alt="{{ $item['label'] ?: '' }}"
                             width="{{ $itemSize }}" height="{{ $itemSize }}"
                             loading="lazy" decoding="async">
                    @endif
                    @if (! empty($item['label']))
                        <figcaption class="sc-orbit-label">{{ e($item['label']) }}</figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </div>
</section>

<script>
    function scOrbit(cfg) {
        return {
            n: cfg.n,
            speed: cfg.speed,
            dir: cfg.dir,
            rx: cfg.rx,
            ry: cfg.ry,
            tilt: cfg.tilt,
            size: cfg.size,
            rot: 0,
            raf: null,

            reducedMotion() {
                return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            },

            init() {
                if (this.speed <= 0 || this.n < 1 || this.reducedMotion()) return;
                var self = this;
                var last = performance.now();
                var loop = function (now) {
                    var dt = Math.min(50, now - last);
                    last = now;
                    self.rot = (self.rot + self.speed * (dt / 1000) * self.dir) % 360;
                    self.raf = requestAnimationFrame(loop);
                };
                this.raf = requestAnimationFrame(loop);
            },

            stageStyle() {
                return { transform: 'perspective(900px) rotateX(' + this.tilt + 'deg)' };
            },

            nodeStyle(index) {
                var angle = (index / this.n) * 360 + this.rot;
                var rad = angle * Math.PI / 180;
                var x = this.rx * Math.cos(rad);
                var y = this.ry * Math.sin(rad);
                return {
                    width: this.size + 'px',
                    height: this.size + 'px',
                    transform: 'translate(-50%, -50%) translate(' + x.toFixed(1) + 'px, ' + y.toFixed(1) + 'px)',
                    zIndex: 10 + Math.round(Math.sin(rad) * 10)
                };
            }
        };
    }
</script>
