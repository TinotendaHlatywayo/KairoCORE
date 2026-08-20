<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\InventoryItemResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Services\Csv\InventoryItemCsvService;
use Filament\Actions; // Corrected namespace import
use Filament\Resources\Pages\ListRecords;
use Modules\Inventory\Filament\Resources\InventoryItemResource;

class ListInventoryItems extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = InventoryItemResource::class;

    protected static function csvService(): string
    {
        return InventoryItemCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
