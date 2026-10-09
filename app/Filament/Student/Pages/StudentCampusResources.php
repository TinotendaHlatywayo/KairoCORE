<?php

namespace App\Filament\Student\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Modules\Communication\Models\CampusResource;

class StudentCampusResources extends Page
{
    protected static string $view = 'filament.student.pages.student-campus-resources';

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Campus Resources';

    protected static ?string $title = 'Campus Resources';

    protected static ?string $slug = 'campus-resources';

    public static function getNavigationLabel(): string
    {
        return __('Campus Resources');
    }

    public function download(int $resourceId)
    {
        $resource = CampusResource::query()->find($resourceId);
        if (!$resource) {
            return;
        }

        $userId = auth()->id();
        $eligibility = $resource->visibility ?? [];
        $targetUserIds = $resource->target_user_ids ?? [];

        $canDownload = empty($eligibility) && empty($targetUserIds)
            || in_array('student', $eligibility, true)
            || in_array($userId, $targetUserIds, true);

        if (!$canDownload) {
            return;
        }

        $resource->increment('download_count');

        return response()->download(storage_path('app/public/' . $resource->file_path));
    }

    protected function getViewData(): array
    {
        $userId = auth()->id();
        $resources = CampusResource::query()
            ->latest()
            ->get()
            ->filter(function (CampusResource $res) use ($userId) {
                $eligibility = $res->visibility ?? [];
                $targetUserIds = $res->target_user_ids ?? [];

                return empty($eligibility) && empty($targetUserIds)
                    || in_array('student', $eligibility, true)
                    || in_array($userId, $targetUserIds, true);
            })
            ->values();

        return [
            'resources' => $resources,
        ];
    }
}
