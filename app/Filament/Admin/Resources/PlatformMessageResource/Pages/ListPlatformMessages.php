<?php

namespace App\Filament\Admin\Resources\PlatformMessageResource\Pages;

use App\Filament\Admin\Resources\PlatformMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlatformMessages extends ListRecords
{
    protected static string $resource = PlatformMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->slideOver(),
        ];
    }
}
