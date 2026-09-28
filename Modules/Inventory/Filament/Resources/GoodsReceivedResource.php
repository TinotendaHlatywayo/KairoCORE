<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Inventory\Filament\Resources\GoodsReceivedResource\Pages;
use Modules\Inventory\Models\GoodsReceivedNote;
use Modules\Inventory\Models\InventoryItem;
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

    /**
     * A posted note has already moved stock and created asset records, so its
     * received quantities are shown for reference rather than edited.
     */
    public static bool $postedNote = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('GRN Metadata'))
                    ->schema([
                        Forms\Components\TextInput::make('grn_number')
                            ->label(__('GRN Number'))
                            ->disabled()
                            // Issued by the system on save, so it is shown for
                            // reference only and is never validated or submitted.
                            ->dehydrated(false)
                            ->default(fn (): string => GoodsReceivedNote::nextNumber(self::currentSchoolId()))
                            ->helperText(__('Generated automatically when the note is saved.')),
                        Forms\Components\Select::make('procurement_order_id')
                            ->label(__('Purchase Order'))
                            ->required()
                            ->searchable()
                            ->live()
                            ->options(fn (): array => ProcurementOrder::query()
                                ->with('supplier')
                                ->latest('order_date')
                                ->latest('id')
                                ->get()
                                ->mapWithKeys(fn (ProcurementOrder $order): array => [
                                    $order->id => $order->order_number.' — '.$order->supplier?->name,
                                ])
                                ->all())
                            ->getOptionLabelUsing(fn ($value): ?string => optional(ProcurementOrder::find($value))->order_number)
                            ->getSearchResultsUsing(fn (string $search): array => ProcurementOrder::query()
                                ->with('supplier')
                                ->where('order_number', 'like', "%{$search}%")
                                ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('request', fn ($query) => $query->where('request_number', 'like', "%{$search}%"))
                                ->latest('order_date')
                                ->latest('id')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(fn (ProcurementOrder $order): array => [
                                    $order->id => $order->order_number.' — '.$order->supplier?->name,
                                ])
                                ->all())
                            ->afterStateUpdated(function ($state, callable $set): void {
                                $order = filled($state) ? ProcurementOrder::find($state) : null;

                                if ($order) {
                                    $set('items', GoodsReceivedNote::deliveryRows($order));
                                }
                            })
                            ->suffixActions([
                                Forms\Components\Actions\Action::make('compare_po')
                                    ->label(__('Compare with PO'))
                                    ->icon('heroicon-o-scale')
                                    ->color('primary')
                                    ->size(ActionSize::Small)
                                    ->tooltip(__('Open the GRN vs Purchase Order comparison for this LPO'))
                                    ->disabled(fn (Get $get): bool => blank($get('procurement_order_id')))
                                    ->url(
                                        fn (Get $get): ?string => filled($get('procurement_order_id'))
                                            ? PurchaseOrderResource::getUrl('view', ['record' => $get('procurement_order_id')])
                                            : null,
                                        shouldOpenInNewTab: true,
                                    ),
                            ]),
                        Forms\Components\DatePicker::make('received_date')
                            ->default(now())
                            ->required(),
                        Forms\Components\Select::make('received_by_id')
                            ->label(__('Received By'))
                            ->relationship('receivedBy', 'name')
                            ->default(fn () => auth()->id())
                            ->required(),
                    ])->columns(4),

                Forms\Components\Section::make(__('Purchase Order Comparison'))
                    ->description(__('Every line on the purchase order is listed. Adjust the quantity actually received, or delete a line that did not arrive.'))
                    ->schema([
                        Forms\Components\Placeholder::make('po_context')
                            ->label(__('Order'))
                            ->content(fn (Get $get): string => self::poContext($get('procurement_order_id'))),
                        Forms\Components\Placeholder::make('receiving_totals')
                            ->label(__('This Delivery'))
                            ->content(fn (Get $get): string => self::deliveryTotals($get('items'))),
                    ])->columns(2),

                Forms\Components\Section::make(__('Accepted Cargo Delivery'))
                    ->schema([
                        // Deliberately not ->relationship('items'): a relationship
                        // repeater re-hydrates from the (still empty) relation and
                        // discards the prefilled comparison rows. The rows are
                        // written explicitly by the create/edit pages instead.
                        Forms\Components\Repeater::make('items')
                            ->addActionLabel(__('Add a line'))
                            ->deletable(! static::$postedNote)
                            ->schema([
                                // The item always comes from the purchase order, so it is
                                // shown as a read-only label plus a hidden value rather
                                // than a nested relationship select. A select with its
                                // own ->relationship() inside a relationship repeater
                                // makes Filament discard the whole row on fill().
                                Forms\Components\Hidden::make('inventory_item_id'),
                                Forms\Components\Placeholder::make('item_label')
                                    ->label(__('Inventory Item'))
                                    ->content(fn (Get $get): string => InventoryItem::find($get('inventory_item_id'))?->name ?? __('Unknown item'))
                                    ->columnSpan(4),
                                Forms\Components\TextInput::make('quantity_ordered')
                                    ->label(__('Ordered'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('quantity_received_before')
                                    ->label(__('Already Received'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('quantity_outstanding')
                                    ->label(__('Outstanding'))
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('quantity_accepted')
                                    ->label(__('Quantity Received Now'))
                                    ->numeric()
                                    ->required()
                                    ->integer()
                                    ->minValue(0)
                                    ->maxValue(fn (Get $get): int => (int) $get('quantity_outstanding'))
                                    ->disabled(static::$postedNote)
                                    ->helperText(static::$postedNote
                                        ? __('Already posted to stock and the asset register.')
                                        : fn (Get $get): string => (int) $get('quantity_accepted') < (int) $get('quantity_outstanding')
                                            ? __('Less than the outstanding quantity; the rest stays on the order.')
                                            : '')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('quantity_rejected')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->default(0)
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('batch_number')
                                    ->placeholder(__('Lot / Batch code'))
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('expiry_date')
                                    ->placeholder(__('Expiry (if consumable)'))
                                    ->columnSpan(3),
                            ])->columns(12)
                            ->addable(false),
                    ]),
            ]);
    }

    /**
     * The school the note is being raised for.
     */
    protected static function currentSchoolId(): int
    {
        return (int) (current_tenant()?->id ?? auth()->user()?->school_id);
    }

    /**
     * One-line summary of the order being received against.
     */
    protected static function poContext(mixed $orderId): string
    {
        if (blank($orderId)) {
            return __('Select a purchase order to start receiving against it.');
        }

        $order = ProcurementOrder::with('supplier')->find($orderId);

        if (! $order) {
            return __('Purchase order not found.');
        }

        return implode(' · ', array_filter([
            $order->order_number,
            $order->supplier?->name,
            __('Status').': '.$order->status,
            __('Order total').': $'.number_format((float) $order->total_amount, 2),
        ]));
    }

    /**
     * Totals for what this delivery will actually record, so the user can see
     * the effect of any adjustments before saving.
     *
     * @param  mixed  $rows
     */
    protected static function deliveryTotals($rows): string
    {
        if (! is_array($rows) || $rows === []) {
            return __('No lines entered yet.');
        }

        $accepted = 0;
        $rejected = 0;
        $outstanding = 0;
        $short = 0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $accepted += (int) ($row['quantity_accepted'] ?? 0);
            $rejected += (int) ($row['quantity_rejected'] ?? 0);
            $outstanding += (int) ($row['quantity_outstanding'] ?? 0);
            $short += max(0, (int) ($row['quantity_outstanding'] ?? 0) - (int) ($row['quantity_accepted'] ?? 0));
        }

        $summary = [
            __('Received now').': '.$accepted,
            __('Rejected').': '.$rejected,
            __('Left outstanding').': '.$short.' of '.$outstanding,
        ];

        if ($short > 0) {
            $summary[] = __('The order stays partially received until the rest arrives.');
        }

        return implode(' · ', $summary);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('grn_number')->searchable(),
                Tables\Columns\TextColumn::make('procurementOrder.order_number')->label(__('LPO Reference'))->searchable(),
                Tables\Columns\TextColumn::make('received_date')->date(),
                Tables\Columns\TextColumn::make('receivedBy.name')->label(__('Received By')),
            ])
            ->actions([
                Tables\Actions\Action::make('compare_po')
                    ->label(__('Compare with PO'))
                    ->icon('heroicon-o-scale')
                    ->color('primary')
                    ->visible(fn (GoodsReceivedNote $record): bool => filled($record->procurement_order_id))
                    ->url(fn (GoodsReceivedNote $record): string => PurchaseOrderResource::getUrl('view', ['record' => $record->procurement_order_id]), shouldOpenInNewTab: true),
            ])
            ->bulkActions([]);
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
