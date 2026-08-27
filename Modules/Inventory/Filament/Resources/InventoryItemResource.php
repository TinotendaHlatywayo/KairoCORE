<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Inventory\Filament\Resources\InventoryItemResource\Pages\CreateInventoryItem;
use Modules\Inventory\Filament\Resources\InventoryItemResource\Pages\EditInventoryItem;
use Modules\Inventory\Filament\Resources\InventoryItemResource\Pages\ListInventoryItems;
use Modules\Inventory\Models\InventoryItem;

class InventoryItemResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory & Procurement');
    }

    use ModuleAwareActiveNavigation;

    protected static ?string $model = InventoryItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Asset & Item Registry';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?string $navigationGroup = 'Inventory & Procurement';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Section::make(__('General Item Specifications'))
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('sku')
                                    ->label(__('SKU Reference'))
                                    ->required()
                                    ->default(fn () => 'SKU-'.now()->year.'-'.str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT))
                                    ->unique(ignoreRecord: true),
                                Forms\Components\TextInput::make('barcode')
                                    ->label(__('Barcode Tracking Code'))
                                    ->placeholder(__('Scan or input barcode value')),
                                Forms\Components\Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')->required(),
                                        Forms\Components\Select::make('parent_id')
                                            ->relationship('parent', 'name')
                                            ->searchable()
                                            ->preload(),
                                    ]),
                            ])->columnSpan(2),

                        Forms\Components\Section::make(__('Class & Tracking Configuration'))
                            ->schema([
                                Forms\Components\Select::make('item_type')
                                    ->required()
                                    ->options([
                                        'consumable' => __('Consumable (decrease when used)'),
                                        'returnable' => __('Returnable (expected back)'),
                                        'fixed_asset' => __('Fixed Asset (capitalized value)'),
                                    ])
                                    ->reactive(),
                                Forms\Components\TextInput::make('unit_of_measure')
                                    ->default('pieces')
                                    ->placeholder(__('e.g., pieces, reams, kg'))
                                    ->required(),
                                Forms\Components\TextInput::make('reorder_level')
                                    ->numeric()
                                    ->default(10)
                                    ->label(__('Low Stock Warning Threshold')),
                            ])->columnSpan(1),
                    ]),

                Forms\Components\Section::make(__('Sales & Dynamic Custom Metadata'))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_saleable')
                                    ->label(__('Is Saleable (Auto-billing integration)'))
                                    ->reactive(),
                                Forms\Components\TextInput::make('sale_price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->visible(fn (Forms\Get $get) => (bool) $get('is_saleable'))
                                    ->required(fn (Forms\Get $get) => (bool) $get('is_saleable')),
                            ]),
                        Forms\Components\KeyValue::make('meta_data')
                            ->label(__('Technical Properties'))
                            ->keyPlaceholder(__('e.g., Size, Color, Hazard Rating')) // Corrected Method
                            ->valuePlaceholder(__('e.g., Large, Blue, Haz-3')), // Corrected Method
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sku')->label(__('SKU'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label(__('Category'))->searchable(),
                Tables\Columns\TextColumn::make('item_type')
                    ->badge()
                    ->colors([
                        'primary' => 'consumable',
                        'success' => 'returnable',
                        'warning' => 'fixed_asset',
                    ]),
                Tables\Columns\TextColumn::make('current_quantity')
                    ->label(__('Qty on Hand'))
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('unit_of_measure')->label(__('UOM')),
                Tables\Columns\TextColumn::make('average_unit_cost')
                    ->label(__('Average Cost'))
                    ->money('USD')
                    ->alignEnd(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('item_type')
                    ->options([
                        'consumable' => __('Consumable'),
                        'returnable' => __('Returnable'),
                        'fixed_asset' => __('Fixed Asset'),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryItems::route('/'),
            'create' => CreateInventoryItem::route('/create'),
            'edit' => EditInventoryItem::route('/{record}/edit'),
        ];
    }
}
