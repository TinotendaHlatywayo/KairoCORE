<?php

namespace App\Filament\App\Resources\ApplicationResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\ApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApplications extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = ApplicationResource::class;

    protected static ?string $title = 'Online Applications';

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
        return array_filter([
            Actions\CreateAction::make(),
            $this->getHelpAction(),
        ]);
    }
}
