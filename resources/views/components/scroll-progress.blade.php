<div id="sc-scroll-progress" aria-hidden="true"></div>
<style>
    #sc-scroll-progress {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 9999;
        height: 6px;
        width: 0%;
        background: linear-gradient(90deg, var(--theme-primary, #15803d), var(--theme-accent, #eab308), #06b6d4);
        background-size: 200% 100%;
        animation: sc-bar-slide 6s linear infinite;
        box-shadow: 0 0 12px var(--theme-primary, #15803d);
        pointer-events: none;
        transition: width 60ms linear;
    }
    @keyframes sc-bar-slide {
        to { background-position: 200% 0; }
    }
</style>
<script>
    (function () {
        const bar = document.getElementById('sc-scroll-progress');
        if (!bar) return;
        let ticking = false;
        const update = () => {
            const doc = document.documentElement;
            const max = doc.scrollHeight - doc.clientHeight;
            const pct = max > 0 ? (doc.scrollTop / max) * 100 : 0;
            bar.style.width = pct + '%';
            ticking = false;
        };
        const onScroll = () => {
            if (!ticking) { ticking = true; requestAnimationFrame(update); }
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        update();
    })();
</script>
