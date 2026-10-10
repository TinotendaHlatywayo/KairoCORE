<?php

namespace App\Filament\App\Resources\AnnouncementResource\Pages;

use App\Filament\App\Resources\AnnouncementResource;
use Filament\Resources\Pages\EditRecord;

class EditAnnouncement extends EditRecord
{
    protected static string $resource = AnnouncementResource::class;

    protected function afterSave(): void
    {
        if ($this->record->status === 'published'
            && ($this->record->wasChanged('status')
                || $this->record->wasChanged('visibility')
                || $this->record->wasChanged('target_user_ids'))) {
            try {
                AnnouncementResource::broadcastToAudience($this->record);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}