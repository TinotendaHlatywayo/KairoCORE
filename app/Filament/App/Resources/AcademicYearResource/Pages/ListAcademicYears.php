<?php

namespace App\Filament\App\Resources\AcademicYearResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\AcademicYearResource;
use App\Services\Csv\AcademicYearCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademicYears extends ListRecords
{
    use HasCsvBulkActions;
    use HasPageHelp;

    protected static string $resource = AcademicYearResource::class;

    protected static function csvService(): string
    {
        return AcademicYearCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return array_filter([
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
            $this->getHelpAction(),
        ]);
    }
}
