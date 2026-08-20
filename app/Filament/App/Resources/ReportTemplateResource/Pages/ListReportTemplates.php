<?php

namespace App\Filament\App\Resources\ReportTemplateResource\Pages;

use App\Filament\App\Resources\ReportTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReportTemplates extends ListRecords
{
    protected static string $resource = ReportTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
