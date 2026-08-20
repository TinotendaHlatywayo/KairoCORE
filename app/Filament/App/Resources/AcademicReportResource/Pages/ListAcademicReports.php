<?php

namespace App\Filament\App\Resources\AcademicReportResource\Pages;

use App\Filament\App\Resources\AcademicReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademicReports extends ListRecords
{
    protected static string $resource = AcademicReportResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
