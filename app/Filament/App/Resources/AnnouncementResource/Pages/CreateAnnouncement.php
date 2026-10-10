<?php

namespace App\Filament\App\Resources\AnnouncementResource\Pages;

use App\Filament\App\Resources\AnnouncementResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnouncement extends CreateRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function afterCreate(): void
    {
        if ($this->record->status === 'published') {
            try {
                AnnouncementResource::broadcastToAudience($this->record);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}