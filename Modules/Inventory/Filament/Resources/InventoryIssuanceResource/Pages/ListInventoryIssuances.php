<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\InventoryIssuanceResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Inventory\Filament\Resources\InventoryIssuanceResource;

class ListInventoryIssuances extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = InventoryIssuanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make(),
        ];
    }
}
