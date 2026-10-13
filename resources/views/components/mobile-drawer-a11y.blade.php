{{-- resources/views/components/mobile-drawer-a11y.blade.php --}}
{{--
    Mobile foundation: turns Filament's sidebar into a proper, accessible
    off-canvas drawer below the 1024px breakpoint.

    - Locks background scrolling while the drawer is open.
    - Closes on Escape and after tapping a navigation link.
    - Keeps `aria-expanded` on every toggle in sync.
    - Remembers and restores focus to the button that opened the drawer.
    - Traps keyboard focus inside the drawer while it is open.
    - Gives the drawer an `id` and an accessible dialog name.
    - Everything is a no-op at >= 1024px, so desktop is untouched.
--}}
@once
<script>
(function () {
    if (window.__scMobileDrawerA11y) return;
    window.__scMobileDrawerA11y = true;

    var mq = window.matchMedia('(max-width: 1023px)');
    var lastTrigger = null;
    var observer = null;
    var observed = null;

    function sidebar() {
        return document.querySelector('.fi-sidebar');
    }

    function isOpen(s) {
        return !!(s && s.classList.contains('fi-sidebar-open'));
    }

    function toggles() {
        return Array.prototype.slice.call(document.querySelectorAll(
            '.fi-topbar-open-sidebar-btn, .fi-topbar-close-sidebar-btn, [aria-controls="fi-sidebar"]'
        ));
    }

    function focusables(el) {
        return Array.prototype.filter.call(
            el.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),select,textarea,[tabindex]:not([tabindex="-1"])'),
            function (n) { return n.offsetParent !== null || n === document.activeElement; }
        );
    }

    var ownsLock = false;

    function lock(open) {
        if (open) {
            if (ownsLock) return;
            ownsLock = true;
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
        } else {
            if (!ownsLock) return;
            ownsLock = false;
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
        }
    }

    function apply() {
        var s = sidebar();

        if (s && !s.id) { s.id = 'fi-sidebar'; }

        if (!mq.matches) {
            lock(false);
            if (s) {
                s.removeAttribute('role');
                s.removeAttribute('aria-modal');
                s.removeAttribute('aria-label');
            }
            toggles().forEach(function (b) { b.setAttribute('aria-expanded', 'true'); });
            return;
        }

        if (!s) { lock(false); return; }

        var open = isOpen(s);
        if (open) {
            s.setAttribute('role', 'dialog');
            s.setAttribute('aria-modal', 'true');
            if (!s.hasAttribute('aria-label')) {
                s.setAttribute('aria-label', @js(__('Navigation')));
            }
        } else {
            s.removeAttribute('role');
            s.removeAttribute('aria-modal');
        }
        toggles().forEach(function (b) { b.setAttribute('aria-expanded', open ? 'true' : 'false'); });

        if (open) {
            if (!ownsLock) {
                lastTrigger = document.activeElement;
                lock(true);
                var f = focusables(s);
                if (f.length && !s.contains(document.activeElement)) {
                    f[0].focus({ preventScroll: true });
                }
            }
        } else {
            var wasLocked = ownsLock;
            lock(false);
            if (wasLocked && lastTrigger && document.contains(lastTrigger)) {
                try { lastTrigger.focus({ preventScroll: true }); } catch (e) {}
            }
        }
    }

    function closeDrawer() {
        if (window.Alpine && window.Alpine.store('sidebar')) {
            window.Alpine.store('sidebar').close();
        }
    }

    document.addEventListener('keydown', function (e) {
        if (!mq.matches || !isOpen(sidebar())) return;

        if (e.key === 'Escape') {
            closeDrawer();
            return;
        }

        // While the drawer is modal, keep Tab focus inside it so keyboard
        // users cannot reach the obscured page behind the backdrop.
        if (e.key !== 'Tab') return;

        var s = sidebar();
        var f = focusables(s);
        if (!f.length) return;

        var first = f[0];
        var last = f[f.length - 1];
        var active = document.activeElement;
        var inside = s.contains(active);

        if (e.shiftKey) {
            if (active === first || !inside) {
                e.preventDefault();
                last.focus();
            }
        } else if (active === last || !inside) {
            e.preventDefault();
            first.focus();
        }
    }, true);

    document.addEventListener('click', function (e) {
        if (!mq.matches || !isOpen(sidebar())) return;
        var link = e.target.closest ? e.target.closest('.fi-sidebar a[href]') : null;
        if (link) {
            closeDrawer();
        }
    }, true);

    function observe() {
        var s = sidebar();
        if (!s) { apply(); return; }
        if (s === observed) { apply(); return; }
        if (observer) { observer.disconnect(); }
        observed = s;
        observer = new MutationObserver(apply);
        observer.observe(s, { attributes: true, attributeFilter: ['class'] });
        apply();
    }

    function boot() { observe(); apply(); }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

    document.addEventListener('livewire:navigated', function () { setTimeout(boot, 60); });

    if (mq.addEventListener) {
        mq.addEventListener('change', apply);
    } else if (mq.addListener) {
        mq.addListener(apply);
    }
})();
</script>
@endonce
