<x-filament-panels::page>
    <div class="space-y-8" x-data="{ search: '' }">
        
        <!-- Header Filter Dashboard Card -->
        <div class="p-6 rounded-2xl border border-gray-150 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-1">
                <h2 class="text-base font-bold text-gray-950 dark:text-gray-50 tracking-tight">{{ __('Analytics Explorer Hub') }}</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Drill down into real-time metrics across 21 core organizational scopes.') }}</p>
            </div>
            
            <!-- Dynamic Filter Bar -->
            <div class="relative max-w-sm w-full">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-4 w-4" />
                </div>
                <input 
                    type="text" 
                    placeholder="Type to filter analytical scopes..." 
                    x-model="search"
                    class="block w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-950/30 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition"
                />
            </div>
        </div>

        <!-- Section Categories loop -->
        @foreach($groups as $groupKey => $group)
            <div class="space-y-5">
                
                <!-- Category Headers -->
                <div class="border-b border-gray-100 dark:border-gray-800 pb-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">{{ $group['title'] }}</h3>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ $group['description'] }}</p>
                </div>

                <!-- Structured Grid Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" data-group="{{ $groupKey }}">
                    @foreach($group['items'] as $item)
                        @php
                            $cardKey = strtolower($item['name']) . ' ' . strtolower($item['desc']);
                        @endphp
                        
                        <!-- Core Card Wrapper -->
                        <div 
                            class="group relative rounded-2xl border border-gray-150 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm hover:shadow-md hover:border-emerald-500 dark:hover:border-emerald-400 transition-all flex flex-col justify-between cursor-pointer min-h-[170px]"
                            x-show="'{{ addslashes(strtolower($cardKey)) }}'.includes(search.toLowerCase())"
                            wire:click="openAnalyticsPanel('{{ $item['id'] }}', '{{ $item['name'] }}')"
                        >
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="rounded-xl p-3 {!! $item['color_class'] !!}">
                                        <x-filament::icon :icon="$item['icon']" class="h-5 w-5" />
                                    </div>
                                    <span class="rounded-full bg-gray-50 dark:bg-gray-850 px-3 py-1 text-[9px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ $item['badge'] }}
                                    </span>
                                </div>

                                <div class="space-y-1.5">
                                    <h4 class="text-xs font-bold text-gray-900 dark:text-gray-100 tracking-tight group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                        {{ $item['name'] }}
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                                        {{ $item['desc'] }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-50 dark:border-gray-950 flex items-center justify-between text-xs font-semibold text-gray-400 dark:text-gray-500 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                <span>{{ __('Explore Operational Metrics') }}</span>
                                <span class="transform group-hover:translate-x-1.5 transition-transform">{{ __('→') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <!-- Interactive Empty Search Placeholder -->
        <div 
            class="text-center py-12 rounded-xl border border-dashed border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900"
            x-show="document.querySelectorAll('[data-group] > div:not([style*=\'display: none\'])').length === 0"
            style="display: none;"
        >
            <x-filament::icon icon="heroicon-o-magnifying-glass" class="mx-auto h-8 w-8 text-gray-400" />
            <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 mt-3">{{ __('No analytical scopes found') }}</h3>
            <p class="text-[10px] text-gray-400 mt-1">{{ __('Try refining your search keyword.') }}</p>
        </div>

        <!-- Slide-Over Drawer Backdrop Overlay (Static separation prevents card squeeze) -->
        <div 
            class="fixed inset-0 z-40 bg-gray-500/20 backdrop-blur-sm transition-opacity" 
            x-show="$wire.isPanelOpen"
            wire:click="closePanel"
            style="display: none;"
        ></div>

        <!-- Floating, Slide-Over Analytics Drawer -->
        <div 
            class="fixed inset-y-0 right-0 z-50 w-full max-w-lg bg-white dark:bg-gray-900 shadow-2xl flex flex-col justify-between border-l border-gray-150 dark:border-gray-800"
            x-show="$wire.isPanelOpen"
            x-transition:enter="transform transition ease-in-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transform transition ease-in-out duration-300"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            style="display: none;"
        >
            <div class="flex-1 h-0 overflow-y-auto p-6 space-y-6">
                
                <!-- Panel Header details -->
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-4">
                    <div class="space-y-1">
                        <h2 class="text-base font-bold text-gray-950 dark:text-gray-50 tracking-tight" id="slide-over-title">
                            {{ $activeCardName }}
                        </h2>
                        <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold uppercase tracking-wider">{{ __('Real-Time Operational Queries') }}</p>
                    </div>
                    <button type="button" class="rounded-lg text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-850 p-2" wire:click="closePanel">
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <!-- 1. PLOTLY INTERACTIVE CHART CONTAINER -->
                <div class="space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Visual Insights Plot') }}</h3>
                    <div class="rounded-xl border border-gray-100 dark:border-gray-850 bg-gray-50/50 dark:bg-gray-950/20 p-4">
                        <div id="analytics-chart" class="w-full"></div>
                    </div>
                </div>

                <!-- 2. DATABASE METRIC CARDS -->
                <div class="space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Database Counts & Metrics') }}</h3>
                    
                    <div class="space-y-3">
                        @if(!empty($analyticsData['KPIs']))
                            @foreach($analyticsData['KPIs'] as $kpi)
                                <div class="rounded-xl border border-gray-100 dark:border-gray-850 p-5 bg-gray-50/50 dark:bg-gray-950/20 flex items-center justify-between">
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider leading-tight">{{ $kpi['label'] }}</p>
                                        <h4 class="text-xl font-extrabold text-gray-900 dark:text-gray-100 mt-1 tracking-tight">{{ $kpi['value'] }}</h4>
                                    </div>
                                    @if(!empty($kpi['trend']))
                                        <span class="rounded-full bg-emerald-50 dark:bg-emerald-950/30 px-3 py-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-400">
                                            {{ $kpi['trend'] }}
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <!-- 3. INTERACTIVE ENTERPRISE DATA TABLE (Phase 5) -->
                @php
                    $tableData = $this->getTableRecords();
                @endphp
                @if(!empty($tableData['headings']))
                    <div class="space-y-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Records Ledger') }}</h3>
                            <button 
                                onclick="navigator.clipboard.writeText(document.getElementById('drawer-table-body').innerText); alert('Table data copied successfully.');"
                                class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline"
                            >
                                {{ __('Copy Table') }}
                            </button>
                        </div>

                        <!-- Table Filtering Bar -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-3.5 w-3.5" />
                            </div>
                            <input 
                                type="text" 
                                placeholder="Search this records subset..." 
                                wire:model.live.debounce.300ms="tableSearch"
                                class="block w-full pl-9 pr-4 py-1.5 text-xs rounded-lg border border-gray-150 dark:border-gray-850 bg-gray-50/50 dark:bg-gray-950/30 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none"
                            />
                        </div>

                        <!-- Data Table Grid -->
                        <div class="overflow-x-auto border border-gray-100 dark:border-gray-850 rounded-xl bg-white dark:bg-gray-900 shadow-sm">
                            <table class="w-full text-left border-collapse text-[11px]">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-gray-950/40 text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-850 font-bold uppercase tracking-wider">
                                        @foreach($tableData['headings'] as $heading)
                                            <th class="p-3 whitespace-nowrap">{{ $heading }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-850 text-gray-800 dark:text-gray-300" id="drawer-table-body">
                                    @forelse($tableData['rows'] as $row)
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-950/20">
                                            <td class="p-3 font-semibold text-gray-900 dark:text-gray-50">{{ $row['col1'] }}</td>
                                            <td class="p-3">{{ $row['col2'] }}</td>
                                            <td class="p-3">{{ $row['col3'] }}</td>
                                            <td class="p-3">{{ $row['col4'] }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="p-6 text-center text-gray-400">{{ __('No matching database lines found.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination controls -->
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-1">
                            <span>Showing {{ count($tableData['rows']) }} of {{ $tableData['total'] }} rows</span>
                            <div class="flex gap-2">
                                <button 
                                    wire:click="previousPage" 
                                    @disabled($tablePage === 1)
                                    class="px-2.5 py-1 rounded border border-gray-200 dark:border-gray-850 bg-white dark:bg-gray-950 hover:bg-gray-50 disabled:opacity-40"
                                >
                                    {{ __('Prev') }}
                                </button>
                                <button 
                                    wire:click="nextPage({{ $tableData['total'] }})" 
                                    @disabled($tablePage * $perPage >= $tableData['total'])
                                    class="px-2.5 py-1 rounded border border-gray-200 dark:border-gray-850 bg-white dark:bg-gray-950 hover:bg-gray-50 disabled:opacity-40"
                                >
                                    {{ __('Next') }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- 4. LOGICAL ANALYTICS INSIGHTS -->
                <div class="space-y-4">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500">{{ __('Statistical Analysis') }}</h3>
                    
                    <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 space-y-3">
                        @if(!empty($analyticsData['insights']))
                            @foreach($analyticsData['insights'] as $insight)
                                <div class="flex items-start gap-3">
                                    <span class="text-emerald-600 dark:text-emerald-400 mt-0.5">{{ __('✔') }}</span>
                                    <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">{{ $insight }}</p>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

            </div>

            <!-- Panel Action Button Block -->
            <div class="border-t border-gray-100 dark:border-gray-800 p-6 bg-gray-50/50 dark:bg-gray-950/20 flex gap-3">
                <a href="{{ \App\Filament\App\Pages\ReportGeneratorPage::getUrl() }}" class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 text-white font-bold text-xs px-4 py-3 hover:bg-emerald-500 transition">
                    <x-filament::icon icon="heroicon-o-document-chart-bar" class="h-4 w-4" />
                    {{ __('Build Full Report') }}
                </a>
                <button type="button" wire:click="closePanel" class="flex-1 inline-flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 font-bold text-xs px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-850 transition">
                    {{ __('Dismiss Panel') }}
                </button>
            </div>

        </div>

    </div>

    <!-- Plotly CDN & Event Listener (Bypasses rendering latency) -->
    <script src="https://cdn.plot.ly/plotly-2.35.2.min.js"></script>
    <script>
        window.addEventListener('renderChart', event => {
            // Wait 150ms for the slide-over transition to complete before plotting
            setTimeout(() => {
                const container = document.getElementById('analytics-chart');
                if (container) {
                    container.innerHTML = '';
                    const chartData = event.detail.chartData;
                    Plotly.newPlot('analytics-chart', chartData.traces, chartData.layout, { 
                        responsive: true, 
                        displayModeBar: false 
                    });
                }
            }, 150);
        });
    </script>
</x-filament-panels::page>