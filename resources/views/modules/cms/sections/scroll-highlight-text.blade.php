@php
    $text = $block['text'] ?? __('Our mission is to foster intellectual curiosity, moral integrity, and lifelong learning in every student who walks through our doors.');
    $dimColor = (string) ($block['dim_color'] ?? '');
    $litColor = (string) ($block['highlight_color'] ?? '');
    $splitBy = (($block['split_by'] ?? 'word') === 'character') ? 'character' : 'word';

    $units = [];
    if ($splitBy === 'character') {
        $units = preg_split('//u', (string) $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    } else {
        $units = preg_split('/\s+/u', trim((string) $text)) ?: [];
    }
@endphp
<section class="sc-scroll-highlight" aria-label="{{ e($text) }}"
         x-data="scScrollHighlight({ dim: '{{ $dimColor }}', lit: '{{ $litColor }}' })"
         x-init="init()" x-cloak>
    <div class="sc-container" style="max-width: 52rem;">
        <p class="sc-sh-body">
            @foreach ($units as $u)
                @if ($splitBy === 'character' && trim($u) === '')
                    <span class="sc-sh-word">{{ $u }}</span>
                @else
                    <span class="sc-sh-word">{{ e($u) }}</span>@if(! $loop->last) {{ ' ' }}@endif
                @endif
            @endforeach
        </p>
    </div>
</section>

<script>
    function scScrollHighlight(cfg) {
        return {
            dim: cfg.dim,
            lit: cfg.lit,
            words: [],

            init() {
                var el = this.$el;
                this.words = Array.prototype.slice.call(el.querySelectorAll('.sc-sh-word'));
                if (this.dim) el.style.setProperty('--sc-sh-dim', this.dim);
                if (this.lit) el.style.setProperty('--sc-sh-lit', this.lit);

                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || this.words.length === 0) {
                    this.words.forEach(function (w) { w.classList.add('is-lit'); });
                    return;
                }

                var self = this;
                var ticking = false;

                function update() {
                    ticking = false;
                    var rect = el.getBoundingClientRect();
                    var progress = (window.innerHeight * 0.55 - rect.top) / Math.max(rect.height, 1);
                    progress = Math.max(0, Math.min(1, progress));
                    var lit = Math.floor(progress * self.words.length);
                    self.words.forEach(function (w, i) {
                        w.classList.toggle('is-lit', i < lit);
                    });
                }

                function onScroll() {
                    if (ticking) return;
                    ticking = true;
                    requestAnimationFrame(update);
                }

                window.addEventListener('scroll', onScroll, { passive: true });
                window.addEventListener('resize', onScroll, { passive: true });
                update();
            }
        };
    }
</script>
