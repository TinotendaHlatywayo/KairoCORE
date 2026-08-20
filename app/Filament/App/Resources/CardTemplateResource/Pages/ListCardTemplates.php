<?php

namespace App\Filament\App\Resources\CardTemplateResource\Pages;

use App\Filament\App\Resources\CardTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCardTemplates extends ListRecords
{
    protected static string $resource = CardTemplateResource::class;

    protected static ?string $title = 'ID Card Templates';

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
