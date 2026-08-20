{{-- Page title typing effect.
     • `.sc-module-title` (the big module heading) types out continuously —
       type → hold → erase → retype — a true looping typewriter.
     • `.fi-header-heading` types out once per page; if it duplicates the
       module title it is hidden to avoid double headers.
     Re-triggers on each SPA navigation; idempotent via dataset flags. --}}
<script>
    (function () {
        const TYPE = 42;
        const HOLD = 3800;
        const ERASE = 24;
        const REST = 260;

        // Hide any page heading that simply repeats the module title.
        function dedupeHeadings() {
            const mod = document.querySelector('.sc-module-title');
            const modText = mod ? (mod.textContent || '').replace(/\s+/g, ' ').trim() : null;
            if (!modText) return;
            document.querySelectorAll('.fi-header-heading').forEach((h) => {
                const t = (h.textContent || '').replace(/\s+/g, ' ').trim();
                if (t === modText) {
                    h.style.display = 'none';
                    h.dataset.scHidden = '1';
                }
            });
        }

        function mount(el, full) {
            el.innerHTML = '';
            const textNode = document.createTextNode('');
            const caret = document.createElement('span');
            caret.className = 'sc-type-cursor';
            el.appendChild(textNode);
            el.appendChild(caret);
            return textNode;
        }

        // Continuous looping typewriter for the big module titles.
        function loop(el) {
            if (el.dataset.scBusy === '1') return;
            const full = (el.dataset.scFull || (el.textContent || '').replace(/\s+/g, ' ').trim()).trim();
            if (!full) return;
            el.dataset.scFull = full;
            el.dataset.scBusy = '1';

            const textNode = mount(el, full);

            const type = (i, done) => {
                if (i <= full.length) {
                    textNode.nodeValue = full.slice(0, i++);
                    setTimeout(() => type(i, done), TYPE);
                } else done();
            };
            const erase = (i, done) => {
                if (i >= 0) {
                    textNode.nodeValue = full.slice(0, i--);
                    setTimeout(() => erase(i, done), ERASE);
                } else {
                    textNode.nodeValue = '';
                    done();
                }
            };

            const once = () => type(0, () => setTimeout(() => erase(full.length, () => setTimeout(once, REST)), HOLD));
            once();
        }

        // Single-pass type for page headings.
        function typeOnce(el) {
            if (el.dataset.scBusy === '1') return;
            const full = (el.dataset.scFull || (el.textContent || '').replace(/\s+/g, ' ').trim()).trim();
            if (!full) return;
            el.dataset.scFull = full;
            el.dataset.scBusy = '1';

            const textNode = mount(el, full);
            let i = 0;
            const tick = () => {
                if (i <= full.length) {
                    textNode.nodeValue = full.slice(0, i++);
                    setTimeout(tick, TYPE);
                } else {
                    el.querySelector('.sc-type-cursor')?.remove();
                }
            };
            tick();
        }

        function run() {
            dedupeHeadings();
            document.querySelectorAll('.sc-module-title').forEach(loop);
            document.querySelectorAll('.fi-header-heading').forEach((h) => {
                if (h.dataset.scHidden === '1') return;
                typeOnce(h);
            });
        }

        run();

        document.addEventListener('livewire:init', () => run());
        document.addEventListener('livewire:navigated', () => run());

        // Fallback for headings added without a full navigation event.
        const observer = new MutationObserver(() => run());
        observer.observe(document.body, { childList: true, subtree: true });
    })();
</script>
