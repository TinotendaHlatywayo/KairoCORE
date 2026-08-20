<button type="button"
        onclick="scGoBack()"
        title="{{ __('Go Back') }}"
        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition shadow-sm border border-gray-200 dark:border-gray-700">
    <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
    </svg>
    <span class="hidden sm:inline">{{ __('Back') }}</span>
</button>

<script>
function scGoBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '{{ url('/workspace') }}';
    }
}
</script>
