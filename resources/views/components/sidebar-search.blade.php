{{-- resources/views/components/sidebar-search.blade.php --}}
<div x-data="searchBar()" 
     x-init="init()"
     class="search-bar-wrapper sticky top-0 z-50 px-4 py-3"
     style="background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(20px) saturate(180%); -webkit-backdrop-filter: blur(20px) saturate(180%); border-bottom: 1px solid rgba(226, 232, 240, 0.6);">
    
    <div class="relative group">
        <!-- Search Icon -->
        <div x-on:click="$store.sidebar.isOpen ? $refs.searchInput.focus() : $store.sidebar.open()"
             title="Search workspace"
             class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 transition-all duration-300 group-focus-within:text-primary-500 group-hover:text-primary-400 cursor-pointer">
            <svg class="w-4.5 h-4.5 transition-transform duration-300 group-focus-within:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>

        <!-- Input Field -->
        <input 
            x-model="search"
            x-ref="searchInput"
            x-on:input="filterMenu()"
            x-on:focus="isFocused = true"
            x-on:blur="setTimeout(() => isFocused = false, 200)"
            type="text" 
            placeholder="Search workspace..." 
            class="w-full pl-10 pr-12 py-2.5 text-sm bg-white/90 dark:bg-gray-900/90 border-2 border-gray-200/60 dark:border-gray-800/60 rounded-xl focus:outline-none focus:ring-4 focus:ring-primary-500/20 focus:border-primary-500 transition-all duration-300 placeholder:text-gray-400 dark:placeholder:text-gray-500 text-gray-700 dark:text-gray-200 shadow-sm hover:shadow-md hover:border-primary-300/50"
            style="transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);"
        />

        <!-- Clear Button -->
        <button 
            x-show="search.length > 0" 
            x-on:click="clearSearch()" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-75"
            x-transition:enter-end="opacity-100 scale-100"
            class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:scale-110 transition-all duration-200 rounded-full bg-gray-100 dark:bg-gray-800 w-5 h-5 flex items-center justify-center"
            style="display: none;"
        >
            <span class="text-xs font-bold leading-none">{{ __('✕') }}</span>
        </button>

        <!-- Keyboard Shortcut Hint -->
        <div class="absolute right-3.5 top-1/2 -translate-y-1/2 pointer-events-none">
            <span x-show="search.length === 0" class="text-[9px] font-bold text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-md border border-gray-200 dark:border-gray-700">
                {{ __('⌘K') }}
            </span>
        </div>

        <!-- Expanding Search Indicator -->
        <div x-show="isFocused" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="absolute -bottom-0.5 left-0 right-0 h-0.5 bg-gradient-to-r from-primary-400 via-primary-500 to-primary-600 rounded-full">
        </div>
    </div>

    <!-- Search Results Dropdown -->
    <div x-show="search.length > 0 && filteredItems.length > 0" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         class="absolute left-4 right-4 mt-2 bg-white dark:bg-gray-900 rounded-xl shadow-2xl border border-gray-200/60 dark:border-gray-800/60 overflow-hidden max-h-80 overflow-y-auto z-50 search-results-scroll"
         style="display: none;"
         x-show="search.length > 0 && filteredItems.length > 0">
        
        <div class="p-2 space-y-1">
            <template x-for="item in filteredItems" :key="item.id">
                <a :href="item.url" 
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-all duration-200 group cursor-pointer">
                    <!-- Icon -->
                    <span class="flex items-center justify-center w-5 h-5 text-gray-400 group-hover:text-primary-500 transition-colors" x-html="item.icon"></span>
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors" x-html="highlightMatch(item.label)"></span>
                        <span class="text-[10px] text-gray-400 block truncate" x-text="item.group"></span>
                    </div>
                    <span class="text-[10px] font-bold text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity">{{ __('↵') }}</span>
                </a>
            </template>
        </div>
    </div>

    <!-- No Results State -->
    <div x-show="search.length > 0 && filteredItems.length === 0" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute left-4 right-4 mt-2 bg-white dark:bg-gray-900 rounded-xl shadow-2xl border border-gray-200/60 dark:border-gray-800/60 p-6 text-center"
         style="display: none;">
        <span class="text-2xl block mb-2">{{ __('🔍') }}</span>
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 block">{{ __('No results found') }}</span>
        <span class="text-xs text-gray-400 block mt-1">{{ __('Try adjusting your search terms') }}</span>
    </div>
</div>

<style>
    .search-bar-wrapper {
        position: sticky;
        top: 0;
        z-index: 50;
        background: rgba(255, 255, 255, 0.72);
        backdrop-filter: blur(20px) saturate(180%);
        -webkit-backdrop-filter: blur(20px) saturate(180%);
        border-bottom: 1px solid rgba(226, 232, 240, 0.6);
        transition: box-shadow 0.3s ease;
    }
    
    .search-bar-wrapper:hover {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    }
    
    .search-bar-wrapper input {
        background: rgba(255, 255, 255, 0.9);
        border-color: rgba(226, 232, 240, 0.6);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    .search-bar-wrapper input:focus {
        border-color: var(--theme-primary, #10b981);
        box-shadow: 0 0 0 4px rgba(var(--primary-600, 16, 185, 129), 0.12), 0 8px 24px rgba(var(--primary-600, 16, 185, 129), 0.06);
    }
    
    .search-bar-wrapper input:hover {
        border-color: rgba(var(--primary-600, 16, 185, 129), 0.4);
    }
    
    .dark .search-bar-wrapper {
        background: rgba(15, 23, 42, 0.85);
        border-bottom-color: rgba(30, 41, 59, 0.6);
    }
    
    .dark .search-bar-wrapper input {
        background: rgba(15, 23, 42, 0.9);
        border-color: rgba(30, 41, 59, 0.6);
        color: #e2e8f0;
    }
    
    .dark .search-bar-wrapper input:focus {
        border-color: var(--theme-primary, #10b981);
        box-shadow: 0 0 0 4px rgba(var(--primary-600, 16, 185, 129), 0.15);
    }
    
    .search-results-scroll::-webkit-scrollbar {
        width: 4px;
    }
    
    .search-results-scroll::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .search-results-scroll::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 8px;
    }
    
    .search-results-scroll::-webkit-scrollbar-thumb:hover {
        background: #cbd5e1;
    }
    
    /* Ensure SVG icons display properly in search results */
    .search-results-scroll svg {
        width: 20px;
        height: 20px;
        display: inline-block;
        vertical-align: middle;
    }
    
    /* Highlight matching text */
    .search-highlight {
        background: rgba(var(--primary-600, 16, 185, 129), 0.2);
        color: var(--theme-primary, #10b981);
        font-weight: 700;
        border-radius: 2px;
        padding: 0 2px;
    }
    
    .dark .search-highlight {
        background: rgba(16, 185, 129, 0.3);
        color: #34d399;
    }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('searchBar', () => ({
            search: '',
            isFocused: false,
            filteredItems: [],
            menuItems: [],
            
            init() {
                // Wait for DOM to be fully loaded
                this.$nextTick(() => {
                    this.menuItems = this.collectMenuItems();
                });
                
                // Keyboard shortcut: Cmd+K / Ctrl+K
                document.addEventListener('keydown', (e) => {
                    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                        e.preventDefault();
                        const input = this.$el.querySelector('input');
                        if (input) input.focus();
                    }
                    // Escape to clear
                    if (e.key === 'Escape' && this.search.length > 0) {
                        this.clearSearch();
                    }
                });
            },
            
            collectMenuItems() {
                const items = [];
                const sidebarItems = document.querySelectorAll('.fi-sidebar-item');
                
                sidebarItems.forEach((item, index) => {
                    const link = item.querySelector('a');
                    const label = link?.textContent?.trim() || 'Untitled';
                    
                    // Get the group label
                    const group = item.closest('.fi-sidebar-group');
                    const groupLabel = group?.querySelector('.fi-sidebar-group-label')?.textContent?.trim() || 'General';
                    
                    // Get the URL
                    const url = link?.getAttribute('href') || '#';
                    
                    // Get the icon - properly extract SVG without including text
                    let iconHtml = '';
                    const iconElement = item.querySelector('svg');
                    if (iconElement) {
                        // Clone the SVG to avoid modifying the original
                        const clone = iconElement.cloneNode(true);
                        // Remove any text nodes from the clone
                        const textNodes = clone.querySelectorAll('text');
                        textNodes.forEach(el => el.remove());
                        iconHtml = clone.outerHTML;
                    } else {
                        // Fallback icon if no SVG found
                        iconHtml = '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm0 10a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2zm0-10a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"/></svg>';
                    }
                    
                    items.push({
                        id: index,
                        label: label,
                        group: groupLabel,
                        url: url,
                        icon: iconHtml,
                        element: item
                    });
                });
                
                // Also index the contextual module navigation tabs so hidden
                // sub-pages stay discoverable via search.
                const moduleNav = document.querySelector('.sc-module-navigation');
                if (moduleNav) {
                    const moduleLabel = moduleNav.querySelector('.sc-module-title')?.textContent?.trim() || 'Module';
                    moduleNav.querySelectorAll('a.sc-tab, a.sc-more-item').forEach((link) => {
                        const label = link.textContent?.trim() || '';
                        if (!label) return;
                        items.push({
                            id: items.length,
                            label: label,
                            group: moduleLabel,
                            url: link.getAttribute('href') || '#',
                            icon: '<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>',
                        });
                    });
                }
                
                return items;
            },
            
            // ──────────────────────────────────────────────────────────────
            // 🔍 FUZZY STRING MATCHING
            // ──────────────────────────────────────────────────────────────
            fuzzyMatch(query, text) {
                if (!query || query.length === 0) return true;
                
                const q = query.toLowerCase().trim();
                const t = text.toLowerCase().trim();
                
                // Exact match check (case insensitive)
                if (t.includes(q)) return true;
                
                // Check if query characters appear in order in text (fuzzy)
                let qIndex = 0;
                for (let i = 0; i < t.length && qIndex < q.length; i++) {
                    if (t[i] === q[qIndex]) {
                        qIndex++;
                    }
                }
                
                // If all characters in query were found in order
                if (qIndex === q.length) return true;
                
                // Check if query is a substring of any word (e.g., "rport" matches "report")
                const words = t.split(/\s+/);
                for (const word of words) {
                    let wordIndex = 0;
                    let queryIndex = 0;
                    while (wordIndex < word.length && queryIndex < q.length) {
                        if (word[wordIndex] === q[queryIndex]) {
                            queryIndex++;
                        }
                        wordIndex++;
                    }
                    if (queryIndex === q.length) return true;
                }
                
                return false;
            },
            
            // ──────────────────────────────────────────────────────────────
            // 🔍 HIGHLIGHT MATCHING TEXT
            // ──────────────────────────────────────────────────────────────
            highlightMatch(text) {
                const query = this.search.toLowerCase().trim();
                if (!query || query.length === 0) return text;
                
                // Find the best match position
                const lowerText = text.toLowerCase();
                let bestStart = -1;
                let bestEnd = -1;
                
                // Try exact substring match first
                const exactIndex = lowerText.indexOf(query);
                if (exactIndex !== -1) {
                    bestStart = exactIndex;
                    bestEnd = exactIndex + query.length;
                } else {
                    // Try fuzzy match to find approximate position
                    let startPos = -1;
                    let endPos = -1;
                    let qIndex = 0;
                    
                    for (let i = 0; i < lowerText.length && qIndex < query.length; i++) {
                        if (lowerText[i] === query[qIndex]) {
                            if (startPos === -1) startPos = i;
                            qIndex++;
                            endPos = i + 1;
                        }
                    }
                    
                    // If we found a fuzzy match
                    if (qIndex === query.length) {
                        bestStart = startPos;
                        bestEnd = endPos;
                    }
                }
                
                if (bestStart === -1) return text;
                
                // Highlight the matched portion
                const before = text.substring(0, bestStart);
                const match = text.substring(bestStart, bestEnd);
                const after = text.substring(bestEnd);
                
                return `<span class="search-highlight">${match}</span>${after}`;
            },
            
            filterMenu() {
                const query = this.search.toLowerCase().trim();
                
                if (query === '') {
                    this.filteredItems = [];
                    // Show all items
                    document.querySelectorAll('.fi-sidebar-item').forEach(el => {
                        el.style.setProperty('display', '', 'important');
                    });
                    document.querySelectorAll('.fi-sidebar-group').forEach(el => {
                        el.style.setProperty('display', '', 'important');
                    });
                    return;
                }
                
                // Filter items using fuzzy matching
                this.filteredItems = this.menuItems.filter(item => {
                    return this.fuzzyMatch(query, item.label) || 
                           this.fuzzyMatch(query, item.group);
                });
                
                // Sort by match quality (items with exact matches first)
                this.filteredItems.sort((a, b) => {
                    const aLabel = a.label.toLowerCase();
                    const bLabel = b.label.toLowerCase();
                    const aScore = aLabel.includes(query) ? 0 : 1;
                    const bScore = bLabel.includes(query) ? 0 : 1;
                    return aScore - bScore;
                });
                
                // Get IDs of visible items
                const visibleIds = new Set(this.filteredItems.map(i => i.id));
                
                // Hide/show items
                document.querySelectorAll('.fi-sidebar-item').forEach(el => {
                    const id = parseInt(el.dataset.index || '0');
                    if (visibleIds.has(id)) {
                        el.style.setProperty('display', '', 'important');
                    } else {
                        el.style.setProperty('display', 'none', 'important');
                    }
                });
                
                // Show/hide groups with visible items
                document.querySelectorAll('.fi-sidebar-group').forEach(group => {
                    const hasVisible = group.querySelector('.fi-sidebar-item:not([style*="display: none"])');
                    if (hasVisible || query === '') {
                        group.style.setProperty('display', '', 'important');
                    } else {
                        group.style.setProperty('display', 'none', 'important');
                    }
                });
            },
            
            clearSearch() {
                this.search = '';
                this.filteredItems = [];
                document.querySelectorAll('.fi-sidebar-group, .fi-sidebar-item').forEach(el => {
                    el.style.setProperty('display', '', 'important');
                });
                const input = this.$el.querySelector('input');
                if (input) input.focus();
            }
        }));
    });
</script>