<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\StockAdjustmentResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventoryStockAdjustment;
use Modules\Inventory\Models\InventoryStockMovement;

class StockAdjustmentResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory & Procurement');
    }

    protected static ?string $model = InventoryStockAdjustment::class;

    protected static ?string $navigationGroup = 'Inventory & Procurement';

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $recordTitleAttribute = 'adjustment_number';

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Stocktake Details')
                    ->schema([
                        Forms\Components\TextInput::make('adjustment_number')
                            ->required()
                            ->disabled()
                            ->default(fn () => 'ADJ-'.now()->year.'-'.str_pad((string) rand(100, 9999), 4, '0', STR_PAD_LEFT)),
                        Forms\Components\Select::make('inventory_location_id')
                            ->relationship('location', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\DatePicker::make('conducted_date')
                            ->default(now())
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Adjustment Ledger Sheets')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('inventory_item_id')
                                    ->relationship('inventoryItem', 'name')
                                    ->required()
                                    ->reactive()
                                    ->searchable()
                                    ->preload()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $item = InventoryItem::find($state);
                                        if ($item) {
                                            $set('system_quantity', $item->current_quantity);
                                        }
                                    })
                                    ->columnSpan(4),
                                Forms\Components\TextInput::make('system_quantity')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('physical_quantity')
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $sys = (int) $get('system_quantity');
                                        $phys = (int) $state;
                                        $set('variance', $phys - $sys);
                                    })
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('variance')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('reason')
                                    ->placeholder(__('e.g., theft, broken'))
                                    ->columnSpan(2),
                            ])
                            ->columns(12)
                            ->defaultItems(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('adjustment_number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('location.name')->label(__('Storage zone'))->searchable(),
                Tables\Columns\TextColumn::make('conducted_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'completed',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('Commit Audit')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (InventoryStockAdjustment $record) => $record->status === 'draft')
                    ->action(function (InventoryStockAdjustment $record) {
                        DB::transaction(function () use ($record) {
                            foreach ($record->items as $adjustmentItem) {
                                /** @var InventoryItem $item */
                                $item = $adjustmentItem->inventoryItem;
                                $variance = $adjustmentItem->variance;

                                if ($variance !== 0) {
                                    // Record compensating entry
                                    InventoryStockMovement::create([
                                        'school_id' => $record->school_id,
                                        'inventory_item_id' => $item->id,
                                        'inventory_location_id' => $record->inventory_location_id,
                                        'type' => 'adjustment',
                                        'quantity' => $variance,
                                        'unit_cost' => $item->average_unit_cost,
                                        'remarks' => "Stocktake adjustment audit: {$record->adjustment_number}",
                                        'performed_by_id' => Auth::id(),
                                    ]);

                                    // Adjust denormalized quantity on hand
                                    $item->increment('current_quantity', $variance);
                                }
                            }

                            $record->update(['status' => 'completed']);
                        });

                        Notification::make()
                            ->title(__('Stock Audit Committed'))
                            ->body('All variances resolved and inventory counts synchronized.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockAdjustments::route('/'),
            'create' => Pages\CreateStockAdjustment::route('/create'),
            'edit' => Pages\EditStockAdjustment::route('/{record}/edit'),
        ];
    }
}
