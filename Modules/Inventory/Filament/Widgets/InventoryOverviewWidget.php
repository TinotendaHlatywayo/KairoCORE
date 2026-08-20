<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\FixedAsset;
use Modules\Inventory\Models\InventoryIssuance;
use Modules\Inventory\Models\InventoryItem;

class InventoryOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $user = Auth::user();
        $schoolId = $user?->school_id;

        if (! $schoolId) {
            return [];
        }

        // 1. Calculate Capitalized Fixed Asset Value
        $fixedAssetValuation = FixedAsset::where('school_id', $schoolId)
            ->where('status', 'active')
            ->sum('current_value');

        // 2. Calculate Consumables Stock Value (Quantity * Average Cost)
        $consumablesValuation = InventoryItem::where('school_id', $schoolId)
            ->whereIn('item_type', ['consumable', 'returnable'])
            ->select(DB::raw('SUM(current_quantity * average_unit_cost) as total_value'))
            ->value('total_value') ?? 0.00;

        // 3. Count Low Stock Catalog entries
        $lowStockCount = InventoryItem::where('school_id', $schoolId)
            ->whereIn('item_type', ['consumable', 'returnable'])
            ->whereColumn('current_quantity', '<=', 'reorder_level')
            ->count();

        // 4. Count Overdue Returnable items (textbooks, laptops, etc.)
        $overdueCount = InventoryIssuance::where('school_id', $schoolId)
            ->where('is_returnable', true)
            ->where('status', 'issued')
            ->where('expected_return_date', '<', now()->toDateString())
            ->count();

        return [
            Stat::make('Capitalized Assets Value', '$'.number_format((float) $fixedAssetValuation, 2))
                ->description(__('Total depreciated book value of physical property'))
                ->descriptionIcon('heroicon-m-cpu-chip')
                ->color('success'),

            Stat::make('Consumables Ledger Value', '$'.number_format((float) $consumablesValuation, 2))
                ->description(__('Moving Average Cost valuation of stock items'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),

            Stat::make('Low Stock Items', (string) $lowStockCount)
                ->description(__('Items at or below safety reorder threshold'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'danger' : 'success'),

            Stat::make('Overdue Loaned Assets', (string) $overdueCount)
                ->description(__('Returnable assets past scheduled return dates'))
                ->descriptionIcon('heroicon-m-clock')
                ->color($overdueCount > 0 ? 'warning' : 'success'),
        ];
    }
}
