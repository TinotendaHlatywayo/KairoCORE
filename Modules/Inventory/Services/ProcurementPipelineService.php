<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\ExpenseCategory;
use Modules\Finance\Models\ExpenseType;
use Modules\Inventory\Models\FixedAsset;
use Modules\Inventory\Models\GoodsReceivedNote;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventoryStockMovement;
use Modules\Inventory\Models\ProcurementOrder;
use RuntimeException;

class ProcurementPipelineService
{
    /**
     * Process Goods Received Note cargo entries into physical inventory.
     */
    public function receiveGoods(GoodsReceivedNote $grn): void
    {
        DB::transaction(function () use ($grn) {
            $po = $grn->procurementOrder;
            if (! $po) {
                throw new RuntimeException('Goods Received Note is missing its associated Purchase Order reference.');
            }

            $poItems = $po->items()->get()->keyBy('inventory_item_id');
            $defaultLocation = $this->getDefaultWarehouse($grn->school_id);

            foreach ($grn->items as $receivedItem) {
                $item = $receivedItem->inventoryItem;

                // Fetch purchase order item guidelines
                $poItem = $poItems->get($item->id);
                $unitCost = $poItem ? (float) $poItem->unit_cost : 0.0000;

                if ($poItem) {
                    $poItem->increment('quantity_received', $receivedItem->quantity_accepted);
                }

                // Create a batch if the item includes tracking details (expiry/lot number)
                $batch = null;
                if ($receivedItem->batch_number || $receivedItem->expiry_date) {
                    $batch = InventoryBatch::create([
                        'school_id' => $grn->school_id,
                        'inventory_item_id' => $item->id,
                        'batch_number' => $receivedItem->batch_number ?? ('LOT-'.now()->format('Ymd').'-'.rand(100, 999)),
                        'expiry_date' => $receivedItem->expiry_date,
                        'initial_quantity' => $receivedItem->quantity_accepted,
                        'current_quantity' => $receivedItem->quantity_accepted,
                        'unit_cost' => $unitCost,
                    ]);
                }

                // Record the acquisition movement
                InventoryStockMovement::create([
                    'school_id' => $grn->school_id,
                    'inventory_item_id' => $item->id,
                    'inventory_location_id' => $po->destination_location_id ?? $defaultLocation,
                    'inventory_batch_id' => $batch?->id,
                    'type' => 'purchase',
                    'quantity' => $receivedItem->quantity_accepted,
                    'unit_cost' => $unitCost,
                    'reference_type' => GoodsReceivedNote::class,
                    'reference_id' => $grn->id,
                    'remarks' => "Acquired via GRN: {$grn->grn_number}",
                    'performed_by_id' => $grn->received_by_id,
                ]);

                // Recalculate the Moving Average Cost (MAC)
                $this->updateMovingAverageCost($item, $receivedItem->quantity_accepted, $unitCost);

                // If this is a capitalized fixed asset, automatically register each accepted unit into the Fixed Assets register
                if ($item->item_type === 'fixed_asset' && $receivedItem->quantity_accepted > 0) {
                    for ($q = 0; $q < $receivedItem->quantity_accepted; $q++) {
                        $assetNumber = 'FA-'.now()->year.'-'.str_pad((string) rand(10, 99999), 5, '0', STR_PAD_LEFT);
                        while (FixedAsset::withoutTenantScope()->where('school_id', $grn->school_id)->where('asset_number', $assetNumber)->exists()) {
                            $assetNumber = 'FA-'.now()->year.'-'.str_pad((string) rand(10, 99999), 5, '0', STR_PAD_LEFT);
                        }

                        FixedAsset::create([
                            'school_id' => $grn->school_id,
                            'inventory_item_id' => $item->id,
                            'asset_number' => $assetNumber,
                            'acquisition_date' => $grn->received_date ?? now(),
                            'purchase_cost' => $unitCost,
                            'salvage_value' => round($unitCost * 0.1, 2),
                            'useful_life_years' => 5,
                            'depreciation_method' => 'straight_line',
                            'current_value' => $unitCost,
                            'assigned_location_id' => $po->destination_location_id ?? $defaultLocation,
                            'custodian_id' => $grn->received_by_id,
                            'status' => 'active',
                        ]);
                    }
                }
            }

            // Update the state machine of the primary Purchase Order
            $this->evaluateOrderStatus($po);

            // Automatically register expense in school's finance ledger for procurement / inventory acquisition
            try {
                $schoolId = $grn->school_id;
                $totalGrnCost = 0.00;
                foreach ($grn->items as $receivedItem) {
                    $item = $receivedItem->inventoryItem;
                    $poItem = $poItems->get($item->id);
                    $unitCost = $poItem ? (float) $poItem->unit_cost : 0.00;
                    $totalGrnCost += ($receivedItem->quantity_accepted * $unitCost);
                }

                if ($totalGrnCost > 0) {
                    $category = ExpenseCategory::firstOrCreate(
                        ['school_id' => $schoolId, 'name' => 'Procurement & Inventory'],
                        ['description' => __('Automated expense tracking for received purchase orders and inventory acquisitions')]
                    );

                    $expenseType = ExpenseType::firstOrCreate(
                        ['school_id' => $schoolId, 'expense_category_id' => $category->id, 'name' => 'Inventory & Asset Procurement']
                    );

                    Expense::create([
                        'school_id' => $schoolId,
                        'expense_category_id' => $category->id,
                        'expense_type_id' => $expenseType->id,
                        'expense_name' => 'Procurement (GRN '.$grn->grn_number.')',
                        'amount' => $totalGrnCost,
                        'expense_date' => now()->toDateString(),
                        'reference_number' => 'EXP-GRN-'.$grn->grn_number,
                        'notes' => 'Automated expense log for Goods Received Note (GRN): '.$grn->grn_number.' (PO: '.$po->order_number.')',
                        'status' => 'paid',
                    ]);
                }
            } catch (\Exception $e) {
                // Fail silently if finance tables aren't present
            }
        });
    }

    /**
     * Compute and write the updated Moving Average Cost (MAC) [1.2].
     */
    protected function updateMovingAverageCost(InventoryItem $item, int $qtyAdded, float $unitCost): void
    {
        $currentQty = (int) $item->current_quantity;
        $currentCost = (float) $item->average_unit_cost;

        $totalNewQty = $currentQty + $qtyAdded;
        if ($totalNewQty > 0) {
            $newAvg = (($currentQty * $currentCost) + ($qtyAdded * $unitCost)) / $totalNewQty;
            $item->update([
                'average_unit_cost' => round($newAvg, 4),
                'current_quantity' => $totalNewQty,
            ]);
        }
    }

    /**
     * Update order state by matching the received counts against the target values.
     */
    protected function evaluateOrderStatus(ProcurementOrder $po): void
    {
        $items = $po->items;
        $fullyReceived = true;
        $anyReceived = false;

        foreach ($items as $item) {
            if ($item->quantity_received > 0) {
                $anyReceived = true;
            }
            if ($item->quantity_received < $item->quantity_ordered) {
                $fullyReceived = false;
            }
        }

        $status = 'sent';
        if ($fullyReceived) {
            $status = 'completed';
        } elseif ($anyReceived) {
            $status = 'partially_received';
        }

        $po->update(['status' => $status]);
    }

    /**
     * Locate the general warehouse of the school.
     */
    protected function getDefaultWarehouse(int $schoolId): int
    {
        $id = DB::table('inventory_locations')
            ->where('school_id', $schoolId)
            ->where('type', 'general')
            ->orderBy('id', 'ASC')
            ->value('id');

        if (! $id) {
            throw new RuntimeException('No active general warehouse location configured for this school.');
        }

        return (int) $id;
    }
}
