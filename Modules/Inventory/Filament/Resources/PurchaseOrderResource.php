<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Inventory\Filament\Resources\PurchaseOrderResource\Pages;
use Modules\Inventory\Models\ProcurementOrder;

class PurchaseOrderResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory & Procurement');
    }

    protected static ?string $model = ProcurementOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Purchase Orders (LPO)';

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
                Forms\Components\Section::make('LPO Details')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->required()
                            ->disabled()
                            ->default(fn () => 'LPO-'.now()->year.'-'.str_pad((string) rand(100, 9999), 4, '0', STR_PAD_LEFT)),
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('order_date')
                            ->default(now())
                            ->required(),
                        Forms\Components\DatePicker::make('expected_delivery_date'),
                    ])->columns(4),

                Forms\Components\Section::make('Ordered Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('inventory_item_id')
                                    ->relationship('inventoryItem', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(5),
                                Forms\Components\TextInput::make('quantity_ordered')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('unit_cost')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->columnSpan(3),
                            ])->columns(10),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->searchable(),
                Tables\Columns\TextColumn::make('supplier.name')->searchable(),
                Tables\Columns\TextColumn::make('order_date')->date(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'draft',
                        'primary' => 'sent',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('total_amount')->money('USD'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
