<button type="button" 
        onclick="toggleSidebarPosition()" 
        title="Switch Sidebar Left/Right"
        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition shadow-sm border border-gray-200 dark:border-gray-700">
    <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
    <span class="hidden sm:inline">{{ __('Sidebar Position') }}</span>
</button>

<script>
function toggleSidebarPosition() {
    const isRight = document.body.classList.toggle('sidebar-right');
    localStorage.setItem('sc_sidebar_right', isRight ? '1' : '0');
}
if (localStorage.getItem('sc_sidebar_right') === '1') {
    document.body.classList.add('sidebar-right');
}
</script>
