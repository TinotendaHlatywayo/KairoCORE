<?php

namespace App\Filament\App\Resources\ReportTemplateResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\ReportTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReportTemplates extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = ReportTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(), Actions\CreateAction::make()];
    }
}
