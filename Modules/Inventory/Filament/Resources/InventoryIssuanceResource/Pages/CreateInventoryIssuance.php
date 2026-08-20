<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\InventoryIssuanceResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Filament\Resources\InventoryIssuanceResource;
use Modules\Inventory\Models\InventoryStockMovement;

class CreateInventoryIssuance extends CreateRecord
{
    protected static string $resource = InventoryIssuanceResource::class;

    protected function afterCreate(): void
    {
        DB::transaction(function () {
            $issuance = $this->record;
            $item = $issuance->inventoryItem;

            // Resolve location fallback if left blank in form [1.2]
            $locationId = $issuance->inventory_location_id ?? $this->getDefaultWarehouse($issuance->school_id);

            // Reduce from overall item quantity on hand [1.2]
            $item->decrement('current_quantity', $issuance->quantity);

            // Record transaction log entry inside stock movements ledger [1.2]
            InventoryStockMovement::create([
                'school_id' => $issuance->school_id,
                'inventory_item_id' => $issuance->inventory_item_id,
                'inventory_location_id' => $locationId,
                'inventory_batch_id' => $issuance->inventory_batch_id,
                'type' => 'issue',
                'quantity' => -$issuance->quantity,
                'unit_cost' => $item->average_unit_cost,
                'remarks' => 'Issued to: '.class_basename($issuance->issued_to_type)." ID: {$issuance->issued_to_id}",
                'performed_by_id' => Auth::id(), // Corrected to static facade
            ]);
        });

    }

    protected function getDefaultWarehouse(int $schoolId): int
    {
        return (int) DB::table('inventory_locations')
            ->where('school_id', $schoolId)
            ->where('type', 'general')
            ->orderBy('id', 'ASC')
            ->value('id');
    }
}
