<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\GoodsReceivedResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Inventory\Filament\Resources\GoodsReceivedResource;
use Modules\Inventory\Models\GoodsReceivedNote;

class EditGoodsReceivedNote extends EditRecord
{
    protected static string $resource = GoodsReceivedResource::class;

    /**
     * Show the same ordered-vs-received comparison as the create page. The
     * quantities this note already recorded are backed out of the order's
     * received totals, so "Outstanding" stays accurate.
     */
    public function mount($record): void
    {
        // Set before the parent builds the form, because the schema reads this
        // flag when it is constructed. A posted note has already moved stock
        // and created asset records, so its received quantities are shown for
        // reference rather than offered for editing.
        GoodsReceivedResource::$postedNote = true;

        parent::mount($record);

        /** @var GoodsReceivedNote $grn */
        $grn = $this->getRecord();
        $order = $grn->procurementOrder;

        if (! $order) {
            return;
        }

        $lines = [];

        foreach (GoodsReceivedNote::deliveryRows($order->load('items'), $grn) as $row) {
            $existing = $grn->items->firstWhere('inventory_item_id', $row['inventory_item_id']);

            if (! $existing) {
                continue;
            }

            $row['quantity_accepted'] = (int) $existing->quantity_accepted;
            $row['quantity_rejected'] = (int) $existing->quantity_rejected;
            $row['batch_number'] = $existing->batch_number;
            $row['expiry_date'] = $existing->expiry_date?->toDateString();
            $lines[] = $row;
        }

        // Merge rather than fill a partial payload: fill() replaces the form
        // state, which would drop the other fields and fail validation.
        $this->form->fill([...$this->form->getRawState(), 'items' => $lines]);
    }

    /**
     * Rejected quantities and consignment details may be corrected, but the
     * accepted quantities are already posted and left untouched.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $lines = array_values((array) ($data['items'] ?? []));
        unset($data['items']);

        foreach ($lines as $line) {
            $itemId = $line['inventory_item_id'] ?? null;

            if (blank($itemId)) {
                continue;
            }

            $this->record->items()
                ->where('inventory_item_id', $itemId)
                ->update([
                    'quantity_rejected' => max(0, (int) ($line['quantity_rejected'] ?? 0)),
                    'batch_number' => $line['batch_number'] ?: null,
                    'expiry_date' => $line['expiry_date'] ?: null,
                ]);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
