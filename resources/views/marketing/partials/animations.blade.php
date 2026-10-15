<style>
    /* Scroll-reveal */
    .reveal { opacity: 0; transform: translateY(28px); transition: opacity .7s cubic-bezier(.22,1,.36,1), transform .7s cubic-bezier(.22,1,.36,1); }
    .reveal.in { opacity: 1; transform: none; }
    .reveal-d1 { transition-delay: .08s; } .reveal-d2 { transition-delay: .16s; } .reveal-d3 { transition-delay: .24s; } .reveal-d4 { transition-delay: .32s; }

    /* Hero float */
    @keyframes floatY { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-14px); } }
    .float-slow { animation: floatY 7s ease-in-out infinite; }
    .float-med { animation: floatY 5.5s ease-in-out infinite .6s; }

    /* Gradient shimmer for headings */
    @keyframes shimmer { 0% { background-position: -200% center; } 100% { background-position: 200% center; } }
    .text-shimmer {
        background: linear-gradient(90deg, #1F2E43 0%, #D94B39 25%, #1F2E43 50%, #D94B39 75%, #1F2E43 100%);
        background-size: 200% auto;
        -webkit-background-clip: text; background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: shimmer 6s linear infinite;
    }

    /* Card hover lift */
    .card-lift { transition: transform .35s cubic-bezier(.22,1,.36,1), box-shadow .35s cubic-bezier(.22,1,.36,1); }
    .card-lift:hover { transform: translateY(-6px); box-shadow: 0 24px 48px -16px rgba(31,46,67,.18); }

    /* Magnetic button */
    .btn-magnetic { transition: transform .25s cubic-bezier(.22,1,.36,1), box-shadow .25s; }
    .btn-magnetic:hover { transform: translateY(-2px); box-shadow: 0 12px 28px -8px rgba(217,75,57,.45); }

    /* Marquee */
    @keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }
    .marquee-track { animation: marquee 32s linear infinite; }
    .marquee-wrap:hover .marquee-track { animation-play-state: paused; }

    /* Pulse ring */
    @keyframes pulseRing { 0% { box-shadow: 0 0 0 0 rgba(217,75,57,.35); } 70% { box-shadow: 0 0 0 14px rgba(217,75,57,0); } 100% { box-shadow: 0 0 0 0 rgba(217,75,57,0); } }
    .pulse-ring { animation: pulseRing 2.4s ease-out infinite; }

    /* Counter */
    @keyframes fadeUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
    .fade-up { animation: fadeUp .5s cubic-bezier(.22,1,.36,1) both; }

    /* Modal / drawer */
    .modal-backdrop { backdrop-filter: blur(8px); background: rgba(15,23,42,.55); }

    /* Feature card image zoom */
    .zoom-img { transition: transform .6s cubic-bezier(.22,1,.36,1); }
    .group:hover .zoom-img { transform: scale(1.07); }

    /* Step connector */
    .step-line { background: linear-gradient(90deg, #D94B39, #1F2E43); }

    @media (prefers-reduced-motion: reduce) {
        .reveal, .float-slow, .float-med, .text-shimmer, .marquee-track, .pulse-ring, .fade-up { animation: none !important; transition: none !important; opacity: 1 !important; transform: none !important; }
    }
</style>
<noscript><style>.reveal { opacity: 1 !important; transform: none !important; }</style></noscript>
<script>
(function () {
    if (window.__scRevealInit) return;
    window.__scRevealInit = true;

    var supportsIO = typeof IntersectionObserver === 'function';

    function showAll() {
        document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('in'); });
    }

    function whenReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    var io = supportsIO ? new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
            if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
        });
    }, { threshold: 0, rootMargin: '0px 0px -40px 0px' }) : null;

    function scan() {
        if (!io) { showAll(); return; }
        document.querySelectorAll('.reveal:not(.in)').forEach(function (el) { io.observe(el); });
    }

    whenReady(scan);
    document.addEventListener('livewire:navigated', function () { setTimeout(scan, 80); });

    /* Safety net: never leave content invisible if observing failed. */
    setTimeout(function () {
        var reveals = document.querySelectorAll('.reveal');
        if (!reveals.length) return;
        if (document.querySelectorAll('.reveal.in').length === 0) showAll();
    }, 2500);

    /* Magnetic buttons */
    document.addEventListener('mousemove', function (e) {
        document.querySelectorAll('.btn-magnetic').forEach(function (btn) {
            var r = btn.getBoundingClientRect();
            var cx = r.left + r.width / 2, cy = r.top + r.height / 2;
            var dx = e.clientX - cx, dy = e.clientY - cy;
            var dist = Math.hypot(dx, dy);
            if (dist < 90) {
                btn.style.transform = 'translate(' + (dx * 0.12) + 'px,' + (dy * 0.12) + 'px)';
            } else {
                btn.style.transform = '';
            }
        });
    });

    /* Count-up numbers */
    whenReady(function () {
        var counters = document.querySelectorAll('[data-countup]');
        if (!counters.length) return;
        if (!supportsIO) {
            counters.forEach(function (el) {
                el.textContent = (el.getAttribute('data-countup') || '') + (el.getAttribute('data-suffix') || '');
            });
            return;
        }
        var cio = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (!e.isIntersecting) return;
                cio.unobserve(e.target);
                var el = e.target, target = parseInt(el.getAttribute('data-countup'), 10), start = 0, dur = 1400, t0 = null;
                if (isNaN(target)) return;
                function step(ts) {
                    if (!t0) t0 = ts;
                    var p = Math.min((ts - t0) / dur, 1);
                    var ease = 1 - Math.pow(1 - p, 3);
                    el.textContent = Math.round(start + (target - start) * ease) + (el.getAttribute('data-suffix') || '');
                    if (p < 1) requestAnimationFrame(step);
                }
                requestAnimationFrame(step);
            });
        }, { threshold: 0.4 });
        counters.forEach(function (el) { cio.observe(el); });
    });
})();
</script>
