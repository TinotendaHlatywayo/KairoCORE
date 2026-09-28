<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\ExpenseCategory;
use Modules\Finance\Models\ExpenseType;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Inventory\Models\FixedAsset;
use Modules\Inventory\Models\GoodsReceivedNote;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventoryLocation;
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
                $accepted = (int) $receivedItem->quantity_accepted;

                // A line left at zero was not delivered, so it must not create
                // a stock movement, a batch or an asset record.
                if ($accepted <= 0) {
                    continue;
                }

                $item = $receivedItem->inventoryItem;

                // Fetch purchase order item guidelines
                $poItem = $poItems->get($item->id);
                $unitCost = $poItem ? (float) $poItem->unit_cost : 0.0000;

                if ($poItem) {
                    $outstanding = max(0, (int) $poItem->quantity_ordered - (int) $poItem->quantity_received);

                    if ($accepted > $outstanding) {
                        throw new RuntimeException(
                            sprintf(
                                'Cannot receive %d of "%s": only %d outstanding on %s.',
                                $accepted,
                                $item->name,
                                $outstanding,
                                $po->order_number
                            )
                        );
                    }

                    $poItem->increment('quantity_received', $accepted);
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
                            'asset_name' => $item->name,
                            'description' => $item->description ?: null,
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
        $items = $po->items()->get();
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
     * Locate the general warehouse of the school, auto-provisioning a default
     * one so Goods Received creation never 500s on a school without locations.
     */
    protected function getDefaultWarehouse(int $schoolId): int
    {
        $id = DB::table('inventory_locations')
            ->where('school_id', $schoolId)
            ->where('type', 'general')
            ->orderBy('id', 'ASC')
            ->value('id');

        if ($id) {
            return (int) $id;
        }

        $location = InventoryLocation::create([
            'school_id' => $schoolId,
            'name' => 'General Warehouse',
            'code' => 'GEN-'.$schoolId,
            'type' => 'general',
        ]);

        return (int) $location->id;
    }

    /**
     * Recompute the order's stored total_amount from its line items. Called
     * after unit costs change on create/edit so the Purchase Orders list stays
     * in sync with what the PDF renders from the item lines.
     */
    public function recomputeOrderTotal(ProcurementOrder $order): void
    {
        // Sum with a fresh query rather than $order->items: after a save the
        // record may still carry the items relation loaded from before the
        // edit, which would write the old total straight back over it.
        $total = (float) $order->items()
            ->withoutGlobalScopes()
            ->selectRaw('COALESCE(SUM(quantity_ordered * unit_cost), 0) AS line_total')
            ->value('line_total');

        $order->forceFill(['total_amount' => round($total, 2)])->save();
    }

    /**
     * Approve a purchase order: move the order to 'approved', record the paid
     * expense against the chosen bank account and deduct the order total from
     * that account's running balance.
     */
    public function approveOrder(ProcurementOrder $order, int $bankAccountId): void
    {
        if (in_array($order->status, ['approved', 'completed', 'cancelled'], true)) {
            return;
        }

        $bankAccount = SchoolBankAccount::find($bankAccountId);

        if (! $bankAccount) {
            throw new RuntimeException('The selected bank account could not be found.');
        }

        $total = (float) $order->total_amount;

        DB::transaction(function () use ($order, $bankAccount, $total) {
            $order->fill([
                'status' => 'approved',
                'bank_account_id' => $bankAccount->id,
                'approved_by_id' => auth()->id(),
                'approved_at' => now(),
            ])->save();

            $category = ExpenseCategory::firstOrCreate(
                ['school_id' => $order->school_id, 'name' => 'Procurement & Inventory'],
                ['description' => __('Automated expense tracking for approved purchase orders')]
            );

            $expenseType = ExpenseType::firstOrCreate(
                ['school_id' => $order->school_id, 'expense_category_id' => $category->id, 'name' => 'Inventory & Asset Procurement']
            );

            Expense::create([
                'school_id' => $order->school_id,
                'expense_category_id' => $category->id,
                'expense_type_id' => $expenseType->id,
                'expense_name' => 'Procurement (PO '.$order->order_number.')',
                'amount' => $total,
                'expense_date' => $order->order_date?->toDateString() ?? now()->toDateString(),
                'reference_number' => 'EXP-PO-'.$order->order_number,
                'notes' => 'Automated expense log for approved Purchase Order: '.$order->order_number,
                'status' => 'paid',
                'bank_account_id' => $bankAccount->id,
            ]);

            $bankAccount->decrement('balance', $total);
        });
    }

    /**
     * Compute the outstanding (ordered but not yet received) value of a
     * purchase order, minus any amount already refunded to the supplier
     * shortfall. This is the maximum the supplier still owes back.
     */
    public function outstandingRefundValue(ProcurementOrder $order): float
    {
        $outstanding = 0.0;
        foreach ($order->items as $item) {
            $remaining = max(0, (int) $item->quantity_ordered - (int) $item->quantity_received);
            $outstanding += $remaining * (float) $item->unit_cost;
        }

        return round(max(0, $outstanding - (float) $order->refunded_amount), 2);
    }

    /**
     * Record a supplier refund for missing / short-delivered PO items. The
     * money comes back into the chosen bank account (balance increment) and an
     * offsetting expense entry is logged so the ledger reflects the reduction.
     */
    public function refundOrder(ProcurementOrder $order, int $bankAccountId): float
    {
        $bankAccount = SchoolBankAccount::find($bankAccountId);

        if (! $bankAccount) {
            throw new RuntimeException('The selected bank account could not be found.');
        }

        $refund = $this->outstandingRefundValue($order);

        if ($refund <= 0) {
            return 0.0;
        }

        DB::transaction(function () use ($order, $bankAccount, $refund) {
            $order->increment('refunded_amount', $refund);

            $category = ExpenseCategory::firstOrCreate(
                ['school_id' => $order->school_id, 'name' => 'Procurement & Inventory'],
                ['description' => __('Automated expense tracking for purchase orders and supplier refunds')]
            );

            $expenseType = ExpenseType::firstOrCreate(
                ['school_id' => $order->school_id, 'expense_category_id' => $category->id, 'name' => 'Inventory & Asset Procurement']
            );

            Expense::create([
                'school_id' => $order->school_id,
                'expense_category_id' => $category->id,
                'expense_type_id' => $expenseType->id,
                'expense_name' => 'Supplier Refund (PO '.$order->order_number.')',
                'amount' => -$refund,
                'expense_date' => now()->toDateString(),
                'reference_number' => 'REF-PO-'.$order->order_number,
                'notes' => 'Supplier refund for missing / short-delivered items on Purchase Order: '.$order->order_number,
                'status' => 'paid',
                'bank_account_id' => $bankAccount->id,
            ]);

            $bankAccount->increment('balance', $refund);
        });

        return $refund;
    }
}
