{{-- Pin the sidebar permanently visible (tablet & up) so it no longer
     auto-hides or only appears on hover. The preference persists in
     localStorage and only takes effect from 768px up — phones keep the
     off-canvas drawer. Rendered next to the language switcher in the
     Platform admin and Student panels. --}}
<button
    type="button"
    x-data="{
        fixed: false,
        on: @js(__('Sidebar pinned')),
        off: @js(__('Pin sidebar')),
        init() {
            this.fixed = document.body.classList.contains('sc-sidebar-fixed');
            this.$watch('fixed', (value) => {
                document.body.classList.toggle('sc-sidebar-fixed', value);
                try { localStorage.setItem('sc_sidebar_fixed', value ? '1' : '0'); } catch (e) {}
            });
        },
    }"
    x-on:click="fixed = !fixed"
    x-bind:aria-pressed="fixed.toString()"
    x-bind:title="fixed ? on : off"
    x-bind:class="fixed
        ? 'bg-primary-600 text-white border-primary-600 hover:bg-primary-500'
        : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 dark:bg-white/5 dark:text-gray-200 dark:border-white/10 dark:hover:bg-white/10'"
    class="sc-sidebar-fixed-toggle flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-sm font-medium shadow-sm transition"
    aria-label="{{ __('Pin sidebar') }}"
>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
    </svg>
    <span class="hidden sm:inline" x-text="fixed ? on : off"></span>
</button>

<script>
    (function () {
        function apply() {
            try {
                if (localStorage.getItem('sc_sidebar_fixed') === '1') {
                    document.body.classList.add('sc-sidebar-fixed');
                }
            } catch (e) {}
        }

        apply();

        if (! window.__scSidebarFixedBound) {
            window.__scSidebarFixedBound = true;
            document.addEventListener('livewire:navigated', apply);
        }
    })();
</script>
