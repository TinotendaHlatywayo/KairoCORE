<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\InventoryItemResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Concerns\HasPageHelp;
use App\Services\Csv\InventoryItemCsvService; // Corrected namespace import
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Inventory\Filament\Resources\InventoryItemResource;

class ListInventoryItems extends ListRecords
{
    use HasCsvBulkActions;
    use HasPageHelp;

    protected static string $resource = InventoryItemResource::class;

    protected static function csvService(): string
    {
        return InventoryItemCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
