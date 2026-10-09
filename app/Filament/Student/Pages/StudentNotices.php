<?php

namespace App\Filament\Student\Pages;

use Filament\Pages\Page;
use Modules\Communication\Models\Announcement;

class StudentNotices extends Page
{
    protected static string $view = 'filament.student.pages.student-notices';

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Notices';

    protected static ?string $title = 'School Notices';

    protected static ?string $slug = 'notices';

    public static function getNavigationLabel(): string
    {
        return __('Notices');
    }

    protected function getViewData(): array
    {
        $userId = auth()->id();
        $notices = Announcement::query()
            ->active()
            ->get()
            ->filter(function (Announcement $notice) use ($userId) {
                $visibility = $notice->visibility ?? [];
                $targetUserIds = $notice->target_user_ids ?? [];

                return (empty($visibility) && empty($targetUserIds))
                    || in_array('student', $visibility, true)
                    || in_array($userId, $targetUserIds, true);
            })
            ->sortByDesc('published_at')
            ->values();

        return [
            'notices' => $notices,
        ];
    }
}
