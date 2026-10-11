<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Modules\Communication\Models\Announcement;
use Modules\Communication\Models\EventCalendar;

/**
 * Notice board for every non-student workspace role.
 *
 * Staff/administrators see every active announcement whose visibility matches
 * one of their role keys (real role storage: `requested_role` plus the linked
 * custom role), every announcement explicitly targeted at them, and all
 * school-wide notices. Uses the same display styles as the student portal.
 */
class Notices extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Communication Center';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Notices';

    protected static ?string $title = 'School Notices & Announcements';

    protected static string $view = 'filament.app.pages.notices';

    public static function getNavigationLabel(): string
    {
        return __('Notices');
    }

    protected function getViewData(): array
    {
        $user = auth()->user();

        $roleKeys = collect([$user?->requested_role])
            ->merge($user?->customRole?->role_key ? [$user->customRole->role_key] : [])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $announcements = Announcement::query()
            ->active()
            ->get()
            ->filter(function (Announcement $notice) use ($user, $roleKeys) {
                $visibility = $notice->visibility ?? [];
                $targets = $notice->target_user_ids ?? [];

                if (empty($visibility) && empty($targets)) {
                    return true;
                }

                if (! empty($targets) && in_array($user?->id, $targets, true)) {
                    return true;
                }

                if (in_array('all', $visibility, true)) {
                    return true;
                }

                return ! empty(array_intersect($visibility, $roleKeys));
            });

        $now = now();
        $events = EventCalendar::query()
            ->where('start_time', '>=', $now)
            ->orderBy('start_time')
            ->get()
            ->filter(function (EventCalendar $event) use ($user, $roleKeys) {
                $visibility = $event->target_roles ?? [];
                $targets = $event->target_user_ids ?? [];

                if (empty($visibility) && empty($targets)) {
                    return true;
                }

                if (! empty($targets) && in_array($user?->id, $targets, true)) {
                    return true;
                }

                if (in_array('all', $visibility, true)) {
                    return true;
                }

                return ! empty(array_intersect($visibility, $roleKeys));
            });

        $notices = $announcements->merge($events)
            ->sortByDesc(fn ($item) => $item->published_at ?? $item->start_time ?? $item->created_at)
            ->values();

        return [
            'notices' => $notices,
        ];
    }
}
