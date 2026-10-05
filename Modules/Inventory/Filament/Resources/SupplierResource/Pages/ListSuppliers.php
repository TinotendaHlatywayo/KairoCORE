<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\SupplierResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Concerns\HasPageHelp;
use App\Services\Csv\InventorySupplierCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Inventory\Filament\Resources\SupplierResource;

class ListSuppliers extends ListRecords
{
    use HasCsvBulkActions;
    use HasPageHelp;

    protected static string $resource = SupplierResource::class;

    protected static function csvService(): string
    {
        return InventorySupplierCsvService::class;
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
