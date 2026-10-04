<?php

namespace App\Filament\App\Resources\AcademicReportResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\AcademicReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademicReports extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = AcademicReportResource::class;

    protected function getHeaderActions(): array
    {
        return array_filter([
            Actions\CreateAction::make(),
            $this->getHelpAction(),
        ]);
    }
}
