<?php

namespace App\Filament\App\Widgets;

use App\Models\User;
use App\Services\UserRegistrationService;
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
        $studentCount = Student::where('school_id', $schoolId)
            ->where('status', 'active')
            ->count();

        // Employee directory role values are human labels ('Teacher',
        // 'Support Staff', ...); the demo seeder stores the legacy
        // 'teaching_staff' / 'non_teaching_staff' codes. Count both spellings
        // so every real teaching employee shows up on the dashboard.
        $teachingRoles = ['Teacher', 'teacher', 'teaching_staff'];

        $teachingCount = Employee::where('school_id', $schoolId)
            ->whereIn('role', $teachingRoles)
            ->whereNull('deleted_at')
            ->count();

        $nonTeachingCount = Employee::where('school_id', $schoolId)
            ->whereNotIn('role', $teachingRoles)
            ->whereNull('deleted_at')
            ->count();

        $adminCount = User::where('school_id', $schoolId)
            ->where('account_status', 'active')
            ->where(function ($q) {
                $q->where('requested_role', 'administrator')
                    ->orWhere('requested_role', 'admin')
                    ->orWhereHas('customRole', fn ($role) => $role->where('name', UserRegistrationService::roleNameForCategory('administrator')));
            })
            ->count();
        if ($adminCount < 1 && auth()->check() && auth()->user()->school_id == $schoolId) {
            $adminCount = 1;
        }

        $girls = Student::where('school_id', $schoolId)
            ->where('status', 'active')
            ->where('gender', 'female')
            ->count();
        $boys = max(0, $studentCount - $girls);

        return [
            Stat::make(__('Teaching Staff'), $teachingCount)
                ->description(__('Registered teaching personnel'))
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            Stat::make(__('Non-Teaching Staff'), $nonTeachingCount)
                ->description(__('Support & administrative staff'))
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary'),

            Stat::make(__('Administrators'), $adminCount)
                ->description(__('School administrators'))
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('primary'),

            Stat::make(__('Students'), $studentCount)
                ->description(__('Enrolled students — :g girls · :b boys', ['g' => $girls, 'b' => $boys]))
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),
        ];
    }
}
