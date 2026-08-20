<div class="space-y-8" 
     id="schoolcore-dashboard-wrapper" 
     data-chart-primary="{{ $chart_primary }}" 
     data-chart-accent="{{ $chart_accent }}">
     
    <!-- Row 1: Personalized Welcome Header & Dynamic Term Progress Widget -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 flex items-center justify-between rounded-xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 schoolcore-glowing-card">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">{{ $greeting }}, {{ $user_name }}</h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Welcome to') }} <span class="font-semibold text-theme-primary" style="color: var(--theme-primary);">{{ $school_name }}</span>{{ __('. Access real-time data grids and configuration utilities securely.') }}</p>
            </div>
        </div>

        <!-- Term Progress Ring Widget (Dynamic calculations) [1] -->
        <div class="flex items-center justify-between rounded-xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 schoolcore-glowing-card">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">{{ $term_name }} Progress</span>
                <h3 class="mt-1 text-base font-bold text-gray-900 dark:text-white">{{ $term_days_left }} Days Remaining</h3>
            </div>
            <div class="relative flex items-center justify-center">
                @php
                    $dasharray = 175;
                    $dashoffset = $dasharray - (($term_progress / 100) * $dasharray);
                @endphp
                <svg class="h-16 w-16 transform -rotate-90">
                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="6" class="text-gray-100 dark:text-gray-800" fill="transparent" />
                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="6" class="text-theme-accent" fill="transparent"
                            style="stroke: var(--theme-accent);"
                            stroke-dasharray="{{ $dasharray }}"
                            stroke-dashoffset="{{ $dashoffset }}" />
                </svg>
                <span class="absolute text-xs font-bold text-gray-900 dark:text-white">{{ $term_progress }}%</span>
            </div>
        </div>
    </div>

    <!-- Row 2: Magnetic Quick Action Shortcuts Hub -->
    <div class="rounded-xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 schoolcore-glowing-card">
        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-4">{{ __('Magnetic Fast Action Center') }}</h2>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <a href="{{ route('filament.app.pages.system-settings-page') }}" class="group flex flex-col items-center justify-center rounded-lg border border-gray-100 bg-gray-50/50 p-4 transition-all duration-200 hover:border-theme-primary hover:bg-theme-primary/10 dark:border-gray-800 dark:bg-gray-950/40">
                <x-heroicon-o-paint-brush class="h-6 w-6 text-theme-primary transition group-hover:scale-110" style="color: var(--theme-primary);" />
                <span class="mt-2 text-xs font-semibold text-gray-800 dark:text-gray-200">{{ __('System Settings') }}</span>
            </a>
            <a href="{{ route('filament.app.resources.custom-roles.index') }}" class="group flex flex-col items-center justify-center rounded-lg border border-gray-100 bg-gray-50/50 p-4 transition-all duration-200 hover:border-theme-primary hover:bg-theme-primary/10 dark:border-gray-800 dark:bg-gray-950/40">
                <x-heroicon-o-finger-print class="h-6 w-6 text-theme-primary transition group-hover:scale-110" style="color: var(--theme-primary);" />
                <span class="mt-2 text-xs font-semibold text-gray-800 dark:text-gray-200">{{ __('Configure Roles') }}</span>
            </a>
            <a href="{{ route('filament.app.resources.departments.index') }}" class="group flex flex-col items-center justify-center rounded-lg border border-gray-100 bg-gray-50/50 p-4 transition-all duration-200 hover:border-theme-primary hover:bg-theme-primary/10 dark:border-gray-800 dark:bg-gray-950/40">
                <x-heroicon-o-building-office class="h-6 w-6 text-theme-primary transition group-hover:scale-110" style="color: var(--theme-primary);" />
                <span class="mt-2 text-xs font-semibold text-gray-800 dark:text-gray-200">{{ __('Departments') }}</span>
            </a>
            <a href="{{ route('filament.app.resources.system-audit-logs.index') }}" class="group flex flex-col items-center justify-center rounded-lg border border-gray-100 bg-gray-50/50 p-4 transition-all duration-200 hover:border-theme-primary hover:bg-theme-primary/10 dark:border-gray-800 dark:bg-gray-950/40">
                <x-heroicon-o-clock class="h-6 w-6 text-theme-primary transition group-hover:scale-110" style="color: var(--theme-primary);" />
                <span class="mt-2 text-xs font-semibold text-gray-800 dark:text-gray-200">{{ __('Compliance Log') }}</span>
            </a>
        </div>
    </div>

    <!-- Row 3: Glowing Card Indicators with Integrated Sparklines -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($kpis as $kpi)
            <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 schoolcore-glowing-card">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $kpi['label'] }}</span>
                    <div class="rounded-lg p-2 text-theme-primary dark:text-theme-accent" style="color: var(--theme-primary);">
                        <x-dynamic-component :component="$kpi['icon']" class="h-5 w-5" />
                    </div>
                </div>
                <div class="mt-4 flex items-end justify-between">
                    <div>
                        <span class="text-3xl font-extrabold tracking-tight text-gray-900 dark:text-white">{{ $kpi['value'] }}</span>
                        <p class="mt-1 text-xs text-gray-400">{{ $kpi['subtext'] }}</p>
                    </div>
                    <div class="h-10 w-28">
                        <svg class="h-full w-full text-theme-accent" viewBox="0 0 100 30" style="color: var(--theme-accent);">
                            <path d="M {{ $kpi['sparkline'] }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Row 4: Dynamic Analytics Charting Grid (Dual Student & Staff Line + Donut Chart) [1] -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 schoolcore-glowing-card">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4">{{ __('Weekly Attendance Distribution') }}</h3>
            <div class="h-64">
                <canvas id="weekly-attendance-canvas"></canvas>
            </div>
        </div>
        <div class="rounded-xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 schoolcore-glowing-card">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4">{{ __('Termly Tuition Collection Status') }}</h3>
            <div class="h-64">
                <canvas id="fee-collection-canvas"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Load ChartJS securely from CDN and map colors dynamically on runtime load -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const wrapper = document.getElementById('schoolcore-dashboard-wrapper');
        const primaryColor = wrapper ? wrapper.getAttribute('data-chart-primary') : '#15803d';
        const accentColor = wrapper ? wrapper.getAttribute('data-chart-accent') : '#eab308';

        // 1. Weekly Attendance (Both Student & Staff) [1]
        const attCanvas = document.getElementById('weekly-attendance-canvas');
        if (attCanvas) {
            new Chart(attCanvas, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                    datasets: [
                        {
                            label: 'Student Attendance (%)',
                            data: [96, 94, 95, 91, 95],
                            borderColor: primaryColor,
                            backgroundColor: primaryColor + '10',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 3
                        },
                        {
                            label: 'Staff Attendance (%)',
                            data: [98, 97, 98, 96, 99],
                            borderColor: accentColor,
                            backgroundColor: accentColor + '10',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { min: 80, max: 100 }
                    }
                }
            });
        }

        // 2. Fees Collection Donut Chart
        const feeCanvas = document.getElementById('fee-collection-canvas');
        if (feeCanvas) {
            new Chart(feeCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Collected', 'Outstanding'],
                    datasets: [{
                        data: [72, 28],
                        backgroundColor: [primaryColor, accentColor],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
    });
</script>
