<?php

namespace App\Filament\App\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\HR\Models\Employee;
use Modules\Students\Models\Student;

class UserRoleStatisticsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $schoolId = current_tenant()?->id;

        if (! $schoolId) {
            return [];
        }

        // Headline counts come from the REAL directory records (students and
        // employees), not from user accounts — self-registration accounts are
        // only a subset of the people actually enrolled/employed at the school.
        $studentCount = Student::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->count();

        $teachingCount = Employee::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('role', 'teaching_staff')
            ->whereNull('deleted_at')
            ->count();

        $nonTeachingCount = Employee::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('role', 'non_teaching_staff')
            ->whereNull('deleted_at')
            ->count();

        $adminCount = User::where('school_id', $schoolId)
            ->whereIn('requested_role', ['administrator', 'admin'])
            ->count();

        $girls = Student::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('status', 'active')->where('gender', 'female')
            ->count();
        $boys = max(0, $studentCount - $girls);

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
                ->description(__('Enrolled students — :g girls · :b boys', ['g' => $girls, 'b' => $boys]))
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
}
