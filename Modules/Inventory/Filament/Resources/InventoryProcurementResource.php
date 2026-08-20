<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Inventory\Filament\Resources\InventoryProcurementResource\Pages\CreateInventoryProcurement;
use Modules\Inventory\Filament\Resources\InventoryProcurementResource\Pages\ListInventoryProcurements;
use Modules\Inventory\Models\InventoryProcurement;

class InventoryProcurementResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory & Procurement');
    }

    protected static ?string $model = InventoryProcurement::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Procurement Orders';

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
                Forms\Components\Section::make('Procurement Master Details')
                    ->schema([
                        Forms\Components\TextInput::make('reference_number')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'request' => __('Internal Purchase Request'),
                                'order' => __('Supplier Purchase Order'),
                                'receipt' => __('Goods Received Note'),
                                'invoice' => __('Supplier Invoice'),
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => __('Draft Workflow State'),
                                'pending_approval' => __('Awaiting Board Review'),
                                'approved' => __('Approved Procurement Asset'),
                                'ordered' => __('Dispatched to Supplier'),
                                'received' => __('Goods Completely Received'),
                                'cancelled' => __('Cancelled Order Record'),
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->required()
                            ->prefix('$'),
                        Forms\Components\KeyValue::make('items_payload')
                            ->label(__('Line Item Summary'))
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label(__('Supplier'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('USD')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryProcurements::route('/'),
            'create' => CreateInventoryProcurement::route('/create'),
        ];
    }
}
