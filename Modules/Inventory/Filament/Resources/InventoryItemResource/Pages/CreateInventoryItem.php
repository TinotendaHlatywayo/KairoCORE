<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\InventoryItemResource\Pages;

use Filament\Resources\Pages\CreateRecord; // Corrected namespace import
use Modules\Inventory\Filament\Resources\InventoryItemResource;

class CreateInventoryItem extends CreateRecord
{
    protected static string $resource = InventoryItemResource::class;
}
