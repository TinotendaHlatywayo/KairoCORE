<?php

namespace App\Filament\Admin\Widgets;

use App\Models\School;
use App\Models\User;
use App\Services\UserRegistrationService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\HR\Models\Employee;
use Modules\Students\Models\Student;

class SchoolPeopleStatisticsWidget extends BaseWidget
{
    public ?int $schoolId = null;

    public function mount(School $record): void
    {
        $this->schoolId = $record->id;
    }

    protected function getHeading(): ?string
    {
        return __('People overview');
    }

    protected function getStats(): array
    {
        $schoolId = $this->schoolId;

        if (! $schoolId) {
            return [];
        }

        // User accounts belong to this school only, never platform-managed
        // super-admin accounts that ride along without a school.
        $users = User::notPlatformManaged()->where('school_id', $schoolId);
        $totalUsers = (clone $users)->count();
        $activeUsers = (clone $users)->where('account_status', User::STATUS_ACTIVE)->count();
        $pendingUsers = (clone $users)->where('account_status', User::STATUS_PENDING)->count();

        // Headline counts come from the REAL directory records, so the totals
        // match what the school sees inside its own screens.
        $studentCount = Student::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->count();

        $teachingRoles = array_values(array_unique([
            UserRegistrationService::roleNameForCategory('teaching_staff'),
            'Teaching Staff',
            'Teacher',
            'teacher',
            'teaching_staff',
        ]));

        $employees = Employee::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->whereNull('deleted_at');
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
        ];
    }
}
