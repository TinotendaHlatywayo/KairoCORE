<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Modules\Inventory\Models\InventoryItem;

class LowStockAlertsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = Auth::user();
        $schoolId = $user?->school_id;

        return $table
            ->query(
                InventoryItem::query()
                    ->where('school_id', $schoolId)
                    ->whereIn('item_type', ['consumable', 'returnable'])
                    ->whereColumn('current_quantity', '<=', 'reorder_level')
            )
            ->heading(__('Critical Low Stock Alerts'))
            ->columns([
                Tables\Columns\TextColumn::make('sku')->label(__('SKU Reference')),
                Tables\Columns\TextColumn::make('name')->label(__('Item Name')),
                Tables\Columns\TextColumn::make('category.name')->label(__('Category')),
                Tables\Columns\TextColumn::make('reorder_level')
                    ->label(__('Min Safety Threshold'))
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('current_quantity')
                    ->label(__('Current stock'))
                    ->color('danger')
                    ->weight('bold')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('unit_of_measure')->label(__('UOM')),
            ]);
    }
}
