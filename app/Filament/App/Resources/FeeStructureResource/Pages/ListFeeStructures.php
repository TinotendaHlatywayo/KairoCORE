<?php

namespace App\Filament\App\Resources\FeeStructureResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Resources\FeeStructureResource;
use App\Services\Csv\FeeStructureCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFeeStructures extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = FeeStructureResource::class;

    protected static function csvService(): string
    {
        return FeeStructureCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
