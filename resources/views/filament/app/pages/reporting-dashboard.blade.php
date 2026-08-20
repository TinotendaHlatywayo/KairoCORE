<x-filament-panels::page>
    <div class="space-y-8">

        <!-- Welcome Jumbotron -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-800 p-8 text-white shadow-md">
            <div class="relative z-10 max-w-2xl">
                <h2 class="text-2xl font-bold tracking-tight">{{ __('Reports & Analytics Center') }}</h2>
                <p class="mt-2 text-sm text-emerald-100">
                    {{ __('Live analytics on report production, run success, output formats and template usage across your school.') }}
                </p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ \App\Filament\App\Pages\ReportGeneratorPage::getUrl() }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-xs font-semibold text-emerald-800 shadow-sm transition hover:bg-emerald-50">
                        <x-filament::icon icon="heroicon-m-plus" class="h-4 w-4" />
                        {{ __('New Generation Run') }}
                    </a>
                    <a href="{{ \App\Filament\App\Resources\EnterpriseReportTemplateResource::getUrl('index') }}" class="inline-flex items-center gap-2 rounded-lg border border-emerald-400 bg-transparent px-4 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700/50">
                        <x-filament::icon icon="heroicon-m-swatch" class="h-4 w-4" />
                        {{ __('Configure Templates') }}
                    </a>
                </div>
            </div>
            <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-emerald-500/20 blur-2xl"></div>
        </div>

        <!-- Metric KPI Cards -->
        <div class="grid grid-cols-2 gap-5 lg:grid-cols-6">
            <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm transition hover:shadow-md">
                <div class="rounded-lg bg-emerald-50 dark:bg-emerald-950/50 p-2.5 text-emerald-600 dark:text-emerald-400 inline-block">
                    <x-filament::icon icon="heroicon-o-document-duplicate" class="h-5 w-5" />
                </div>
                <p class="mt-3 text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Reports Compiled') }}</p>
                <h4 class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-0.5">{{ number_format($totalReportsCount) }}</h4>
            </div>

            <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm transition hover:shadow-md">
                <div class="rounded-lg bg-teal-50 dark:bg-teal-950/50 p-2.5 text-teal-600 dark:text-teal-400 inline-block">
                    <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5" />
                </div>
                <p class="mt-3 text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Success Rate') }}</p>
                <h4 class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-0.5">{{ $successRate }}%</h4>
            </div>

            <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm transition hover:shadow-md">
                <div class="rounded-lg bg-blue-50 dark:bg-blue-950/50 p-2.5 text-blue-600 dark:text-blue-400 inline-block">
                    <x-filament::icon icon="heroicon-o-swatch" class="h-5 w-5" />
                </div>
                <p class="mt-3 text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Custom Layouts') }}</p>
                <h4 class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-0.5">{{ number_format($savedTemplatesCount) }}</h4>
            </div>

            <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm transition hover:shadow-md">
                <div class="rounded-lg bg-amber-50 dark:bg-amber-950/50 p-2.5 text-amber-600 dark:text-amber-400 inline-block">
                    <x-filament::icon icon="heroicon-o-clock" class="h-5 w-5" />
                </div>
                <p class="mt-3 text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Active Schedules') }}</p>
                <h4 class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-0.5">{{ $activeSchedulesCount }}</h4>
            </div>

            <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm transition hover:shadow-md">
                <div class="rounded-lg bg-indigo-50 dark:bg-indigo-950/50 p-2.5 text-indigo-600 dark:text-indigo-400 inline-block">
                    <x-filament::icon icon="heroicon-o-table-cells" class="h-5 w-5" />
                </div>
                <p class="mt-3 text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Records Extracted') }}</p>
                <h4 class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-0.5">{{ number_format($recordsExtracted) }}</h4>
            </div>

            <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm transition hover:shadow-md">
                <div class="rounded-lg bg-rose-50 dark:bg-rose-950/50 p-2.5 text-rose-600 dark:text-rose-400 inline-block">
                    <x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-5 w-5" />
                </div>
                <p class="mt-3 text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Downloads') }}</p>
                <h4 class="text-xl font-bold text-gray-900 dark:text-gray-100 mt-0.5">{{ number_format($downloadsCount) }}</h4>
            </div>
        </div>

        <!-- Analytics Charts Row 1 -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Report Trend -->
            <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-chart-bar" class="h-5 w-5 text-emerald-600" />
                            {{ __('Report Production Trend') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Successful vs failed runs over the last 14 days.') }}</p>
                    </div>
                    <span class="rounded-full bg-gray-50 dark:bg-gray-850 px-3 py-1 text-[9px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('14 Day') }}</span>
                </div>
                @if(empty($trendChart['empty'] ?? true))
                    <div id="trend-chart" class="mt-4 w-full"></div>
                @else
                    <div class="mt-6 py-10 text-center text-xs text-gray-400">{{ __('No report runs in the last 14 days.') }}</div>
                @endif
            </div>

            <!-- Status Distribution -->
            <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-circle-stack" class="h-5 w-5 text-teal-600" />
                            {{ __('Run Status Distribution') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Share of completed, pending and failed compilations.') }}</p>
                    </div>
                    <span class="rounded-full bg-gray-50 dark:bg-gray-850 px-3 py-1 text-[9px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('All Time') }}</span>
                </div>
                @if(empty($statusChart['empty'] ?? true))
                    <div id="status-chart" class="mt-4 w-full"></div>
                @else
                    <div class="mt-6 py-10 text-center text-xs text-gray-400">{{ __('No report runs recorded.') }}</div>
                @endif
            </div>
        </div>

        <!-- Analytics Charts Row 2 -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Format Distribution -->
            <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5 text-blue-600" />
                            {{ __('Output Format Usage') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Count of generated files by export format.') }}</p>
                    </div>
                </div>
                @if(empty($formatChart['empty'] ?? true))
                    <div id="format-chart" class="mt-4 w-full"></div>
                @else
                    <div class="mt-6 py-10 text-center text-xs text-gray-400">{{ __('No generated files recorded.') }}</div>
                @endif
            </div>

            <!-- Module Distribution -->
            <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-squares-2x2" class="h-5 w-5 text-indigo-600" />
                            {{ __('Runs by Module') }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Report generation volume per operational module.') }}</p>
                    </div>
                </div>
                @if(empty($moduleChart['empty'] ?? true))
                    <div id="module-chart" class="mt-4 w-full"></div>
                @else
                    <div class="mt-6 py-10 text-center text-xs text-gray-400">{{ __('No module-linked report runs recorded.') }}</div>
                @endif
            </div>
        </div>

        <!-- Main Content Area split -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">

                <!-- Top Templates -->
                <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-trophy" class="h-5 w-5 text-amber-600" />
                            {{ __('Most Used Templates') }}
                        </h3>
                        <a href="{{ \App\Filament\App\Resources\EnterpriseReportTemplateResource::getUrl('index') }}" class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">{{ __('View All') }}</a>
                    </div>

                    <div class="mt-4 overflow-hidden border border-gray-100 dark:border-gray-800 rounded-lg">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-950/40 text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-800">
                                    <th class="p-3">{{ __('Template') }}</th>
                                    <th class="p-3 text-center">{{ __('Module') }}</th>
                                    <th class="p-3 text-center">{{ __('Runs') }}</th>
                                    <th class="p-3 text-right">{{ __('Records') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-xs">
                                @forelse($topTemplates as $template)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-950/20">
                                        <td class="p-3 font-semibold text-gray-900 dark:text-gray-100 max-w-[220px] truncate">{{ $template->name }}</td>
                                        <td class="p-3 text-center">
                                            <span class="rounded px-2 py-0.5 text-[9px] font-bold uppercase bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700">
                                                {{ $template->module }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-center font-mono text-[10px] text-gray-500 dark:text-gray-400">{{ $template->run_count }}</td>
                                        <td class="p-3 text-right font-mono text-[10px] text-gray-500 dark:text-gray-400">{{ number_format($template->records_sum) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="p-8 text-center text-xs text-gray-400">
                                            {{ __('No templates have been run yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Run History logs -->
                <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-queue-list" class="h-5 w-5 text-emerald-600" />
                            {{ __('Recent Generation Logs') }}
                        </h3>
                        <a href="{{ \App\Filament\App\Resources\GeneratedReportResource::getUrl('index') }}" class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">{{ __('View All') }}</a>
                    </div>

                    <div class="mt-4 overflow-hidden border border-gray-100 dark:border-gray-800 rounded-lg">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-950/40 text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-gray-800">
                                    <th class="p-3">{{ __('Report Name') }}</th>
                                    <th class="p-3 text-center">{{ __('Format') }}</th>
                                    <th class="p-3 text-center">{{ __('Records') }}</th>
                                    <th class="p-3 text-center">{{ __('Status') }}</th>
                                    <th class="p-3 text-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-xs">
                                @forelse($recentReports as $report)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-950/20">
                                        <td class="p-3 font-semibold text-gray-900 dark:text-gray-100 max-w-[200px] truncate">
                                            {{ $report->name }}
                                        </td>
                                        <td class="p-3 text-center">
                                            <span class="rounded px-2 py-0.5 text-[9px] font-bold uppercase {{ $report->format === 'pdf' ? 'bg-rose-50 dark:bg-rose-950/30 text-rose-700' : 'bg-green-50 dark:bg-green-950/30 text-green-700' }}">
                                                {{ $report->format }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-center text-gray-500 dark:text-gray-400 font-mono text-[10px]">
                                            {{ number_format($report->record_count) }} rows
                                        </td>
                                        <td class="p-3 text-center">
                                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[9px] font-medium capitalize {{ $report->status === 'completed' ? 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700' : 'bg-amber-50 dark:bg-amber-950/30 text-amber-700' }}">
                                                {{ $report->status }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-right">
                                            @if($report->status === 'completed' && $report->file_path)
                                                <a href="{{ asset('storage/' . $report->file_path) }}" target="_blank" class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline">{{ __('Download') }}</a>
                                            @else
                                                <span class="text-[10px] text-gray-400">{{ __('-') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-xs text-gray-400">
                                            {{ __('No report runs recorded in the system log.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Side: Scheduled Tasks & Quick Links -->
            <div class="space-y-6">
                <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-rocket-launch" class="h-5 w-5 text-emerald-600" />
                        {{ __('Quick Access Nodes') }}
                    </h3>
                    <div class="mt-4 space-y-2">
                        <a href="{{ \App\Filament\App\Pages\ReportGeneratorPage::getUrl() }}" class="flex items-center justify-between rounded-lg border border-gray-100 dark:border-gray-850 p-3 hover:bg-gray-50/50 dark:hover:bg-gray-950/30 text-xs font-medium">
                            <span class="text-gray-700 dark:text-gray-300">{{ __('Launch Custom Creator') }}</span>
                            <x-filament::icon icon="heroicon-m-chevron-right" class="h-4 w-4 text-gray-400" />
                        </a>
                        <a href="{{ \App\Filament\App\Resources\EnterpriseReportTemplateResource::getUrl('index') }}" class="flex items-center justify-between rounded-lg border border-gray-100 dark:border-gray-850 p-3 hover:bg-gray-50/50 dark:hover:bg-gray-950/30 text-xs font-medium">
                            <span class="text-gray-700 dark:text-gray-300">{{ __('Saved Layout Presets') }}</span>
                            <span class="rounded bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-[9px] font-bold text-gray-500">{{ $savedTemplatesCount }}</span>
                        </a>
                        <a href="{{ \App\Filament\App\Resources\GeneratedReportResource::getUrl('index') }}" class="flex items-center justify-between rounded-lg border border-gray-100 dark:border-gray-850 p-3 hover:bg-gray-50/50 dark:hover:bg-gray-950/30 text-xs font-medium">
                            <span class="text-gray-700 dark:text-gray-300">{{ __('Open Report Archive') }}</span>
                            <span class="rounded bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-[9px] font-bold text-gray-500">{{ $totalReportsCount }}</span>
                        </a>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-clock" class="h-5 w-5 text-emerald-600" />
                        {{ __('Recurring Automated Jobs') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Automated PDF deliveries dispatched directly to emails.') }}</p>

                    <div class="mt-4 space-y-3">
                        @forelse($activeSchedules as $schedule)
                            <div class="rounded-lg border border-gray-100 dark:border-gray-850 p-3 bg-gray-50/50 dark:bg-gray-950/20">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-bold text-gray-900 dark:text-gray-100 max-w-[120px] truncate">{{ $schedule->name }}</h4>
                                    <span class="rounded-full bg-amber-50 dark:bg-amber-950/30 px-2 py-0.5 text-[9px] font-bold text-amber-700 uppercase tracking-tight">
                                        {{ $schedule->frequency }}
                                    </span>
                                </div>
                                <p class="text-[9px] text-gray-400 mt-2">Next Run: {{ $schedule->next_run_at ? $schedule->next_run_at->format('d-M-Y H:i') : 'Pending Execution' }}</p>
                            </div>
                        @empty
                            <div class="py-6 text-center text-xs text-gray-400">
                                {{ __('No active distribution calendars registered.') }}
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Pinned Layouts -->
                <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-star" class="h-5 w-5 text-emerald-600" />
                        {{ __('Favorite & Pinned') }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Click any card to compile an updated snapshot.') }}</p>

                    <div class="mt-4 space-y-3">
                        @forelse($pinnedTemplates as $template)
                            <div class="group rounded-lg border border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-950/30 p-3 transition hover:bg-white dark:hover:bg-gray-900 hover:shadow-sm">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[9px] font-bold text-emerald-600 dark:text-emerald-400 uppercase bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded">{{ $template->module }}</span>
                                    <span class="text-[9px] text-gray-400">{{ ucfirst($template->orientation) }}</span>
                                </div>
                                <h4 class="mt-2 text-xs font-bold text-gray-900 dark:text-gray-100 tracking-tight group-hover:text-emerald-600">{{ $template->name }}</h4>
                                <button wire:click="compileTemplate({{ $template->id }})" wire:loading.attr="disabled" class="mt-3 flex items-center gap-1.5 text-[10px] font-semibold text-emerald-700 dark:text-emerald-400 transition hover:underline">
                                    <span wire:loading.remove wire:target="compileTemplate({{ $template->id }})">{{ __('⚡ Compile Snapshot') }}</span>
                                    <span wire:loading wire:target="compileTemplate({{ $template->id }})">{{ __('⚙️ Compiling...') }}</span>
                                </button>
                            </div>
                        @empty
                            <div class="py-6 text-center text-xs text-gray-400">
                                {{ __('No layouts are pinned yet.') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

    </div>

    @if(!empty($trendChart['traces'] ?? null) || !empty($statusChart['traces'] ?? null) || !empty($formatChart['traces'] ?? null) || !empty($moduleChart['traces'] ?? null))
        <script src="https://cdn.plot.ly/plotly-2.35.2.min.js"></script>
        <script>
            const plotlyChart = (el, data, layout) => Plotly.newPlot(el, data, layout, { responsive: true, displayModeBar: false });

            @if(!empty($trendChart['traces'] ?? null))
                document.addEventListener('DOMContentLoaded', () => {
                    const el = document.getElementById('trend-chart');
                    if (el) plotlyChart(el, @json($trendChart['traces']), @json($trendChart['layout']));
                });
            @endif
            @if(!empty($statusChart['traces'] ?? null))
                document.addEventListener('DOMContentLoaded', () => {
                    const el = document.getElementById('status-chart');
                    if (el) plotlyChart(el, @json($statusChart['traces']), @json($statusChart['layout']));
                });
            @endif
            @if(!empty($formatChart['traces'] ?? null))
                document.addEventListener('DOMContentLoaded', () => {
                    const el = document.getElementById('format-chart');
                    if (el) plotlyChart(el, @json($formatChart['traces']), @json($formatChart['layout']));
                });
            @endif
            @if(!empty($moduleChart['traces'] ?? null))
                document.addEventListener('DOMContentLoaded', () => {
                    const el = document.getElementById('module-chart');
                    if (el) plotlyChart(el, @json($moduleChart['traces']), @json($moduleChart['layout']));
                });
            @endif
        </script>
    @endif
</x-filament-panels::page>
