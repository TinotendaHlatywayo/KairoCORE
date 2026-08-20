<?php

namespace App\Filament\App\Resources\PlatformInboxResource\Pages;

use App\Filament\App\Resources\PlatformInboxResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlatformInboxes extends ListRecords
{
    protected static string $resource = PlatformInboxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->slideOver(),
        ];
    }
}
