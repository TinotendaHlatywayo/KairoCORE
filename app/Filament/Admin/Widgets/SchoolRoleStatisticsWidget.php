<?php

namespace App\Filament\Admin\Widgets;

use App\Models\School;
use App\Models\User;
use App\Services\UserRegistrationService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SchoolRoleStatisticsWidget extends BaseWidget
{
    public ?int $schoolId = null;

    public function mount(School $record): void
    {
        $this->schoolId = $record->id;
    }

    protected function getHeading(): ?string
    {
        return __('Users by role');
    }

    protected function getStats(): array
    {
        $schoolId = $this->schoolId;

        if (! $schoolId) {
            return [];
        }

        // Group in SQL so a school with thousands of accounts still renders in
        // one query; users without a requested role land in their own bucket.
        $rows = User::notPlatformManaged()
            ->where('school_id', $schoolId)
            ->selectRaw("COALESCE(NULLIF(requested_role, ''), 'none') as role_key, COUNT(*) as total")
            ->groupBy('role_key')
            ->pluck('total', 'role_key');

        $total = (int) $rows->sum();

        if ($total === 0) {
            return [
                Stat::make(__('No user accounts'), 0)
                    ->description(__('This institution has no users yet'))
                    ->descriptionIcon('heroicon-m-users')
                    ->color('gray'),
            ];
        }

        $catalogue = UserRegistrationService::categoryRoleNames();
        $preferred = ['administrator', 'admin', 'teaching_staff', 'non_teaching_staff', 'student'];

        $orderedKeys = collect($rows->keys())
            ->sortBy(function (string $key) use ($preferred) {
                $position = array_search($key, $preferred, true);

                return $position === false ? count($preferred) : $position;
            })
            ->values();

        $stats = [];

        foreach ($orderedKeys as $key) {
            $count = (int) $rows[$key];
            $percent = (int) round($count / max(1, $total) * 100);

            [$label, $icon, $color] = match ($key) {
                'administrator', 'admin' => [__('Administrators'), 'heroicon-m-shield-check', 'warning'],
                'teaching_staff' => [__('Teaching Staff'), 'heroicon-m-academic-cap', 'success'],
                'non_teaching_staff' => [__('Non-Teaching Staff'), 'heroicon-m-briefcase', 'info'],
                'student' => [__('Students'), 'heroicon-m-user-group', 'primary'],
                'none' => [__('No role assigned'), 'heroicon-m-question-mark-circle', 'gray'],
                default => [
                    __($catalogue[$key] ?? ucwords(str_replace('_', ' ', $key))),
                    'heroicon-m-user',
                    'primary',
                ],
            };

            $stats[] = Stat::make($label, $count)
                ->description(__(':p% of users', ['p' => $percent]))
                ->descriptionIcon($icon)
                ->color($color);
        }

        return $stats;
    }
}
