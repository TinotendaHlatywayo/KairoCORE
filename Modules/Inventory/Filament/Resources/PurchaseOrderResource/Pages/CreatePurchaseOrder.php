<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\PurchaseOrderResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Inventory\Filament\Resources\PurchaseOrderResource;
use Modules\Inventory\Services\ProcurementPipelineService;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function afterCreate(): void
    {
        app(ProcurementPipelineService::class)->recomputeOrderTotal($this->record);
    }
}
