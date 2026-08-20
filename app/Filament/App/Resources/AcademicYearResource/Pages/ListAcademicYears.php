<?php

namespace App\Filament\App\Resources\AcademicYearResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Resources\AcademicYearResource;
use App\Services\Csv\AcademicYearCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAcademicYears extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = AcademicYearResource::class;

    protected static function csvService(): string
    {
        return AcademicYearCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
