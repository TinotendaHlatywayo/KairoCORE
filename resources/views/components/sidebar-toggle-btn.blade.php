<button type="button" 
        x-data="{}"
        x-on:click="$store.sidebar.isOpen = ! $store.sidebar.isOpen"
        title="Toggle Sidebar (On/Off)"
        class="flex items-center justify-center p-2 rounded-xl text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition shadow-sm border border-gray-200 dark:border-gray-700">
    <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
    </svg>
</button>
