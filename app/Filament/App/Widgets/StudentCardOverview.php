<?php

namespace App\Filament\App\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Students\Models\Student;

class StudentCardOverview extends BaseWidget
{
    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        $school = current_tenant();

        if (! $school) {
            return [];
        }

        $schoolId = $school->id;

        // 1. Total Active printed cards
        $activeCards = Student::where('school_id', $schoolId)
            ->where('card_status', 'active')
            ->count();

        // 2. Expired Printed Cards
        $expiredCards = Student::where('school_id', $schoolId)
            ->where('card_status', 'active')
            ->where('card_expiry_date', '<', now()->toDateString())
            ->count();

        // 3. Lost / Stolen Cards queue
        $lostStolenCards = Student::where('school_id', $schoolId)
            ->whereIn('card_status', ['lost', 'stolen'])
            ->count();

        // 4. Pending Issuance reprints queue
        $pendingIssuance = Student::where('school_id', $schoolId)
            ->where('card_status', 'pending_issuance')
            ->count();

        return [
            Stat::make('Active Student ID Cards', $activeCards)
                ->description(__('Total validated physical cards currently in circulation'))
                ->descriptionIcon('heroicon-m-identification')
                ->color('success'),

            Stat::make('Expired ID Cards', $expiredCards)
                ->description(__('Cards requiring immediate milestone re-calculation'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('danger'),

            Stat::make('Reported Lost / Stolen', $lostStolenCards)
                ->description(__('Cards flagged as disabled inside the secure QR portal'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('Pending Reprint Queue', $pendingIssuance)
                ->description(__('Students queued for bulk PVC card print runs'))
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),
        ];
    }
}
