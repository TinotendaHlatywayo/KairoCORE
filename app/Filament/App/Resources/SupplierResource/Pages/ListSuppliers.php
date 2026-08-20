<?php

namespace App\Filament\App\Resources\SupplierResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Resources\SupplierResource;
use App\Services\Csv\SupplierCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSuppliers extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = SupplierResource::class;

    protected static function csvService(): string
    {
        return SupplierCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
