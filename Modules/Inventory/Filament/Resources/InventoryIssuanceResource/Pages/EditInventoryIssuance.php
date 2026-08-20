<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\InventoryIssuanceResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Inventory\Filament\Resources\InventoryIssuanceResource;

class EditInventoryIssuance extends EditRecord
{
    protected static string $resource = InventoryIssuanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
