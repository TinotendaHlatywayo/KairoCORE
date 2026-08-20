<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\GoodsReceivedResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Inventory\Filament\Resources\GoodsReceivedResource;
use Modules\Inventory\Services\ProcurementPipelineService;

class CreateGoodsReceivedNote extends CreateRecord
{
    protected static string $resource = GoodsReceivedResource::class;

    protected function afterCreate(): void
    {
        // Automatically reconcile stock balances and update average costing on completion [1.2]
        app(ProcurementPipelineService::class)->receiveGoods($this->record);
    }
}
