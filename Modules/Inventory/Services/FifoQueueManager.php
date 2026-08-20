<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Inventory\Models\InventoryBatch;
use Modules\Inventory\Models\InventoryIssuance;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventoryStockMovement;
use RuntimeException;

class FifoQueueManager
{
    /**
     * Issue stock using an Expiry-First, then First-In-First-Out (FIFO) queue hierarchy.
     */
    public function issueStock(
        InventoryItem $item,
        int $locationId,
        int $requiredQty,
        string $issuedToType,
        int $issuedToId,
        int $issuedById,
        bool $isReturnable = false,
        ?string $expectedReturnDate = null,
        ?string $remarks = null
    ): void {
        DB::transaction(function () use (
            $item,
            $locationId,
            $requiredQty,
            $issuedToType,
            $issuedToId,
            $issuedById,
            $isReturnable,
            $expectedReturnDate,
            $remarks
        ) {
            if ($requiredQty <= 0) {
                throw new InvalidArgumentException('Quantity to issue must be a positive integer.');
            }

            if ($item->current_quantity < $requiredQty) {
                throw new RuntimeException("Insufficient stock available in system ledger for SKU: {$item->sku}");
            }

            // Retrieve active batches sorted by expiration date (expiry-first), then arrival date (FIFO)
            $batches = InventoryBatch::where('school_id', $item->school_id)
                ->where('inventory_item_id', $item->id)
                ->where('current_quantity', '>', 0)
                ->orderByRaw('expiry_date ASC, created_at ASC')
                ->get();

            $remainingNeeded = $requiredQty;

            // 1. If batches exist, allocate stock across them sequentially
            if ($batches->isNotEmpty()) {
                foreach ($batches as $batch) {
                    if ($remainingNeeded <= 0) {
                        break;
                    }

                    $takeFromThisBatch = min($batch->current_quantity, $remainingNeeded);

                    $batch->decrement('current_quantity', $takeFromThisBatch);

                    // Record the inventory movement
                    $movement = InventoryStockMovement::create([
                        'school_id' => $item->school_id,
                        'inventory_item_id' => $item->id,
                        'inventory_location_id' => $locationId,
                        'inventory_batch_id' => $batch->id,
                        'type' => 'issue',
                        'quantity' => -$takeFromThisBatch,
                        'unit_cost' => $batch->unit_cost,
                        'remarks' => $remarks ?? 'FIFO auto-disbursement',
                        'performed_by_id' => $issuedById,
                    ]);

                    // Log the distribution assignment
                    InventoryIssuance::create([
                        'school_id' => $item->school_id,
                        'inventory_item_id' => $item->id,
                        'inventory_location_id' => $locationId,
                        'inventory_batch_id' => $batch->id,
                        'issued_to_type' => $issuedToType,
                        'issued_to_id' => $issuedToId,
                        'quantity' => $takeFromThisBatch,
                        'is_returnable' => $isReturnable,
                        'expected_return_date' => $expectedReturnDate,
                        'status' => 'issued',
                        'remarks' => $remarks,
                        'issued_by_id' => $issuedById,
                    ]);

                    $remainingNeeded -= $takeFromThisBatch;
                }
            } else {
                // 2. Fallback if no specific batch profile exists (unbatched inventory)
                $movement = InventoryStockMovement::create([
                    'school_id' => $item->school_id,
                    'inventory_item_id' => $item->id,
                    'inventory_location_id' => $locationId,
                    'inventory_batch_id' => null,
                    'type' => 'issue',
                    'quantity' => -$remainingNeeded,
                    'unit_cost' => $item->average_unit_cost,
                    'remarks' => $remarks ?? 'Unbatched disbursement',
                    'performed_by_id' => $issuedById,
                ]);

                InventoryIssuance::create([
                    'school_id' => $item->school_id,
                    'inventory_item_id' => $item->id,
                    'inventory_location_id' => $locationId,
                    'inventory_batch_id' => null,
                    'issued_to_type' => $issuedToType,
                    'issued_to_id' => $issuedToId,
                    'quantity' => $remainingNeeded,
                    'is_returnable' => $isReturnable,
                    'expected_return_date' => $expectedReturnDate,
                    'status' => 'issued',
                    'remarks' => $remarks,
                    'issued_by_id' => $issuedById,
                ]);

                $remainingNeeded = 0;
            }

            if ($remainingNeeded > 0) {
                throw new RuntimeException('Queue processing failed. Physical records and database indexes are out of sync.');
            }

            // Update the master item total count
            $item->decrement('current_quantity', $requiredQty);
        });
    }
}
