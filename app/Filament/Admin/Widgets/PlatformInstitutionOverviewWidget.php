<?php

namespace App\Filament\Admin\Widgets;

use App\Models\School;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformInstitutionOverviewWidget extends BaseWidget
{
    protected function getHeading(): ?string
    {
        return __('Institutions by status');
    }

    protected function getStats(): array
    {
        $total = School::query()->count();
        $active = School::query()->where('status', 'active')->count();
        $pending = School::query()->where('status', 'pending')->count();
        $suspended = School::query()->where('status', 'suspended')->count();
        $trials = School::query()->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', now())->count();
        $endingSoon = School::query()
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays(7)])
            ->count();

        $activePercent = $total > 0 ? (int) round($active / $total * 100) : 0;

        return [
            Stat::make(__('Total Institutions'), $total)
                ->description(__('Registered school workspaces'))
                ->descriptionIcon('heroicon-m-building-library')
                ->color('primary'),

            Stat::make(__('Active'), $active)
                ->description(__(':p% of all institutions', ['p' => $activePercent]))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make(__('Pending Approval'), $pending)
                ->description(__('Awaiting activation'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make(__('Suspended'), $suspended)
                ->description(__('Temporarily suspended'))
                ->descriptionIcon('heroicon-m-no-symbol')
                ->color('danger'),

            Stat::make(__('On Trial'), $trials)
                ->description(__(':n ending within 7 days', ['n' => $endingSoon]))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
        ];
    }
}
