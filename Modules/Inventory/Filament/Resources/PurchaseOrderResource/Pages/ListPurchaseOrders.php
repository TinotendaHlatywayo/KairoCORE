<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Concerns\HasPageHelp;
use App\Services\Csv\PurchaseOrderCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Inventory\Filament\Resources\PurchaseOrderResource;

class ListPurchaseOrders extends ListRecords
{
    use HasCsvBulkActions;
    use HasPageHelp;

    protected static string $resource = PurchaseOrderResource::class;

    protected static function csvService(): string
    {
        return PurchaseOrderCsvService::class;
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
