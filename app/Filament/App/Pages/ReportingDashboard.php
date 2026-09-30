<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Reports\Models\EnterpriseReportTemplate;
use Modules\Reports\Models\GeneratedReport;
use Modules\Reports\Models\ReportSchedule;
use Modules\Reports\Services\ReportExecutionService;

class ReportingDashboard extends Page
{
    use ModuleAwareActiveNavigation;
    use ModulePermissionAccess;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Reports & Intelligence';

    public static function getNavigationGroup(): ?string
    {

        return __(static::$navigationGroup);

    }

    protected static ?string $navigationLabel = 'Dashboard';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?string $title = 'Reports & Analytics Center';

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    protected static ?int $navigationSort = 0;

    protected static string $view = 'filament.app.pages.reporting-dashboard';

    public Collection $pinnedTemplates;

    public Collection $recentReports;

    public Collection $activeSchedules;

    public Collection $topTemplates;

    public int $totalReportsCount = 0;

    public int $savedTemplatesCount = 0;

    public int $activeSchedulesCount = 0;

    public int $successRate = 0;

    public int $recordsExtracted = 0;

    public int $downloadsCount = 0;

    // Analytics datasets (serialized for Plotly)
    public array $trendChart = [];

    public array $statusChart = [];

    public array $formatChart = [];

    public array $moduleChart = [];

    public function mount(): void
    {
        $this->loadDashboardData();
    }

    public function loadDashboardData(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        $schoolId = session('current_tenant')->id ?? ($user ? $user->school_id : null);

        if (! $schoolId) {
            $this->pinnedTemplates = collect();
            $this->recentReports = collect();
            $this->activeSchedules = collect();
            $this->topTemplates = collect();

            return;
        }

        $this->totalReportsCount = GeneratedReport::where('school_id', $schoolId)->count();
        $this->savedTemplatesCount = EnterpriseReportTemplate::where('school_id', $schoolId)->count();
        $this->activeSchedulesCount = ReportSchedule::where('school_id', $schoolId)->where('is_active', true)->count();

        $completed = GeneratedReport::where('school_id', $schoolId)->where('status', 'completed')->count();
        $this->recordsExtracted = (int) GeneratedReport::where('school_id', $schoolId)->sum('record_count');
        $this->downloadsCount = GeneratedReport::where('school_id', $schoolId)->where('is_downloaded', true)->count();
        $this->successRate = $this->totalReportsCount > 0 ? round(($completed / $this->totalReportsCount) * 100) : 0;

        // Query pinned or favorited templates
        $this->pinnedTemplates = EnterpriseReportTemplate::where('school_id', $schoolId)
            ->where(function ($query) {
                $query->where('is_pinned', true)
                    ->orWhere('is_favorite', true);
            })
            ->latest()
            ->limit(4)
            ->get();

        $this->recentReports = GeneratedReport::with('template')
            ->where('school_id', $schoolId)
            ->latest()
            ->limit(8)
            ->get();

        $this->activeSchedules = ReportSchedule::with('template')
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->latest()
            ->limit(3)
            ->get();

        $this->topTemplates = EnterpriseReportTemplate::withCount('generatedReports as run_count')
            ->withSum('generatedReports as records_sum', 'record_count')
            ->where('school_id', $schoolId)
            ->having('run_count', '>', 0)
            ->orderByDesc('run_count')
            ->limit(6)
            ->get();

        $this->buildAnalyticsCharts($schoolId);
    }

    protected function buildAnalyticsCharts(int $schoolId): void
    {
        $this->statusChart = $this->buildStatusChart($schoolId);
        $this->formatChart = $this->buildFormatChart($schoolId);
        $this->moduleChart = $this->buildModuleChart($schoolId);
        $this->trendChart = $this->buildTrendChart($schoolId);
    }

    protected function buildStatusChart(int $schoolId): array
    {
        $rows = GeneratedReport::where('school_id', $schoolId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $colors = [
            'completed' => '#10b981',
            'failed' => '#f43f5e',
            'pending' => '#f59e0b',
            'processing' => '#6366f1',
        ];

        $labels = [];
        $values = [];
        $traceColors = [];
        $order = ['completed', 'pending', 'processing', 'failed'];
        foreach ($order as $status) {
            $count = (int) ($rows[$status] ?? 0);
            if ($count > 0) {
                $labels[] = ucfirst($status);
                $values[] = $count;
                $traceColors[] = $colors[$status] ?? '#94a3b8';
            }
        }

        if (empty($values)) {
            return ['empty' => true];
        }

        return [
            'traces' => [[
                'values' => $values,
                'labels' => $labels,
                'type' => 'pie',
                'hole' => 0.55,
                'marker' => ['colors' => $traceColors],
                'textinfo' => 'label+percent',
                'hovertemplate' => '%{label}: %{value} runs (%{percent})',
            ]],
            'layout' => [
                'margin' => ['t' => 0, 'b' => 0, 'l' => 0, 'r' => 0],
                'height' => 240,
                'showlegend' => true,
                'legend' => ['orientation' => 'h', 'y' => -0.25, 'font' => ['size' => 11]],
                'paper_bgcolor' => 'rgba(0,0,0,0)',
                'plot_bgcolor' => 'rgba(0,0,0,0)',
            ],
        ];
    }

    protected function buildFormatChart(int $schoolId): array
    {
        $rows = GeneratedReport::where('school_id', $schoolId)
            ->select('format', DB::raw('COUNT(*) as count'))
            ->groupBy('format')
            ->pluck('count', 'format');

        $colors = [
            'pdf' => '#f43f5e',
            'csv' => '#10b981',
            'xls' => '#3b82f6',
            'xlsx' => '#3b82f6',
            'json' => '#a855f7',
        ];

        $labels = [];
        $values = [];
        $traceColors = [];
        foreach ($rows as $format => $count) {
            $labels[] = strtoupper($format);
            $values[] = (int) $count;
            $traceColors[] = $colors[$format] ?? '#94a3b8';
        }

        if (empty($values)) {
            return ['empty' => true];
        }

        return [
            'traces' => [[
                'x' => $labels,
                'y' => $values,
                'type' => 'bar',
                'marker' => ['color' => $traceColors],
                'hovertemplate' => '%{x}: %{y} files',
            ]],
            'layout' => [
                'margin' => ['t' => 0, 'b' => 30, 'l' => 35, 'r' => 10],
                'height' => 240,
                'paper_bgcolor' => 'rgba(0,0,0,0)',
                'plot_bgcolor' => 'rgba(0,0,0,0)',
                'yaxis' => ['gridcolor' => '#f1f5f9', 'zeroline' => false, 'tickfont' => ['size' => 10]],
                'xaxis' => ['zeroline' => false, 'tickfont' => ['size' => 10]],
            ],
        ];
    }

    protected function buildModuleChart(int $schoolId): array
    {
        $rows = DB::table('generated_reports as g')
            ->join('enterprise_report_templates as t', 't.id', '=', 'g.enterprise_report_template_id')
            ->where('g.school_id', $schoolId)
            ->select('t.module', DB::raw('COUNT(g.id) as count'))
            ->groupBy('t.module')
            ->orderByDesc('count')
            ->get();

        $palette = ['#10b981', '#3b82f6', '#6366f1', '#f59e0b', '#a855f7', '#f43f5e'];

        $labels = $rows->pluck('module')->map(fn ($m) => ucfirst($m))->toArray();
        $values = $rows->pluck('count')->map(fn ($c) => (int) $c)->toArray();
        $colors = array_slice($palette, 0, count($labels));

        if (empty($values)) {
            return ['empty' => true];
        }

        return [
            'traces' => [[
                'y' => $labels,
                'x' => $values,
                'type' => 'bar',
                'orientation' => 'h',
                'marker' => ['color' => $colors],
                'hovertemplate' => '%{y}: %{x} runs',
            ]],
            'layout' => [
                'margin' => ['t' => 0, 'b' => 30, 'l' => 80, 'r' => 10],
                'height' => 240,
                'paper_bgcolor' => 'rgba(0,0,0,0)',
                'plot_bgcolor' => 'rgba(0,0,0,0)',
                'yaxis' => ['gridcolor' => '#f1f5f9', 'zeroline' => false, 'tickfont' => ['size' => 10], 'automargin' => true],
                'xaxis' => ['zeroline' => false, 'tickfont' => ['size' => 10]],
            ],
        ];
    }

    protected function buildTrendChart(int $schoolId): array
    {
        // Last 14 days including today (zero-filled)
        $days = [];
        for ($i = 13; $i >= 0; $i--) {
            $days[now()->subDays($i)->format('Y-m-d')] = now()->subDays($i)->format('M j');
        }

        $rows = GeneratedReport::where('school_id', $schoolId)
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->select(DB::raw('DATE(created_at) as day'), 'status', DB::raw('COUNT(*) as count'))
            ->groupBy('day', 'status')
            ->get();

        $completed = array_fill(0, 14, 0);
        $failed = array_fill(0, 14, 0);
        $labels = array_values($days);
        $keys = array_keys($days);

        foreach ($rows as $row) {
            $idx = array_search($row->day, $keys);
            if ($idx !== false) {
                if ($row->status === 'completed') {
                    $completed[$idx] = (int) $row->count;
                } else {
                    $failed[$idx] = (int) $row->count;
                }
            }
        }

        return [
            'traces' => [
                [
                    'x' => $labels,
                    'y' => $completed,
                    'name' => 'Completed',
                    'type' => 'scatter',
                    'mode' => 'lines+markers',
                    'line' => ['color' => '#10b981', 'width' => 2.5],
                    'fill' => 'tonexty',
                    'fillcolor' => 'rgba(16,185,129,0.15)',
                    'hovertemplate' => '%{x}: %{y} completed',
                ],
                [
                    'x' => $labels,
                    'y' => $failed,
                    'name' => 'Failed',
                    'type' => 'scatter',
                    'mode' => 'lines+markers',
                    'line' => ['color' => '#f43f5e', 'width' => 2],
                    'fill' => 'tonexty',
                    'fillcolor' => 'rgba(244,63,94,0.12)',
                    'hovertemplate' => '%{x}: %{y} failed',
                ],
            ],
            'layout' => [
                'margin' => ['t' => 0, 'b' => 30, 'l' => 40, 'r' => 10],
                'height' => 260,
                'legend' => ['orientation' => 'h', 'y' => 1.15, 'font' => ['size' => 11]],
                'paper_bgcolor' => 'rgba(0,0,0,0)',
                'plot_bgcolor' => 'rgba(0,0,0,0)',
                'yaxis' => ['gridcolor' => '#f1f5f9', 'zeroline' => false, 'tickfont' => ['size' => 10], 'rangemode' => 'tozero'],
                'xaxis' => ['zeroline' => false, 'tickfont' => ['size' => 10]],
            ],
        ];
    }

    public function compileTemplate(int $templateId): void
    {
        $template = EnterpriseReportTemplate::find($templateId);

        if (! $template) {
            Notification::make()
                ->title(__('Template Not Found'))
                ->danger()
                ->send();

            return;
        }

        $report = app(ReportExecutionService::class)->execute($template, 'pdf', [], Auth::id());

        if ($report->status === 'completed') {
            Notification::make()
                ->title(__('Report Compiled'))
                ->success()
                ->body("{$template->name} has been processed and saved.")
                ->send();

            $this->loadDashboardData();
        } else {
            Notification::make()
                ->title(__('Compilation Failed'))
                ->danger()
                ->body($report->error_message)
                ->send();
        }
    }
}
