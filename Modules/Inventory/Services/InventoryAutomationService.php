<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use App\Models\School;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventoryProcurement;
use Modules\Inventory\Models\InventoryTransaction;

class InventoryAutomationService
{
    public function adjustStock(
        int $itemId,
        int $userId,
        int $deltaQuantity,
        string $transactionType,
        ?string $notes = null
    ): InventoryTransaction {
        return DB::transaction(function () use ($itemId, $userId, $deltaQuantity, $transactionType, $notes) {
            $item = InventoryItem::where('id', $itemId)
                ->lockForUpdate()
                ->firstOrFail();

            $newStock = $item->quantity_on_hand + $deltaQuantity;

            if ($newStock < 0) {
                throw new Exception('Adjusted quantity structure violates absolute safety stock bounds.');
            }

            $item->quantity_on_hand = $newStock;

            if ($item->quantity_on_hand <= $item->reorder_level) {
                $item->status = 'low_stock';
                $this->triggerProcurementRequest($item);
            } else {
                $item->status = 'active';
            }

            $item->save();

            return InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'user_id' => $userId,
                'transaction_type' => $transactionType,
                'quantity' => $deltaQuantity,
                'balance_after' => $newStock,
                'notes' => $notes,
            ]);
        });
    }

    protected function triggerProcurementRequest(InventoryItem $item): void
    {
        $existingDraft = InventoryProcurement::where('status', 'draft')
            ->whereJsonContains('items_payload', [['item_id' => $item->id]])
            ->exists();

        if ($existingDraft) {
            return;
        }

        $orderVolume = $item->maximum_stock - $item->quantity_on_hand;
        if ($orderVolume <= 0) {
            $orderVolume = $item->reorder_level > 0 ? ($item->reorder_level * 2) : 5;
        }

        $costSummary = $orderVolume * (float) $item->purchase_cost;

        $itemsPayload = [
            [
                'item_id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'quantity' => $orderVolume,
                'unit_cost' => $item->purchase_cost,
                'total_cost' => $costSummary,
            ],
        ];

        $tenant = app('current_tenant');
        $tenantId = $tenant instanceof School ? $tenant->id : null;

        InventoryProcurement::create([
            'school_id' => $tenantId,
            'requested_by_id' => Auth::id() ?? 1,
            'supplier_id' => $item->inventory_supplier_id,
            'type' => 'request',
            'status' => 'draft',
            'total_amount' => $costSummary,
            'items_payload' => $itemsPayload,
            'reference_number' => 'AUTO-REQ-'.strtoupper(bin2hex(random_bytes(4))),
        ]);
    }
}
