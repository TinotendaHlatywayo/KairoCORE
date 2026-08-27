@php
    $text = $block['text'] ?? __('Shaping Tomorrow, Today');
    $trigger = in_array($block['trigger'] ?? 'scroll', ['scroll', 'hover', 'load'], true) ? $block['trigger'] : 'scroll';
    $intensity = max(0.2, min(2, (float) ($block['intensity'] ?? 1)));
    $size = max(24, min(160, (int) ($block['title_size'] ?? 56)));
    $vw = round($size / 12, 3);
    $variant = (($block['variant'] ?? 'smoke') === 'rise') ? 'rise' : 'smoke';
    $words = preg_split('/\s+/u', trim((string) $text));
@endphp
<section class="sc-kinetic-heading" aria-label="{{ e($text) }}"
         data-trigger="{{ $trigger }}" data-variant="{{ $variant }}"
         x-data="scKinetic({ trigger: '{{ $trigger }}' })" x-init="init()" x-cloak>
    <div class="sc-container" style="text-align: center;">
        <h2 class="sc-kinetic-text" style="--sc-smoke: {{ $intensity }}; font-size: clamp(1.9rem, {{ $vw }}vw, {{ $size }}px); {{ preg_replace('/font-size:[^;]+;?/', '', $v['titleStyle'] ?? '') }}">
            @foreach ($words as $i => $word)
                <span class="sc-kinetic-word" style="--sc-delay: {{ $i * 0.08 }}s;">{{ e($word) }}@if(! $loop->last)&nbsp;@endif</span>
            @endforeach
        </h2>
    </div>
</section>

<script>
    function scKinetic(cfg) {
        return {
            trigger: cfg.trigger,

            init() {
                var el = this.$el;
                var words = el.querySelectorAll('.sc-kinetic-word');
                if (!words.length) return;

                if (this.trigger === 'load' || !('IntersectionObserver' in window)) {
                    words.forEach(function (w) { w.classList.add('is-in'); });
                    return;
                }

                if (this.trigger === 'scroll') {
                    var io = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (entry.isIntersecting) {
                                entry.target.querySelectorAll('.sc-kinetic-word').forEach(function (w) { w.classList.add('is-in'); });
                                io.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.25 });
                    io.observe(el);
                }
            }
        };
    }
</script>
