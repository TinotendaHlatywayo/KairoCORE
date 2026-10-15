<?php

namespace App\Filament\Admin\Widgets;

use App\Models\User;
use App\Services\UserRegistrationService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\HR\Models\Employee;
use Modules\Students\Models\Student;

class PlatformPeopleOverviewWidget extends BaseWidget
{
    protected function getHeading(): ?string
    {
        return __('People across all institutions');
    }

    protected function getStats(): array
    {
        // Combined totals across every school workspace, excluding the
        // platform-managed operators and accounts that belong to no school.
        $users = User::notPlatformManaged()->whereNotNull('school_id');
        $totalUsers = (clone $users)->count();
        $activeUsers = (clone $users)->where('account_status', User::STATUS_ACTIVE)->count();
        $pendingUsers = (clone $users)->where('account_status', User::STATUS_PENDING)->count();

        $adminCount = (clone $users)->where(function ($query) {
            $query->where('requested_role', 'administrator')
                ->orWhere('requested_role', 'admin')
                ->orWhereHas('customRole', fn ($role) => $role->where('name', UserRegistrationService::roleNameForCategory('administrator')));
        })->count();

        $studentCount = Student::withoutTenantScope()->count();

        $teachingRoles = array_values(array_unique([
            UserRegistrationService::roleNameForCategory('teaching_staff'),
            'Teaching Staff',
            'Teacher',
            'teacher',
            'teaching_staff',
        ]));

        $employees = Employee::withoutTenantScope()->whereNull('deleted_at');
        $totalStaff = (clone $employees)->count();
        $teachingStaff = (clone $employees)->whereIn('role', $teachingRoles)->count();
        $supportStaff = max(0, $totalStaff - $teachingStaff);

        return [
            Stat::make(__('Total Users'), $totalUsers)
                ->description(__(':a active · :p pending approval', ['a' => $activeUsers, 'p' => $pendingUsers]))
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make(__('Total Students'), $studentCount)
                ->description(__('Student directory records'))
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make(__('Total Staff'), $totalStaff)
                ->description(__(':t teaching · :s support staff', ['t' => $teachingStaff, 's' => $supportStaff]))
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),

            Stat::make(__('Administrators'), $adminCount)
                ->description(__('School administrator accounts'))
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('warning'),
        ];
    }
}
