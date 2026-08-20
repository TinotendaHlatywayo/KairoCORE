{{-- Shared typewriter effect for auth pages (headings + rotating taglines).

     Marks an element to animate:
       data-sc-typing="Full Text"    – type once, keep the result
       data-sc-tw-once="Full Text"   – alias for headings
       data-sc-tw='["a","b"]'        – type through each phrase once, stopping
                                       on the last one (no infinite loop)

     Respects prefers-reduced-motion (shows the full text immediately) and
     keeps a hidden aria-label so screen readers get the complete copy. --}}

<script>
    (function () {
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function makeCaret() {
            const c = document.createElement('span');
            c.className = 'sc-caret';
            c.setAttribute('aria-hidden', 'true');
            return c;
        }

        function typeOnce(el, full) {
            if (reduceMotion || !full) { el.textContent = full; return; }
            el.setAttribute('aria-label', full);
            el.textContent = '';
            const caret = makeCaret();
            let i = 0;
            const tick = () => {
                i++;
                el.textContent = full.slice(0, i);
                el.appendChild(caret);
                if (i < full.length) setTimeout(tick, 70);
                else setTimeout(() => caret.remove(), 3500);
            };
            setTimeout(tick, 600);
        }

        // Type through each phrase once, then stop on the last one.
        function typePhrases(el, phrases) {
            if (reduceMotion || !phrases.length) { el.textContent = phrases[0] || ''; return; }
            el.setAttribute('aria-label', phrases.join(' '));
            const caret = makeCaret();
            let p = 0, i = 0, deleting = false;
            const tick = () => {
                const text = phrases[p] || '';
                if (!deleting) {
                    i++;
                    el.textContent = text.slice(0, i);
                    el.appendChild(caret);
                    if (i >= text.length) {
                        if (p >= phrases.length - 1) { setTimeout(() => caret.remove(), 2600); return; }
                        deleting = true;
                        setTimeout(tick, 1400);
                        return;
                    }
                    setTimeout(tick, 50 + Math.random() * 55);
                } else {
                    i--;
                    el.textContent = text.slice(0, i);
                    el.appendChild(caret);
                    if (i <= 0) {
                        deleting = false;
                        p++;
                        setTimeout(tick, 450);
                        return;
                    }
                    setTimeout(tick, 26);
                }
            };
            setTimeout(tick, 1000);
        }

        function init() {
            document.querySelectorAll('[data-sc-typing], [data-sc-tw-once]').forEach((el) => {
                const full = el.dataset.scTyping || el.dataset.scTwOnce || el.textContent.trim();
                typeOnce(el, full);
            });
            document.querySelectorAll('[data-sc-tw]').forEach((el) => {
                let phrases = [];
                try { phrases = JSON.parse(el.dataset.scTw); } catch (e) { phrases = [el.textContent.trim()]; }
                if (phrases.length) typePhrases(el, phrases);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>
