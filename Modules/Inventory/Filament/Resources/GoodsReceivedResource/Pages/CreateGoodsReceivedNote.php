<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\GoodsReceivedResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Inventory\Filament\Resources\GoodsReceivedResource;
use Modules\Inventory\Models\ProcurementOrder;
use Modules\Inventory\Services\ProcurementPipelineService;

class CreateGoodsReceivedNote extends CreateRecord
{
    protected static string $resource = GoodsReceivedResource::class;

    public function mount(): void
    {
        parent::mount();

        $poId = request()->query('procurement_order_id');
        if ($poId) {
            $po = ProcurementOrder::find($poId);
            if ($po) {
                $this->form->fill([
                    'procurement_order_id' => $po->id,
                    'items' => $po->items->map(fn ($item) => [
                        'inventory_item_id' => $item->inventory_item_id,
                        'quantity_accepted' => max(0, $item->quantity_ordered - $item->quantity_received),
                        'quantity_rejected' => 0,
                    ])->toArray(),
                ]);
            }
        }
    }

    protected function afterCreate(): void
    {
        app(ProcurementPipelineService::class)->receiveGoods($this->record);
    }
}
