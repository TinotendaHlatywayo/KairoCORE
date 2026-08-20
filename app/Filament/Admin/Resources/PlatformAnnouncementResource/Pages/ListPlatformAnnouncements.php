<?php

namespace App\Filament\Admin\Resources\PlatformAnnouncementResource\Pages;

use App\Filament\Admin\Resources\PlatformAnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlatformAnnouncements extends ListRecords
{
    protected static string $resource = PlatformAnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->slideOver(),
        ];
    }
}
