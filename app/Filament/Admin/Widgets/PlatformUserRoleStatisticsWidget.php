<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformUserRoleStatisticsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $teachingCount = User::where('requested_role', 'teaching_staff')->count();
        $nonTeachingCount = User::where('requested_role', 'non_teaching_staff')->count();
        $adminCount = User::whereIn('requested_role', ['administrator', 'admin'])->count();
        $studentCount = User::where('requested_role', 'student')->count();

        return [
            Stat::make(__('Platform Teaching Staff'), $teachingCount)
                ->description(__('Total teaching staff across all institutions'))
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),

            Stat::make(__('Platform Non-Teaching Staff'), $nonTeachingCount)
                ->description(__('Total non-teaching support staff'))
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),

            Stat::make(__('Platform Administrators'), $adminCount)
                ->description(__('Total school administrators'))
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('warning'),

            Stat::make(__('Platform Students'), $studentCount)
                ->description(__('Total enrolled students across all schools'))
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
}
