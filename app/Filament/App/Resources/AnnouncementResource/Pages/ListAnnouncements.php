<?php

namespace App\Filament\App\Resources\AnnouncementResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\AnnouncementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnnouncements extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = AnnouncementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make()->label(__('Create Notice')),
        ];
    }
}