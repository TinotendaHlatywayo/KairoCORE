<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Inventory\Filament\Resources\GoodsReceivedResource\Pages;
use Modules\Inventory\Models\GoodsReceivedNote;
use Modules\Inventory\Models\ProcurementOrder;

class GoodsReceivedResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory & Procurement');
    }

    protected static ?string $model = GoodsReceivedNote::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Goods Received (GRN)';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?string $navigationGroup = 'Inventory & Procurement';

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('GRN Metadata')
                    ->schema([
                        Forms\Components\TextInput::make('grn_number')
                            ->required()
                            ->disabled()
                            ->default(fn () => 'GRN-'.now()->year.'-'.str_pad((string) rand(100, 9999), 4, '0', STR_PAD_LEFT)),
                        Forms\Components\Select::make('procurement_order_id')
                            ->relationship('procurementOrder', 'order_number')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Dynamically preload LPO items into the repeater on change
                                $po = ProcurementOrder::find($state);
                                if ($po) {
                                    $items = $po->items->map(fn ($item) => [
                                        'inventory_item_id' => $item->inventory_item_id,
                                        'quantity_accepted' => $item->quantity_ordered - $item->quantity_received,
                                    ])->toArray();
                                    $set('items', $items);
                                }
                            }),
                        Forms\Components\DatePicker::make('received_date')
                            ->default(now())
                            ->required(),
                        Forms\Components\Select::make('received_by_id')
                            ->relationship('receivedBy', 'name')
                            ->default(fn () => auth()->id())
                            ->required(),
                    ])->columns(4),

                Forms\Components\Section::make('Accepted Cargo Delivery')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('inventory_item_id')
                                    ->relationship('inventoryItem', 'name')
                                    ->disabled()
                                    ->dehydrated()
                                    ->required()
                                    ->columnSpan(4),
                                Forms\Components\TextInput::make('quantity_accepted')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('quantity_rejected')
                                    ->numeric()
                                    ->default(0)
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('batch_number')
                                    ->placeholder(__('Lot / Batch code'))
                                    ->columnSpan(2),
                                Forms\Components\DatePicker::make('expiry_date')
                                    ->placeholder(__('Expiry (if consumable)'))
                                    ->columnSpan(2),
                            ])->columns(12),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('grn_number')->searchable(),
                Tables\Columns\TextColumn::make('procurementOrder.order_number')->label(__('LPO Reference'))->searchable(),
                Tables\Columns\TextColumn::make('received_date')->date(),
                Tables\Columns\TextColumn::make('receivedBy.name')->label(__('Received By')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoodsReceivedNotes::route('/'),
            'create' => Pages\CreateGoodsReceivedNote::route('/create'),
            'edit' => Pages\EditGoodsReceivedNote::route('/{record}/edit'),
        ];
    }
}
