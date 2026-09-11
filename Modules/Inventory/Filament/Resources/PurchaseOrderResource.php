<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Inventory\Filament\Resources\PurchaseOrderResource\Pages;
use Modules\Inventory\Models\InventorySupplier;
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
                Forms\Components\Section::make(__('LPO Details'))
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->default(fn () => 'LPO-'.now()->year.'-'.str_pad((string) rand(100, 9999), 4, '0', STR_PAD_LEFT)),
                        Forms\Components\Select::make('supplier_id')
                            ->options(fn (?string $search = '') => \Modules\Inventory\Models\InventorySupplier::query()
                                ->when($search, fn ($q) => fuzzy_search_where($q, ['name', 'contact_person', 'email'], $search))
                                ->orderBy('name')
                                ->limit(50)
                                ->pluck('name', 'id'))
                            ->getOptionLabelUsing(fn ($value) => InventorySupplier::find($value)?->name)
                            ->searchable()
                            ->searchPrompt(__('Type to search suppliers (typos are OK)...'))
                            ->required(),
                        Forms\Components\DatePicker::make('order_date')
                            ->default(now())
                            ->required(),
                        Forms\Components\DatePicker::make('expected_delivery_date'),
                    ])->columns(4),

                Forms\Components\Section::make(__('Ordered Items'))
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('inventory_item_id')
                                    ->options(fn (?string $search = '') => inventory_item_search_options($search))
                                    ->getOptionLabelUsing(fn ($value) => inventory_item_label(\Modules\Inventory\Models\InventoryItem::find($value)))
                                    ->searchable()
                                    ->optionsLimit(50)
                                    ->required()
                                    ->columnSpan(4),
                                Forms\Components\Toggle::make('is_fixed_asset')
                                    ->label(__('Fixed Asset?'))
                                    ->default(false)
                                    ->helperText(__('Check if capitalized asset'))
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('quantity_ordered')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('unit_cost')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->columnSpan(2),
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
            ])
            ->actions([
                Tables\Actions\Action::make('receive_goods')
                    ->label(__('Receive Goods'))
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->url(fn (ProcurementOrder $record): string => GoodsReceivedResource::getUrl('create', ['procurement_order_id' => $record->id]))
                    ->visible(fn (ProcurementOrder $record): bool => in_array($record->status, ['sent', 'partially_received'])),
                Tables\Actions\Action::make('view')
                    ->label(__('Compare GRN'))
                    ->icon('heroicon-o-scale')
                    ->color('primary')
                    ->url(fn (ProcurementOrder $record): string => static::getUrl('view', ['record' => $record])),
                Tables\Actions\Action::make('pdf')
                    ->label(__('Print'))
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->action(fn (ProcurementOrder $record) => self::streamOrderPdf($record)),
            ])
            ->bulkActions([]);
    }

    public static function streamOrderPdf(ProcurementOrder $order)
    {
        $pdf = Pdf::loadView('modules.inventory.purchase-order-pdf', [
            'school' => current_tenant(),
            'order' => $order->load(['supplier', 'items.inventoryItem', 'request.requester']),
            'primaryColor' => '#5b4fe9',
        ])->setPaper('a4');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $order->order_number.'-Purchase-Order.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['supplier', 'items.inventoryItem', 'grns.items', 'request.requester']);
    }
}
