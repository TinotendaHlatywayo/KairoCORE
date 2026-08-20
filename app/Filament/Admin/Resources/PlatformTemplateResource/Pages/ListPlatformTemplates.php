<?php

namespace App\Filament\Admin\Resources\PlatformTemplateResource\Pages;

use App\Filament\Admin\Resources\PlatformTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlatformTemplates extends ListRecords
{
    protected static string $resource = PlatformTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->slideOver(),
        ];
    }
}
