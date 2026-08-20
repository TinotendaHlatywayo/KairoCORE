{{-- ============================================================
     SchoolCore Website Motion Library
     Vanilla JS (no framework): scroll reveals, parallax, tilt,
     animated counters and the DepthText effect (adapted from the
     React Bits component to a framework-agnostic implementation).

     Every effect respects `prefers-reduced-motion` and the
     site-level `enable_animations` flag (data-sc-motion="off").
     ============================================================ --}}
<script>
    (function () {
        'use strict';

        var root = document.documentElement;
        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var disabled = document.querySelector('[data-sc-motion]')?.getAttribute('data-sc-motion') === 'off';

        function onReady(fn) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', fn);
            } else {
                fn();
            }
        }

        onReady(function () {
            var site = document.querySelector('.sc-site');
            if (!site) return;

            /* ── Sticky nav shadow ── */
            var nav = site.querySelector('.sc-nav');
            if (nav && !reduced && !disabled) {
                var onScroll = function () {
                    nav.classList.toggle('is-scrolled', window.scrollY > 8);
                };
                onScroll();
                window.addEventListener('scroll', onScroll, { passive: true });
            }

            /* ── Scroll reveal ── */
            if (!reduced && !disabled && 'IntersectionObserver' in window) {
                var revealEls = site.querySelectorAll('[data-sc-reveal]');
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-in');
                            io.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });

                revealEls.forEach(function (el) { io.observe(el); });
            } else {
                site.querySelectorAll('[data-sc-reveal]').forEach(function (el) {
                    el.classList.add('is-in');
                });
            }

            /* ── Animated counters ── */
            if (!reduced && !disabled && 'IntersectionObserver' in window) {
                var countEls = site.querySelectorAll('[data-sc-count]');
                var cio = new IntersectionObserver(function (entries) {
                    entries.forEach(function (entry) {
                        if (!entry.isIntersecting) return;
                        var el = entry.target;
                        cio.unobserve(el);
                        var target = parseFloat(el.getAttribute('data-sc-count')) || 0;
                        var suffix = el.getAttribute('data-sc-suffix') || '';
                        var dur = Math.min(1600, Math.max(600, target * 8));
                        var start = performance.now();

                        function tick(now) {
                            var p = Math.min((now - start) / dur, 1);
                            var eased = 1 - Math.pow(1 - p, 3);
                            var val = Math.round(target * eased);
                            el.textContent = val.toLocaleString() + suffix;
                            if (p < 1) requestAnimationFrame(tick);
                        }
                        requestAnimationFrame(tick);
                    });
                }, { threshold: 0.4 });
                countEls.forEach(function (el) { cio.observe(el); });
            }

            /* ── Parallax ── */
            if (!reduced && !disabled && !window.matchMedia('(hover: none)').matches) {
                var parEls = Array.prototype.slice.call(site.querySelectorAll('.sc-parallax'));
                if (parEls.length && 'IntersectionObserver' in window) {
                    var pio = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (entry.isIntersecting) {
                                var depth = parseFloat(entry.target.getAttribute('data-sc-parallax')) || 0.12;
                                var rect = entry.target.getBoundingClientRect();
                                var offset = (rect.top + rect.height / 2 - window.innerHeight / 2) * depth;
                                entry.target.style.transform = 'translate3d(0, ' + offset.toFixed(1) + 'px, 0)';
                            }
                        });
                    }, { threshold: 0 });
                    parEls.forEach(function (el) { pio.observe(el); });

                    var ticking = false;
                    window.addEventListener('scroll', function () {
                        if (ticking) return;
                        ticking = true;
                        requestAnimationFrame(function () {
                            parEls.forEach(function (el) {
                                var rect = el.getBoundingClientRect();
                                if (rect.top > window.innerHeight || rect.bottom < 0) return;
                                var depth = parseFloat(el.getAttribute('data-sc-parallax')) || 0.12;
                                var offset = (rect.top + rect.height / 2 - window.innerHeight / 2) * depth;
                                el.style.transform = 'translate3d(0, ' + offset.toFixed(1) + 'px, 0)';
                            });
                            ticking = false;
                        });
                    }, { passive: true });
                }
            }

            /* ── Pointer tilt cards ── */
            if (!reduced && !disabled && window.matchMedia('(hover: hover)').matches) {
                site.querySelectorAll('.sc-tilt').forEach(function (card) {
                    var max = parseFloat(card.getAttribute('data-sc-tilt')) || 8;
                    card.addEventListener('mousemove', function (e) {
                        var rect = card.getBoundingClientRect();
                        var px = (e.clientX - rect.left) / rect.width - 0.5;
                        var py = (e.clientY - rect.top) / rect.height - 0.5;
                        card.style.transform = 'perspective(900px) rotateX(' + (-py * max).toFixed(2) + 'deg) rotateY(' + (px * max).toFixed(2) + 'deg) translateY(-3px)';
                    });
                    card.addEventListener('mouseleave', function () {
                        card.style.transform = '';
                    });
                });
            }

            /* ── DepthText (React Bits adaptation) ──
               Rebuilds the layered 3D text effect: a stack of aria-hidden
               text layers offset behind the real face, plus subtle auto
               motion and pointer tilt. Real text stays as the top layer
               so it remains fully accessible and selectable. */
            function buildDepth(el) {
                var text = el.getAttribute('data-sc-depth-text');
                if (!text) text = el.textContent.trim();
                if (!text) return;

                var layers = parseInt(el.getAttribute('data-sc-layers')) || 4;
                var step = parseFloat(el.getAttribute('data-sc-step')) || 2.5;
                var depthColor = el.getAttribute('data-sc-depth-color') || '';
                var face = el.querySelector('.sc-depth-face');
                if (!face) return;

                el.querySelectorAll('.sc-depth-layer').forEach(function (l) { l.remove(); });

                for (var i = layers; i >= 1; i--) {
                    var span = document.createElement('span');
                    span.className = 'sc-depth-layer';
                    span.setAttribute('aria-hidden', 'true');
                    span.textContent = text;
                    var off = i * step;
                    var mix = Math.max(0.25, 0.9 - i * 0.14);
                    span.style.transform = 'translateZ(' + (-off).toFixed(1) + 'px) translateY(' + (off * 0.28).toFixed(1) + 'px)';
                    if (depthColor) {
                        span.style.color = depthColor;
                        span.style.opacity = mix.toFixed(2);
                    }
                    el.appendChild(span);
                }
            }

            if (!reduced && !disabled) {
                site.querySelectorAll('[data-sc-depth]').forEach(function (el) { buildDepth(el); });

                var tiltEls = Array.prototype.slice.call(site.querySelectorAll('[data-sc-depth]'));
                var depthTicking = false;
                var depthCenter = { x: 0, y: 0 };

                function animateDepth() {
                    if (reduced || disabled) return;
                    var time = performance.now() / 1000;
                    tiltEls.forEach(function (el) {
                        var orbitOn = el.getAttribute('data-sc-depth-orbit') !== '0';
                        var pointerOn = el.getAttribute('data-sc-depth-pointer') !== '0';
                        var baseRotY = orbitOn ? Math.sin(time * 0.7) * 2.2 : 0;
                        var baseRotX = orbitOn ? Math.cos(time * 0.55) * 1.4 : 0;
                        var rotY = baseRotY + (pointerOn ? depthCenter.x : 0);
                        var rotX = -baseRotX + (pointerOn ? depthCenter.y : 0);
                        el.style.transform = 'rotateY(' + rotY.toFixed(2) + 'deg) rotateX(' + rotX.toFixed(2) + 'deg)';
                    });
                    requestAnimationFrame(animateDepth);
                }

                if (tiltEls.length && window.matchMedia('(hover: hover)').matches) {
                    window.addEventListener('mousemove', function (e) {
                        depthCenter.x = ((e.clientX / window.innerWidth) - 0.5) * 6;
                        depthCenter.y = ((e.clientY / window.innerHeight) - 0.5) * 4;
                    }, { passive: true });
                }

                if (tiltEls.length) requestAnimationFrame(animateDepth);
            }
        });
    })();
</script>
