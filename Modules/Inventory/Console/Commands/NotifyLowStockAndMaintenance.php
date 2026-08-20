<?php

declare(strict_types=1);

namespace Modules\Inventory\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Models\AssetMaintenanceLog;
use Modules\Inventory\Models\InventoryItem;

class NotifyLowStockAndMaintenance extends Command
{
    protected $signature = 'inventory:notify-alerts';

    protected $description = 'Scan database for critical low stock items and scheduled asset maintenance due today';

    public function handle(): void
    {
        $this->info('Initiating schoolcore inventory diagnostics sweep...');

        // 1. Scan and flag items that fell below reorder level
        $lowStockItems = InventoryItem::whereIn('item_type', ['consumable', 'returnable'])
            ->whereColumn('current_quantity', '<=', 'reorder_level')
            ->with('category')
            ->get();

        if ($lowStockItems->isNotEmpty()) {
            $this->warn("Identified {$lowStockItems->count()} item(s) below reorder levels:");
            foreach ($lowStockItems as $item) {
                $this->line(" - SKU: {$item->sku} | Name: {$item->name} | Qty: {$item->current_quantity} (Min: {$item->reorder_level})");
            }
        }

        // 2. Scan and flag maintenance scheduled for today [1.2]
        $today = now()->toDateString();
        $dueMaintenances = AssetMaintenanceLog::where('status', 'pending')
            ->where('scheduled_date', $today)
            ->with('fixedAsset.inventoryItem')
            ->get();

        if ($dueMaintenances->isNotEmpty()) {
            $this->warn("Identified {$dueMaintenances->count()} asset maintenance schedule(s) due today ({$today}):");
            foreach ($dueMaintenances as $log) {
                $asset = $log->fixedAsset;
                $this->line(" - Asset: {$asset->asset_number} ({$asset->inventoryItem?->name}) | Title: {$log->title}");
            }
        }

        // 3. Log results to system channels for diagnostic tracing
        Log::info('Completed automated schoolcore inventory alerts audit.', [
            'timestamp' => now()->toIso8601String(),
            'low_stock_count' => $lowStockItems->count(),
            'maintenance_due_count' => $dueMaintenances->count(),
        ]);

        $this->info('Diagnostics sweep completed successfully.');
    }
}
