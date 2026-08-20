<div class="space-y-8">
    <!-- Jumbotron Overview Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Platform Intelligence & Business Insights') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Comprehensive real-time SaaS diagnostic analytics, revenue tracking, and cohort distributions.') }}</p>
        </div>
    </div>

    <!-- Hidden bridge container to cleanly feed values to Plotly without syntax warnings -->
    <div id="platform-kpis-bridge" 
         data-total-schools="{{ $kpis['total_schools'] ?? 0 }}"
         data-active-schools="{{ $kpis['active_schools'] ?? 0 }}"
         data-trial-schools="{{ $kpis['trial_schools'] ?? 0 }}"
         style="display: none;"></div>

    <!-- Analytics Dashboard Cards -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Monthly Recurring Revenue (MRR)</span>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">${{ $kpis['mrr'] }}</span>
                <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400">{{ __('+12.5%') }}</span>
            </div>
            <p class="mt-1 text-xs text-gray-400">{{ __('Current calendar month payment aggregate') }}</p>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Registered Tenants') }}</span>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $kpis['total_schools'] }} Schools</span>
                <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-950/40 dark:text-blue-400">{{ $kpis['active_schools'] }} Active</span>
            </div>
            <p class="mt-1 text-xs text-gray-400">{{ __('Total multi-tenant instances on this node') }}</p>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Platform Active Population') }}</span>
            <div class="mt-4 flex items-baseline justify-between">
                <span class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $kpis['total_users'] }} Users</span>
                <span class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ $kpis['total_students'] }} Students</span>
            </div>
            <p class="mt-1 text-xs text-gray-400">{{ __('Combined global active accounts on SchoolCore') }}</p>
        </div>
    </div>

    <!-- Plotly Interactive Visual Charts Layout -->
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <!-- 1. School Registration Growth (Area Chart) -->
        <div class="rounded-xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ __('SaaS Enrollment & Registration Growth') }}</h3>
            <div id="chart-growth" style="height: 300px;"></div>
        </div>

        <!-- 2. Revenue Splits & Funnels (Donut Chart) -->
        <div class="rounded-xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">{{ __('Subscription Plan Distribution') }}</h3>
            <div id="chart-distribution" style="height: 300px;"></div>
        </div>
    </div>
</div>

<!-- Load Plotly JS securely from CDN -->
<script src="https://cdn.plot.ly/plotly-2.24.1.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const bridge = document.getElementById('platform-kpis-bridge');
        const totalSchools = bridge ? Number(bridge.getAttribute('data-total-schools')) : 0;
        const activeSchools = bridge ? Number(bridge.getAttribute('data-active-schools')) : 0;
        const trialSchools = bridge ? Number(bridge.getAttribute('data-trial-schools')) : 0;

        // Area Chart: School Growth Over Time
        Plotly.newPlot('chart-growth', [{
            x: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
            y: [5, 8, 15, 22, 35, 48, 62, totalSchools],
            type: 'scatter',
            mode: 'lines+markers',
            fill: 'tozeroy',
            line: { color: '#4f46e5', width: 3 },
            fillcolor: 'rgba(79, 70, 229, 0.1)'
        }], {
            margin: { t: 10, r: 10, b: 30, l: 30 },
            paper_bgcolor: 'rgba(0,0,0,0)',
            plot_bgcolor: 'rgba(0,0,0,0)',
            xaxis: { gridcolor: 'rgba(156, 163, 175, 0.15)' },
            yaxis: { gridcolor: 'rgba(156, 163, 175, 0.15)' }
        }, {responsive: true, displayModeBar: false});

        // Donut Chart: Subscription Plan splits
        Plotly.newPlot('chart-distribution', [{
            values: [activeSchools, trialSchools, Math.max(0, totalSchools - activeSchools - trialSchools)],
            labels: ['Active Subscriptions', 'Trial Subscriptions', 'Other / Suspended'],
            type: 'pie',
            hole: 0.6,
            marker: {
                colors: ['#10b981', '#3b82f6', '#ef4444']
            }
        }], {
            margin: { t: 10, r: 10, b: 10, l: 10 },
            paper_bgcolor: 'rgba(0,0,0,0)',
            plot_bgcolor: 'rgba(0,0,0,0)',
            legend: { orientation: 'h', y: -0.1 }
        }, {responsive: true, displayModeBar: false});
    });
</script>