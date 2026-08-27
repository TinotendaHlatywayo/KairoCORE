{{-- resources/views/components/sidebar-search.blade.php --}}
<div x-data="searchBar()" 
     x-init="init()"
     class="search-bar-wrapper sticky top-0 z-50 px-4 py-3"
     style="background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(20px) saturate(180%); -webkit-backdrop-filter: blur(20px) saturate(180%); border-bottom: 1px solid rgba(226, 232, 240, 0.6);">
    
    <div class="relative group">
        <!-- Search Icon -->
        <div x-on:click="$store.sidebar.isOpen ? $refs.searchInput.focus() : $store.sidebar.open()"
             title="{{ __('Search workspace') }}"
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
            placeholder="{{ __('Search workspace...') }}" 
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

@php
    $workspaceSearchIndex = collect($serverItems ?? [])
        ->map(function ($item) {
            $iconHtml = '';
            try {
                if (\Filament\Support\Icons\Heroicon::tryFrom($item['icon'] ?? '')) {
                    $iconHtml = svg($item['icon'], 'w-5 h-5')->toHtml();
                }
            } catch (\Throwable) {
                $iconHtml = '';
            }

            return [
                'label' => $item['label'],
                'group' => $item['group'],
                'url' => $item['url'],
                'iconHtml' => $iconHtml,
            ];
        })
        ->values()
        ->all();
@endphp
<script>
    // Server-side registry: every accessible page, including pages that are
    // not present in the sidebar DOM (shouldRegisterNavigation=false etc.).
    window.__workspaceSearchIndex = @json($workspaceSearchIndex);
    document.addEventListener('alpine:init', () => {
        Alpine.data('searchBar', () => ({
            search: '',
            isFocused: false,
            filteredItems: [],
            menuItems: [],
            
            init() {
                // Wait for DOM to be fully loaded
                this.$nextTick(() => {
                    this.menuItems = this.mergeServerItems(this.collectMenuItems(), window.__workspaceSearchIndex || []);
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
            
            // Merge the server-side page registry (covers pages that are NOT
            // in the sidebar or module tabs, e.g. Kairo CORE Messages) with
            // DOM-collected entries, deduped by URL path.
            mergeServerItems(domItems, serverItems) {
                const seen = new Set(
                    domItems
                        .filter((i) => i.url && i.url !== '#')
                        .map((i) => {
                            try { return new URL(i.url, window.location.origin).pathname; }
                            catch (e) { return i.url; }
                        })
                );
                
                serverItems.forEach((item, index) => {
                    let path = item.url;
                    try { path = new URL(item.url, window.location.origin).pathname; } catch (e) {}
                    if (!path || path === '#' || seen.has(path)) return;
                    seen.add(path);
                    domItems.push({
                        id: 'srv-' + index,
                        label: item.label,
                        group: item.group,
                        url: item.url,
                        icon: item.iconHtml,
                        element: null,
                    });
                });
                
                return domItems;
            },
            
            // ──────────────────────────────────────────────────────────────
            // 🔍 ROBUST MATCHING ENGINE
            // Layered scoring: exact → prefix → typo-tolerant (Levenshtein)
            // → synonym-aware → URL slug → character subsequence.
            // ──────────────────────────────────────────────────────────────

            // Education-domain synonyms so "clinic" also finds "Health &
            // Safety", "fees" finds Finance pages, etc.
            synonyms: {
                clinic: ['health', 'medical', 'nurse', 'sickbay', 'infirmary'],
                medical: ['clinic', 'health', 'nurse'],
                health: ['clinic', 'medical'],
                fees: ['fee', 'invoice', 'billing', 'payment', 'ledger'],
                fee: ['fees', 'invoice', 'billing'],
                invoice: ['billing', 'fees'],
                finance: ['fees', 'billing', 'accounting', 'expenses', 'revenue'],
                payroll: ['salary', 'salaries', 'wages', 'payslip'],
                salary: ['payroll', 'wages'],
                hr: ['human resources', 'staff', 'employees', 'personnel', 'leave', 'disciplinary', 'payroll'],
                staff: ['employee', 'employees', 'hr', 'teachers', 'personnel'],
                employee: ['staff', 'hr'],
                students: ['student', 'pupils', 'pupil', 'learners', 'learner', 'enrollment', 'enrolment', 'sis'],
                pupil: ['students', 'learner'],
                learner: ['students', 'pupil'],
                exams: ['exam', 'examination', 'tests', 'marks', 'grading', 'assessments', 'results'],
                exam: ['exams', 'marks', 'grades', 'assessment'],
                grades: ['grade', 'grading', 'marks', 'scores', 'reports'],
                attendance: ['register', 'presence', 'absence'],
                timetable: ['schedule', 'scheduling', 'periods', 'lessons'],
                library: ['books', 'book', 'e-resources', 'eresource', 'reading', 'catalogue'],
                book: ['library', 'catalogue'],
                inventory: ['stock', 'stores', 'supplies', 'assets'],
                stock: ['inventory', 'stores', 'adjustment'],
                assets: ['asset', 'fixed assets', 'equipment', 'depreciation'],
                procurement: ['purchase', 'purchases', 'supplier', 'suppliers', 'orders', 'goods received', 'requisition'],
                supplier: ['procurement', 'vendors', 'purchase'],
                hostel: ['hostels', 'boarding', 'dormitory', 'dorms', 'rooms', 'beds', 'allocation'],
                boarding: ['hostel', 'dormitory'],
                communication: ['messages', 'message', 'announcements', 'chat', 'sms', 'email', 'newsletter', 'notifications'],
                message: ['communication', 'chat', 'inbox', 'messaging'],
                reports: ['report', 'analytics', 'dashboards', 'statistics', 'insights', 'intelligence'],
                report: ['reports', 'analytics'],
                admissions: ['admission', 'applications', 'application', 'apply', 'applicants', 'enrollment'],
                admission: ['admissions', 'applications', 'apply'],
                homework: ['assignment', 'assignments', 'lessons', 'lms', 'classwork'],
                lms: ['homework', 'lessons', 'e-learning', 'courses'],
                website: ['cms', 'pages', 'builder', 'public site', 'content'],
                cms: ['website', 'pages', 'builder'],
                users: ['user', 'accounts', 'roles', 'permissions', 'administrators'],
                user: ['users', 'accounts', 'roles'],
                settings: ['configuration', 'configure', 'preferences', 'system', 'setup'],
                billing: ['subscription', 'subscriptions', 'plans', 'saas', 'payments'],
                subscription: ['billing', 'plans', 'saas'],
                tasks: ['task', 'todo', 'my day', 'calendar', 'schedule'],
                task: ['tasks', 'todo'],
                academics: ['academic', 'courses', 'classes', 'subjects', 'terms', 'promotions'],
                academic: ['academics', 'courses', 'subjects'],
                welfare: ['wellbeing', 'pastoral'],
                knowledge: ['knowledge base', 'gallery', 'resources', 'documents'],
            },

            normalize(text) {
                return (text || '').toLowerCase().replace(/[^a-z0-9\s&\/]/g, ' ').replace(/\s+/g, ' ').trim();
            },

            // Bounded Levenshtein edit distance with early exit.
            editDistance(a, b, max) {
                if (Math.abs(a.length - b.length) > max) return max + 1;
                const prev = new Array(b.length + 1);
                const curr = new Array(b.length + 1);
                for (let j = 0; j <= b.length; j++) prev[j] = j;
                for (let i = 1; i <= a.length; i++) {
                    curr[0] = i;
                    let rowMin = curr[0];
                    for (let j = 1; j <= b.length; j++) {
                        const cost = a[i - 1] === b[j - 1] ? 0 : 1;
                        curr[j] = Math.min(prev[j] + 1, curr[j - 1] + 1, prev[j - 1] + cost);
                        if (curr[j] < rowMin) rowMin = curr[j];
                    }
                    if (rowMin > max) return max + 1;
                    for (let j = 0; j <= b.length; j++) prev[j] = curr[j];
                }
                return prev[b.length];
            },

            // All acceptable alternative needles for the typed query.
            expandQuery(query) {
                const q = this.normalize(query);
                const needles = new Set([q]);
                // Whole query and each word may be a known synonym key.
                const keys = [q, ...q.split(' ')];
                for (const key of keys) {
                    if (this.synonyms[key]) {
                        this.synonyms[key].forEach(s => needles.add(this.normalize(s)));
                    }
                }
                return [...needles].filter(n => n.length > 1);
            },

            /**
             * Score an item against the query. Higher is better; 0 = no match.
             */
            scoreItem(query, item) {
                const q = this.normalize(query);
                if (!q) return 0;

                const label = this.normalize(item.label);
                const group = this.normalize(item.group);
                let urlPath = '';
                try { urlPath = this.normalize(new URL(item.url, window.location.origin).pathname); }
                catch (e) { urlPath = this.normalize(item.url); }

                const haystacks = [label, group, urlPath];

                // 1. Exact substring — strongest signal.
                if (label.includes(q)) return 100 - Math.min(label.indexOf(q), 20);
                if (group.includes(q)) return 85;

                // 2. Word-prefix match (query starts a word in the label).
                const labelWords = label.split(' ');
                if (labelWords.some(w => w.startsWith(q))) return 92;

                // 3. Synonym-expanded substring/prefix.
                const needles = this.expandQuery(q);
                for (const needle of needles) {
                    if (label.includes(needle)) return 80;
                    if (group.includes(needle)) return 70;
                    if (urlPath.includes(needle)) return 68;
                    if (labelWords.some(w => w.startsWith(needle))) return 75;
                }

                // 4. Typo tolerance — Levenshtein against each label word and
                //    each needle (handles "clnic" → "clinic", "studen" → "student").
                const tolerance = w => w.length >= 7 ? 2 : 1;
                for (const needle of [q, ...needles]) {
                    for (const word of labelWords) {
                        if (Math.abs(word.length - needle.length) <= 2 &&
                            this.editDistance(word, needle, tolerance(word)) <= tolerance(word)) {
                            return 72;
                        }
                    }
                }

                // 5. Loose subsequence fallback (existing behaviour).
                const subseq = (needle, text) => {
                    let i = 0;
                    for (let c = 0; c < text.length && i < needle.length; c++) {
                        if (text[c] === needle[i]) i++;
                    }
                    return i === needle.length;
                };
                if (subseq(q, label)) return 40;
                if (subseq(q, group)) return 35;

                return 0;
            },

            /** Backwards-compatible helper used elsewhere in this component. */
            fuzzyMatch(query, text) {
                return this.scoreItem(query, { label: text, group: '', url: '' }) > 0;
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
                const query = this.search;
                
                if (query.trim() === '') {
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
                
                // Score every indexed item and keep genuine matches.
                const scored = this.menuItems
                    .map(item => ({ item, score: this.scoreItem(query, item) }))
                    .filter(entry => entry.score > 0);
                
                // Best matches first; stable tie-break on original order.
                scored.sort((a, b) => b.score - a.score || a.item.id - b.item.id);
                this.filteredItems = scored.map(entry => entry.item);
                
                // Live-highlight matching sidebar items (by element reference —
                // dataset indexes were never assigned, so the old id lookup
                // silently filtered everything to item 0).
                const visibleElements = new Set(
                    scored.filter(e => e.item.element).map(e => e.item.element)
                );
                document.querySelectorAll('.fi-sidebar-item').forEach(el => {
                    if (visibleElements.has(el)) {
                        el.style.setProperty('display', '', 'important');
                    } else {
                        el.style.setProperty('display', 'none', 'important');
                    }
                });
                
                // Show/hide groups with visible items
                document.querySelectorAll('.fi-sidebar-group').forEach(group => {
                    const hasVisible = group.querySelector('.fi-sidebar-item:not([style*="display: none"])');
                    if (hasVisible || query.trim() === '') {
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