<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\FixedAsset;
use Modules\Inventory\Models\InventoryIssuance;
use Modules\Inventory\Models\InventoryItem;

class InventoryReportService
{
    /**
     * Compiles data for the Ministry Asset Register (standard MoPSE/Government format) [1.2].
     */
    public function generateMinistryAssetRegister(int $schoolId): array
    {
        return FixedAsset::where('school_id', $schoolId)
            ->with(['inventoryItem', 'location', 'custodian'])
            ->get()
            ->map(fn ($asset) => [
                'Asset Code' => $asset->asset_number,
                'Description' => $asset->inventoryItem?->name ?? 'N/A',
                'Serial Number' => $asset->serial_number ?? 'N/A',
                'Acquisition Date' => $asset->acquisition_date->format('Y-m-d'),
                'Purchase Cost (USD)' => number_format((float) $asset->purchase_cost, 2),
                'Current Net Value (USD)' => number_format((float) $asset->current_value, 2),
                'Stewardship Location' => $asset->location?->name ?? 'N/A',
                'Custodian' => $asset->custodian?->name ?? 'N/A',
                'Funding Source' => ucfirst($asset->funding_source),
                'Status' => ucfirst($asset->status),
            ])
            ->toArray();
    }

    /**
     * Summarizes inventory valuations grouped by category.
     */
    public function generateInventoryValuationReport(int $schoolId): array
    {
        return InventoryItem::where('school_id', $schoolId)
            ->whereIn('item_type', ['consumable', 'returnable'])
            ->with('category')
            ->select(
                'category_id',
                DB::raw('COUNT(*) as total_items'),
                DB::raw('SUM(current_quantity) as total_quantity'),
                DB::raw('SUM(current_quantity * average_unit_cost) as total_valuation')
            )
            ->groupBy('category_id')
            ->get()
            ->map(fn ($row) => [
                'Category' => $row->category?->name ?? 'Uncategorized',
                'Distinct Items Count' => $row->total_items,
                'Aggregated Units' => $row->total_quantity,
                'Estimated Net Valuation (USD)' => number_format((float) $row->total_valuation, 2),
            ])
            ->toArray();
    }

    /**
     * Compiles unreturned assets overdue for return.
     */
    public function generateOverdueLoansReport(int $schoolId): array
    {
        return InventoryIssuance::where('school_id', $schoolId)
            ->where('is_returnable', true)
            ->where('status', 'issued')
            ->where('expected_return_date', '<', now()->toDateString())
            ->with(['inventoryItem', 'location', 'issuedBy'])
            ->get()
            ->map(function ($issuance) {
                // Polymorphically resolve borrower name details
                $borrower = $issuance->issuedTo;
                $borrowerName = $borrower ? $borrower->name : 'Unknown';

                return [
                    'Item' => $issuance->inventoryItem?->name ?? 'N/A',
                    'Qty' => $issuance->quantity,
                    'Location Out' => $issuance->location?->name ?? 'N/A',
                    'Borrower' => $borrowerName,
                    'Class Context' => class_basename($issuance->issued_to_type),
                    'Expected Return Date' => $issuance->expected_return_date->format('Y-m-d'),
                    'Days Overdue' => now()->diffInDays($issuance->expected_return_date),
                    'Authorized By' => $issuance->issuedBy?->name ?? 'N/A',
                ];
            })
            ->toArray();
    }
}
