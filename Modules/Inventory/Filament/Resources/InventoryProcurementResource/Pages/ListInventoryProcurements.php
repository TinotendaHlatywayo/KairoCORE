<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\InventoryProcurementResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Inventory\Filament\Resources\InventoryProcurementResource;

class ListInventoryProcurements extends ListRecords
{
    protected static string $resource = InventoryProcurementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
