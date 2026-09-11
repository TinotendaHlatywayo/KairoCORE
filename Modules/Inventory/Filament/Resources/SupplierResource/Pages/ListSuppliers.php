<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\SupplierResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Services\Csv\InventorySupplierCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Inventory\Filament\Resources\SupplierResource;

class ListSuppliers extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = SupplierResource::class;

    protected static function csvService(): string
    {
        return InventorySupplierCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
