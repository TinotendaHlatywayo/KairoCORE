<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Reports\Models\EnterpriseReportTemplate;
use Modules\Reports\Models\GeneratedReport;
use Modules\Reports\Models\ReportSchedule;

class ReportingDashboardOverview extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $schoolId = current_tenant()?->id;

        if (! $schoolId) {
            return [];
        }

        $compiledTrend = collect(range(6, 0))->map(function ($daysAgo) use ($schoolId) {
            return GeneratedReport::withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->whereDate('created_at', now()->subDays($daysAgo)->toDateString())
                ->count();
        })->all();

        return [
            Stat::make('Total Compiled Reports', GeneratedReport::where('school_id', $schoolId)->count())
                ->description(__('Total historical report runs stored in archive'))
                ->descriptionIcon('heroicon-m-document-duplicate')
                ->color('success')
                ->chart($compiledTrend),

            Stat::make('Saved Custom Templates', EnterpriseReportTemplate::where('school_id', $schoolId)->count())
                ->description(__('Custom layouts configured for re-run extraction'))
                ->descriptionIcon('heroicon-m-swatch')
                ->color('info'),

            Stat::make('Automated Distribution Tasks', ReportSchedule::where('school_id', $schoolId)->where('is_active', true)->count())
                ->description(__('Active recurring automated email tasks'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
