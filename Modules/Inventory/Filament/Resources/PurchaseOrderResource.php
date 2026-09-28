<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Inventory\Filament\Resources\PurchaseOrderResource\Pages;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventorySupplier;
use Modules\Inventory\Models\ProcurementOrder;
use Modules\Inventory\Models\ProcurementRequest;
use Modules\Inventory\Services\ProcurementPipelineService;

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
                        Forms\Components\Select::make('procurement_request_id')
                            ->label(__('Request Number'))
                            ->placeholder(__('Select a request to autofill...'))
                            ->options(fn (?string $search = '') => ProcurementRequest::query()
                                ->where('school_id', current_tenant()?->id ?? auth()->user()?->school_id)
                                ->when($search, fn ($q) => fuzzy_search_where($q, ['request_number'], $search))
                                ->orderByDesc('created_at')
                                ->limit(50)
                                ->pluck('request_number', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => ($request = ProcurementRequest::find($value))
                                ? $request->request_number.($request->requester ? ' — '.$request->requester->name : '')
                                : null)
                            ->searchable()
                            ->searchPrompt(__('Type a request number (PR-...) to pull its details...'))
                            ->reactive()
                            ->helperText(__('Choosing a Purchase Request pulls its items, supplier and dates into this order.'))
                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set, $component): void {
                                self::applyRequestToOrder($state, $get, $set, $component->getRecord());
                            }),
                        Forms\Components\TextInput::make('order_number')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->default(fn () => 'LPO-'.now()->year.'-'.str_pad((string) rand(100, 9999), 4, '0', STR_PAD_LEFT)),
                        Forms\Components\Select::make('supplier_id')
                            ->options(fn (?string $search = '') => InventorySupplier::query()
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
                    ->description(__('The order total updates as you change quantities and unit costs.'))
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->minItems(1)
                            ->addActionLabel(__('Add an item'))
                            ->schema([
                                Forms\Components\Select::make('inventory_item_id')
                                    ->options(fn (?string $search = '') => inventory_item_search_options($search))
                                    ->getOptionLabelUsing(fn ($value) => inventory_item_label(InventoryItem::find($value)))
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
                        Forms\Components\Placeholder::make('order_total_summary')
                            ->label(__('Order Total'))
                            ->content(fn (Forms\Get $get): string => '$'.number_format(self::totalForLines($get('items')), 2)),
                    ]),
            ]);
    }

    /**
     * The read-only Order Summary shown in the Approve modal.
     *
     * These values have to be supplied through fillForm(). Defining them with
     * ->default() on the mounted action's own schema does not populate the
     * form, which is why the summary used to open empty.
     *
     * @return array<string, string>
     */
    public static function approvalSummary(ProcurementOrder $record): array
    {
        return [
            'order_number' => $record->order_number,
            'supplier' => $record->supplier?->name ?? '',
            'order_date' => $record->order_date?->format('Y-m-d') ?? '',
            'total_amount' => number_format((float) $record->total_amount, 2),
            'item_count' => (string) $record->items()->count(),
        ];
    }

    /**
     * Running total of the ordered-lines repeater, so the figure is visible
     * while the user edits instead of only after saving.
     *
     * @param  mixed  $items
     */
    public static function totalForLines($items): float
    {
        if (! is_array($items)) {
            return 0.0;
        }

        $total = 0.0;

        foreach ($items as $line) {
            if (! is_array($line)) {
                continue;
            }

            $total += (float) ($line['quantity_ordered'] ?? 0) * (float) ($line['unit_cost'] ?? 0);
        }

        return round($total, 2);
    }

    /**
     * Pull a linked Purchase Request into the order form: reuse the supplier
     * already chosen for that requisition, refresh the dates, and hydrate the
     * ordered-items repeater from the requisition lines. An existing order's
     * own lines are never overwritten.
     */
    public static function applyRequestToOrder($state, Forms\Get $get, Forms\Set $set, $record): void
    {
        if (! $state) {
            return;
        }

        $request = ProcurementRequest::find($state);

        if (! $request) {
            return;
        }

        if ($supplierId = $request->orders()->first()?->supplier_id) {
            $set('supplier_id', (int) $supplierId);
        }

        $set('order_date', now()->toDateString());

        if ($get('expected_delivery_date') === null) {
            $set('expected_delivery_date', now()->addDays(7)->toDateString());
        }

        if ($record instanceof ProcurementOrder && $record->items()->exists()) {
            return;
        }

        $rows = $request->items()->orderBy('id')->get()->map(fn ($line) => [
            'inventory_item_id' => $line->inventory_item_id,
            'is_fixed_asset' => (bool) $line->is_fixed_asset,
            'quantity_ordered' => (int) $line->quantity,
            'unit_cost' => (float) $line->estimated_unit_cost,
        ])->values()->all();

        if ($rows !== []) {
            $set('items', $rows);
        }
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
                        'success' => ['approved', 'completed'],
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
                    ->visible(fn (ProcurementOrder $record): bool => in_array($record->status, ['approved', 'sent', 'partially_received'])),
                Tables\Actions\Action::make('approve')
                    ->label(__('Approve'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (ProcurementOrder $record): bool => in_array($record->status, ['draft', 'sent']))
                    ->modalHeading(__('Approve Purchase Order?'))
                    ->modalDescription(fn (ProcurementOrder $record): string => __('On approval the order total is deducted from the selected bank account and a paid expense is recorded.'))
                    ->modalSubmitActionLabel(__('Approve & Deduct Funds'))
                    ->fillForm(fn (ProcurementOrder $record): array => [
                        ...self::approvalSummary($record),
                        'bank_account_id' => self::previousBankAccountId($record->school_id),
                    ])
                    ->form(fn (ProcurementOrder $record): array => [
                        Forms\Components\Section::make(__('Order Summary'))
                            ->schema([
                                Forms\Components\TextInput::make('order_number')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('supplier')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\DatePicker::make('order_date')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('total_amount')
                                    ->prefix('$')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('item_count')
                                    ->label(__('Number of Items'))
                                    ->disabled()
                                    ->dehydrated(false),
                            ])->columns(2),
                        Forms\Components\Select::make('bank_account_id')
                            ->label(__('Deduct From Bank Account'))
                            ->options(fn () => SchoolBankAccount::query()
                                ->where('school_id', $record->school_id)
                                ->where('is_active', true)
                                ->orderByDesc('is_default')
                                ->get()
                                ->mapWithKeys(fn ($account) => [$account->id => trim(implode(' — ', array_filter([
                                    $account->bank_name,
                                    $account->account_name,
                                    $account->account_number,
                                ])))])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->action(function (ProcurementOrder $record, array $data): void {
                        app(ProcurementPipelineService::class)->approveOrder($record, (int) $data['bank_account_id']);

                        Notification::make()
                            ->title(__('Purchase Order approved'))
                            ->body(__(':total deducted from the selected bank account.', ['total' => '$'.number_format((float) $record->total_amount, 2)]))
                            ->success()
                            ->send();
                    }),
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
            fn () => print ($pdf->output()),
            $order->order_number.'-Purchase-Order.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Resolve the bank account to pre-select when approving a purchase order:
     * the account most recently used on an approved order for this school,
     * falling back to the school's main (default) active account.
     */
    public static function previousBankAccountId(int $schoolId): ?int
    {
        $lastUsed = ProcurementOrder::query()
            ->where('school_id', $schoolId)
            ->whereNotNull('bank_account_id')
            ->whereIn('status', ['approved', 'completed', 'partially_received'])
            ->latest('approved_at')
            ->value('bank_account_id');

        return $lastUsed
            ?? SchoolBankAccount::where('school_id', $schoolId)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->value('id');
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['supplier', 'items.inventoryItem', 'grns.items', 'request.requester']);
    }
}
