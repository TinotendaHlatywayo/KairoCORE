<?php

namespace App\Filament\App\Resources\GeneratedReportResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\GeneratedReportResource;
use Filament\Resources\Pages\ListRecords;

class ListGeneratedReports extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = GeneratedReportResource::class;

    protected static ?string $title = 'Report Archive';

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    public function getHeading(): string
    {
        return __('Report Archive');
    }

    public function getSubheading(): ?string
    {
        return __('Review, verify and download compiled reports.');
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
        ];
    }
}
