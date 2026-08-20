<?php

namespace App\Filament\App\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserRoleStatisticsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $schoolId = current_tenant()?->id;

        if (! $schoolId) {
            return [];
        }

        $teachingCount = User::where('school_id', $schoolId)->where('requested_role', 'teaching_staff')->count();
        $nonTeachingCount = User::where('school_id', $schoolId)->where('requested_role', 'non_teaching_staff')->count();
        $adminCount = User::where('school_id', $schoolId)->whereIn('requested_role', ['administrator', 'admin'])->count();
        $studentCount = User::where('school_id', $schoolId)->where('requested_role', 'student')->count();

        return [
            Stat::make(__('Teaching Staff'), $teachingCount)
                ->description(__('Registered teaching personnel'))
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('success'),

            Stat::make(__('Non-Teaching Staff'), $nonTeachingCount)
                ->description(__('Support & administrative staff'))
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),

            Stat::make(__('Administrators'), $adminCount)
                ->description(__('School administrators'))
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('warning'),

            Stat::make(__('Students'), $studentCount)
                ->description(__('Enrolled students'))
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
}
