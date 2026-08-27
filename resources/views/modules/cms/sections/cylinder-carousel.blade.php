@php
    // Deterministic, self-contained showcase cards. Content editors may supply
    // their own `images` (url + label + tagline); otherwise we render bold,
    // gradient tiles so the cylinder never depends on external image hosts.
    $cards = $block['images'] ?? [];
    if (empty($cards)) {
        $cards = [
            ['label' => __('Academics'), 'tagline' => __('Championing world-class methodologies and high-performance regional curricula.'), 'icon' => 'book'],
            ['label' => __('Athletics'), 'tagline' => __('Nurturing physical endurance, teamwork, and championship-level capabilities.'), 'icon' => 'trophy'],
            ['label' => __('Science & STEM'), 'tagline' => __('Fostering logical, algorithmic, and digital transformation in education.'), 'icon' => 'atom'],
            ['label' => __('Arts & Culture'), 'tagline' => __('Refining creative horizons through drama, design, and performance.'), 'icon' => 'palette'],
            ['label' => __('Admissions'), 'tagline' => __('Apply today to secure a bright academic career pathway.'), 'icon' => 'grad'],
        ];
    }

    $icons = [
        'book' => '<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>',
        'trophy' => '<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>',
        'atom' => '<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><path d="M20.2 20.2c2.04-2.03.02-7.36-4.5-11.9-4.54-4.52-9.87-6.54-11.9-4.5-2.04 2.03-.02 7.36 4.5 11.9 4.54 4.52 9.87 6.54 11.9 4.5Z"/><path d="M15.7 15.7c4.52-4.54 6.54-9.87 4.5-11.9-2.03-2.04-7.36-.02-11.9 4.5-4.52 4.54-6.54 9.87-4.5 11.9 2.03 2.04 7.36.02 11.9-4.5Z"/></svg>',
        'palette' => '<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.92 0 1.5-.77 1.5-1.5 0-.37-.13-.72-.36-1.01-.23-.29-.36-.64-.36-1.02 0-1.1.9-2 2-2h1.5C19.5 16.5 22 14 22 10.5 22 5.5 17.5 2 12 2z"/></svg>',
        'grad' => '<svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10 12 5 2 10l10 5 10-5z"/><path d="M6 12v5c0 1.66 2.69 3 6 3s6-1.34 6-3v-5"/><path d="M22 10v6"/></svg>',
    ];
    $count = count($cards);
    $segment = $count > 0 ? 360 / $count : 72;
    $gradients = [
        'linear-gradient(140deg, color-mix(in srgb, var(--sc-primary) 88%, #000 12%), color-mix(in srgb, var(--sc-secondary) 82%, #000 18%))',
        'linear-gradient(140deg, color-mix(in srgb, var(--sc-secondary) 88%, #000 12%), color-mix(in srgb, var(--sc-accent) 82%, #000 18%))',
        'linear-gradient(140deg, color-mix(in srgb, var(--sc-accent) 86%, #000 14%), color-mix(in srgb, var(--sc-primary) 86%, #000 14%))',
        'linear-gradient(140deg, color-mix(in srgb, var(--sc-primary) 85%, #000 15%), color-mix(in srgb, var(--sc-accent) 80%, #000 20%))',
        'linear-gradient(140deg, color-mix(in srgb, var(--sc-secondary) 86%, #000 14%), color-mix(in srgb, var(--sc-primary) 84%, #000 16%))',
    ];
@endphp

<div class="sc-cylinder" style="padding: 0.5rem 0 1rem;" aria-label="{{ __('Campus highlights carousel') }}">

    <div class="sc-section-head is-center">
        <span class="sc-eyebrow">{{ __('Featured Highlights') }}</span>
        @php $cylTitle = trim((string) ($block['title'] ?? '')) !== '' ? $block['title'] : __('Inside Our Campus'); @endphp
        <h2 class="sc-section-title" style="color: var(--sc-text); {{ $v['titleStyle'] ?? '' }}">{{ $cylTitle }}</h2>
    </div>

    <div class="sc-cylinder-stage"
         x-data="scCylinder({{ $count }}, {{ $segment }})"
         role="group"
         aria-roledescription="{{ __('carousel') }}"
         aria-label="{{ __('Campus highlights') }}">

        <p class="sc-cylinder-caption" aria-live="polite" style="min-height: 6rem; text-align: center; margin: 0 auto 1.5rem; max-width: 30rem;">
            <span class="sc-badge sc-badge-solid" x-text="cards[activeIdx].label">&#8203;</span>
            <span class="sc-muted" style="display: block; margin-top: 0.6rem; font-size: 0.98rem;" x-text="cards[activeIdx].tagline"></span>
        </p>

        <div class="sc-cylinder-row" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
            <button type="button" class="sc-cylinder-arrow" @click="prev()" aria-label="{{ __('Previous highlight') }}">&#8592;</button>

            <div class="sc-cylinder-viewport"
                 style="perspective: 1100px; position: relative; width: min(22rem, 86vw); height: 22rem;"
                 x-ref="viewport"
                 @keydown.arrow-left.prevent="prev()"
                 @keydown.arrow-right.prevent="next()"
                 tabindex="0"
                 role="region"
                 aria-label="{{ __('Rotate highlights with the arrow buttons or drag. Use left and right arrow keys.') }}"
                 @mousedown="handleStart"
                 @mousemove="handleMove"
                 @mouseup="handleEnd"
                 @mouseleave="handleEnd"
                 @touchstart="handleStart"
                 @touchmove="handleMove"
                 @touchend="handleEnd">

                <div class="sc-cylinder-track" style="width: 100%; height: 100%; position: relative; transform-style: preserve-3d; transition: transform .5s ease;"
                     :style="`transform: rotateY(${rotationY}deg); transition-duration: ${transitionMs}ms;`">

                    <template x-for="(card, idx) in cards" :key="idx">
                        <div class="sc-cylinder-card"
                             style="position: absolute; inset: 0; margin: auto; width: min(16rem, 70%); height: 18rem; border-radius: var(--sc-radius); overflow: hidden; box-shadow: var(--sc-shadow-lg); display: flex; flex-direction: column; justify-content: flex-end; color: #fff; transform-style: preserve-3d; backface-visibility: hidden;"
                             :style="`transform: rotateY(${idx * segment}deg) translateZ(${radius}px); background-image: ${card.url ? 'url(\'' + card.url + '\'), ' : ''} ${card.gradient || 'linear-gradient(140deg, var(--sc-primary), var(--sc-secondary))'}; background-size: cover; background-position: center;`"
                             :aria-hidden="activeIdx !== idx ? 'true' : 'false'"
                             :class="activeIdx === idx ? 'sc-cylinder-card--active' : ''">

                            <div style="padding: 1.5rem; background: linear-gradient(to top, rgba(2,6,23,.78), transparent 85%);">
                                <span class="sc-cylinder-icon" x-html="card.icon" style="display:block; margin-bottom: .75rem; opacity:.9;"></span>
                                <h3 style="font-family: var(--sc-font-display); font-weight: 800; font-size: 1.3rem; text-transform: uppercase; letter-spacing: .02em;" x-text="card.label"></h3>
                                <span style="display:block; width: 2.5rem; height: 3px; border-radius: 999px; margin-top: .6rem; background: color-mix(in srgb, var(--sc-accent) 85%, #fff);"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <button type="button" class="sc-cylinder-arrow" @click="next()" aria-label="{{ __('Next highlight') }}">&#8594;</button>
        </div>
    </div>
</div>

<script>
    function scCylinder(count, segment) {
        return {
            activeIdx: 0,
            rotationY: 0,
            startX: 0,
            isDown: false,
            count: count || 1,
            segment: segment || 72,
            radius: 280,
            transitionMs: 500,
            reduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            cards: @js(array_map(function ($c) use ($icons) {
                return [
                    'label' => $c['label'] ?? 'Highlight',
                    'tagline' => $c['tagline'] ?? '',
                    'url' => $c['url'] ?? '',
                    'icon' => $icons[$c['icon'] ?? 'book'] ?? $icons['book'],
                    'gradient' => '',
                ];
            }, $cards)),
            init() {
                if (this.reduced) this.transitionMs = 0;
                this.calculateRadius();
                const self = this;
                window.addEventListener('resize', () => self.calculateRadius());
            },
            calculateRadius() {
                const el = this.$refs.viewport;
                if (!el) return;
                const w = Math.max(el.offsetWidth, 200);
                this.radius = Math.max(200, Math.round((w / 2.4) / Math.tan(Math.PI / this.count)));
            },
            handleStart(e) {
                if (this.reduced) return;
                this.isDown = true;
                this.startX = (e.pageX || e.touches[0].pageX) - this.rotationY;
            },
            handleMove(e) {
                if (!this.isDown || this.reduced) return;
                e.preventDefault();
                this.rotationY = (e.pageX || e.touches[0].pageX) - this.startX;
                this.resolveActive();
            },
            handleEnd() {
                if (this.reduced) { this.isDown = false; return; }
                this.isDown = false;
                const snapped = Math.round(-this.rotationY / this.segment);
                this.rotationY = -snapped * this.segment;
                this.resolveActive();
            },
            next() { this.rotationY -= this.segment; this.resolveActive(); },
            prev() { this.rotationY += this.segment; this.resolveActive(); },
            resolveActive() {
                let raw = Math.round(-this.rotationY / this.segment) % this.count;
                if (raw < 0) raw += this.count;
                this.activeIdx = raw;
            },
        };
    }
</script>
