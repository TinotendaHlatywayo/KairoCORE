<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\GoodsReceivedResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Inventory\Filament\Resources\GoodsReceivedResource;
use Modules\Inventory\Models\GoodsReceivedNote;
use Modules\Inventory\Models\ProcurementOrder;
use Modules\Inventory\Services\ProcurementPipelineService;

class CreateGoodsReceivedNote extends CreateRecord
{
    protected static string $resource = GoodsReceivedResource::class;

    /**
     * Delivery lines, held aside from the note payload so they can be written
     * once the note itself exists.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $deliveryLines = [];

    /**
     * Reached from a purchase order with ?procurement_order_id=, which is how
     * the "Receive Goods" action works. The whole order is loaded as an
     * ordered-vs-received comparison so the user only has to adjust the lines
     * that actually arrived.
     */
    public function mount(): void
    {
        GoodsReceivedResource::$postedNote = false;

        parent::mount();

        $orderId = request()->query('procurement_order_id');

        if (blank($orderId)) {
            return;
        }

        $order = ProcurementOrder::with('items')->find($orderId);

        if (! $order) {
            return;
        }

        // Every key is filled explicitly. A partial fill() replaces the form
        // state, which is what left the form failing validation on the
        // system-generated fields.
        $this->form->fill([
            'grn_number' => GoodsReceivedNote::nextNumber((int) $order->school_id),
            'procurement_order_id' => $order->id,
            'received_date' => now()->toDateString(),
            'received_by_id' => auth()->id(),
            'items' => GoodsReceivedNote::deliveryRows($order),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->deliveryLines = array_values((array) ($data['items'] ?? []));
        unset($data['items']);

        return $data;
    }

    protected function afterCreate(): void
    {
        foreach ($this->deliveryLines as $line) {
            $itemId = $line['inventory_item_id'] ?? null;

            // A line without an item cannot be received; the form blocks this
            // but the service should not be handed a broken line.
            if (blank($itemId)) {
                continue;
            }

            $this->record->items()->create([
                'inventory_item_id' => $itemId,
                'quantity_accepted' => max(0, (int) ($line['quantity_accepted'] ?? 0)),
                'quantity_rejected' => max(0, (int) ($line['quantity_rejected'] ?? 0)),
                'batch_number' => $line['batch_number'] ?: null,
                'expiry_date' => $line['expiry_date'] ?: null,
            ]);
        }

        $this->record->unsetRelation('items');

        app(ProcurementPipelineService::class)->receiveGoods($this->record);
    }
}
