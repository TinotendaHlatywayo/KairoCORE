@php
    use Modules\CMS\Services\ComponentRegistry;

    // ── Aurora scene config (registry-normalized so variants supply defaults) ──
    $variant = $block['variant'] ?? 'aurora-immersive';
    $title = $block['title'] ?? __('Where Learning Comes Alive');
    $subtitle = $block['subtitle'] ?? __('A living school where every day is a story worth telling.');
    $primaryCtaText = $block['primary_cta_text'] ?? __('Explore Our Campus');
    $primaryCtaUrl = $block['primary_cta_url'] ?? '/about';
    $secondaryCtaText = $block['secondary_cta_text'] ?? __('Apply Now');
    $secondaryCtaUrl = $block['secondary_cta_url'] ?? '/apply-online';

    $hueShift = (bool) ($block['hue_shift'] ?? true);
    $blobCount = max(2, min(8, (int) ($block['blob_count'] ?? 4)));
    $intensity = max(0.1, min(1, (float) ($block['intensity'] ?? 0.55)));
    $speed = max(0.1, min(3, (float) ($block['speed'] ?? 1)));
    $scrollReaction = (bool) ($block['scroll_reaction'] ?? true);
    $pointerReaction = (bool) ($block['pointer_reaction'] ?? true);
    $showChips = (bool) ($block['show_chips'] ?? true);
    $showProgress = (bool) ($block['show_progress'] ?? true);

    // ── Palette: theme tokens (via $v when present) with an aurora-safe fallback ──
    $baseColor = $v['bg'] ?? ($theme['background'] ?? '#0b0f19');
    $hueA = $v['secondary'] ?? ($theme['secondary'] ?? '#06b6d4');
    $hueB = $v['accent'] ?? ($theme['accent'] ?? '#f43f5e');

    $words = preg_split('/\s+/u', trim((string) $title));

    // Floating stat chips — live module counts when available.
    $chips = [];
    if ($showChips) {
        $candidates = [
            ['key' => 'students_count', 'label' => __('Active Learners'), 'suffix' => '+'],
            ['key' => 'teachers_count', 'label' => __('Certified Faculty'), 'suffix' => '+'],
            ['key' => 'courses_count', 'label' => __('Programs'), 'suffix' => '+'],
        ];
        foreach ($candidates as $cand) {
            $chips[] = ['value' => (int) ($stats[$cand['key']] ?? 0), 'label' => $cand['label'], 'suffix' => $cand['suffix']];
        }
    }
@endphp
<section class="sc-aurora-hero" aria-label="{{ $title }}"
         x-data="scAurora({
    base: '{{ $baseColor }}',
    colors: ['{{ $hueA }}', '{{ $hueB }}', '#ffffff'],
    blobs: {{ $blobCount }},
    intensity: {{ $intensity }},
    speed: {{ $speed }},
    hueShift: {{ $hueShift ? 'true' : 'false' }},
    scrollReaction: {{ $scrollReaction ? 'true' : 'false' }},
    pointerReaction: {{ $pointerReaction ? 'true' : 'false' }}
})" x-init="init()">
    <canvas x-ref="canvas" class="sc-aurora-canvas" aria-hidden="true"></canvas>
    <div class="sc-aurora-vignette" aria-hidden="true"></div>

    <div class="sc-aurora-content">
        <div class="sc-container">
            <div class="sc-aurora-inner" x-ref="content">
                <span class="sc-eyebrow sc-eyebrow-light">{{ __('Welcome to Our School') }}</span>
                <h1 class="sc-aurora-title">
                    @foreach ($words as $i => $word)
                        <span class="sc-kinetic-word" style="--sc-delay: {{ 0.15 + $i * 0.09 }}s;">{{ e($word) }}@if(! $loop->last)&nbsp;@endif</span>
                    @endforeach
                </h1>
                <p class="sc-aurora-subtitle">{{ $subtitle }}</p>
                <div class="sc-aurora-actions">
                    <a href="{{ e($primaryCtaUrl) }}" class="sc-btn sc-btn-light sc-btn-lg">{{ e($primaryCtaText) }}</a>
                    <a href="{{ e($secondaryCtaUrl) }}" class="sc-btn sc-btn-outline-light sc-btn-lg">{{ e($secondaryCtaText) }}</a>
                </div>
            </div>
        </div>
    </div>

    @if ($showChips && ! empty($chips))
        <div class="sc-aurora-chips" aria-hidden="true">
            @foreach ($chips as $chip)
                <div class="sc-aurora-chip">
                    <span class="sc-aurora-chip-num" data-sc-count="{{ $chip['value'] }}" data-sc-suffix="{{ $chip['suffix'] }}">{{ number_format($chip['value']) }}{{ $chip['suffix'] }}</span>
                    <span class="sc-aurora-chip-label">{{ $chip['label'] }}</span>
                </div>
            @endforeach
        </div>
    @endif

    @if ($showProgress)
        <div class="sc-aurora-progress" aria-hidden="true">
            <div class="sc-aurora-progress-bar" :style="`width: ${progress * 100}%`"></div>
        </div>
    @endif
</section>

<script>
    function scAurora(cfg) {
        return {
            base: cfg.base,
            colors: cfg.colors,
            blobCount: cfg.blobs,
            intensity: cfg.intensity,
            speed: cfg.speed,
            hueShift: cfg.hueShift,
            scrollReaction: cfg.scrollReaction,
            pointerReaction: cfg.pointerReaction,
            progress: 0,

            blobs: [],
            raf: null,
            time: 0,
            pointer: { x: 0, y: 0 },
            target: { x: 0, y: 0 },

            reducedMotion() {
                return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            },

            motionDisabled() {
                var el = document.querySelector('[data-sc-motion]');
                return el ? el.getAttribute('data-sc-motion') === 'off' : false;
            },

            hexToRgb(hex) {
                var m = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex || '');
                return m ? { r: parseInt(m[1], 16), g: parseInt(m[2], 16), b: parseInt(m[3], 16) } : { r: 6, g: 182, b: 212 };
            },

            init() {
                var canvas = this.$refs.canvas;
                if (!canvas) return;
                this.ctx = canvas.getContext('2d');
                this.setupBlobs();
                this.resize();
                window.addEventListener('resize', () => this.resize(), { passive: true });

                var self = this;
                var words = this.$el.querySelectorAll('.sc-kinetic-word');
                var reduced = this.reducedMotion() || this.motionDisabled();

                if (reduced) {
                    words.forEach(function (w) { w.classList.add('is-in'); });
                    this.drawFrame(0);
                    return;
                }

                if (this.pointerReaction && window.matchMedia('(hover: hover)').matches) {
                    window.addEventListener('pointermove', function (e) {
                        self.target.x = (e.clientX / window.innerWidth) - 0.5;
                        self.target.y = (e.clientY / window.innerHeight) - 0.5;
                    }, { passive: true });
                }

                if (this.scrollReaction) {
                    var ticking = false;
                    window.addEventListener('scroll', function () {
                        if (ticking) return;
                        ticking = true;
                        requestAnimationFrame(function () {
                            self.updateScroll();
                            ticking = false;
                        });
                    }, { passive: true });
                }
                this.updateScroll();

                if ('IntersectionObserver' in window) {
                    var io = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (entry.isIntersecting) {
                                entry.target.querySelectorAll('.sc-kinetic-word').forEach(function (w) { w.classList.add('is-in'); });
                                io.disconnect();
                            }
                        });
                    }, { threshold: 0.3 });
                    io.observe(this.$el);
                } else {
                    words.forEach(function (w) { w.classList.add('is-in'); });
                }

                var last = performance.now();
                var loop = function (now) {
                    var dt = Math.min(50, now - last);
                    last = now;
                    self.time += dt / 1000;
                    self.pointer.x += (self.target.x - self.pointer.x) * 0.05;
                    self.pointer.y += (self.target.y - self.pointer.y) * 0.05;
                    self.drawFrame(dt / 1000);
                    self.raf = requestAnimationFrame(loop);
                };
                this.raf = requestAnimationFrame(loop);
            },

            setupBlobs() {
                var self = this;
                this.blobs = [];
                for (var i = 0; i < this.blobCount; i++) {
                    var col = this.hexToRgb(this.colors[i % this.colors.length]);
                    this.blobs.push({
                        r: col, a: 60 + (i % 2) * 30,
                        cx: 0, cy: 0,
                        ax: 0.25 + Math.random() * 0.35,
                        ay: 0.2 + Math.random() * 0.3,
                        fx: 0.12 + (i % 3) * 0.045,
                        fy: 0.09 + (i % 2) * 0.05,
                        px: Math.random() * Math.PI * 2,
                        py: Math.random() * Math.PI * 2,
                        radius: 0.45 + Math.random() * 0.35
                    });
                }
            },

            resize() {
                var canvas = this.$refs.canvas;
                if (!canvas) return;
                var dpr = Math.min(2, window.devicePixelRatio || 1);
                var w = canvas.parentElement.clientWidth || window.innerWidth;
                var h = canvas.parentElement.clientHeight || window.innerHeight;
                this.W = w;
                this.H = h;
                canvas.width = w * dpr;
                canvas.height = h * dpr;
                canvas.style.width = w + 'px';
                canvas.style.height = h + 'px';
                this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            },

            updateScroll() {
                var rect = this.$el.getBoundingClientRect();
                var total = Math.max(rect.height - window.innerHeight, 1);
                this.progress = Math.max(0, Math.min(1, -rect.top / total));
                var content = this.$refs.content;
                if (content && this.scrollReaction) {
                    content.style.transform = 'translateY(' + (this.progress * 90).toFixed(1) + 'px)';
                    content.style.opacity = String(Math.max(0.15, 1 - this.progress * 1.4));
                }
            },

            drawFrame(dt) {
                var ctx = this.ctx;
                if (!ctx) return;
                var t = this.time;
                var scrollBoost = this.scrollReaction ? 1 + this.progress * 0.7 : 1;
                var pScale = this.pointerReaction ? 60 : 0;
                var hueShift = this.hueShift ? t * 6 : 0;

                ctx.globalCompositeOperation = 'source-over';
                ctx.fillStyle = this.base;
                ctx.fillRect(0, 0, this.W, this.H);

                ctx.globalCompositeOperation = 'lighter';
                for (var i = 0; i < this.blobs.length; i++) {
                    var b = this.blobs[i];
                    var x = this.W * (0.5 + b.cx + b.ax * Math.sin(t * b.fx * this.speed + b.px) * scrollBoost)
                        + this.pointer.x * pScale * (0.4 + i * 0.18);
                    var y = this.H * (0.5 + b.cy + b.ay * Math.cos(t * b.fy * this.speed + b.py) * scrollBoost)
                        + this.pointer.y * pScale * (0.4 + i * 0.14);
                    var rad = Math.max(this.W, this.H) * b.radius;

                    var hue = (hueShift * (i % 2 === 0 ? 1 : -0.6));
                    var grd = ctx.createRadialGradient(x, y, 0, x, y, rad);
                    var c = b.r;
                    grd.addColorStop(0, 'hsla(' + (360 + hue) % 360 + ', 82%, 62%, ' + (0.5 * this.intensity) + ')');
                    grd.addColorStop(0.5, 'rgba(' + c.r + ',' + c.g + ',' + c.b + ',' + (0.28 * this.intensity) + ')');
                    grd.addColorStop(1, 'rgba(' + c.r + ',' + c.g + ',' + c.b + ', 0)');
                    ctx.fillStyle = grd;
                    ctx.beginPath();
                    ctx.arc(x, y, rad, 0, Math.PI * 2);
                    ctx.fill();
                }
                ctx.globalCompositeOperation = 'source-over';
            }
        };
    }
</script>

<noscript>
    <style>
        .sc-aurora-hero .sc-kinetic-word { opacity: 1; transform: none; filter: none; }
        .sc-aurora-progress { display: none; }
    </style>
</noscript>
