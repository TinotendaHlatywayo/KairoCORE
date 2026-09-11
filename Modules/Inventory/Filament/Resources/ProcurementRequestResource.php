<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\Department;
use Modules\Inventory\Filament\Resources\ProcurementRequestResource\Pages;
use Modules\Inventory\Models\InventoryCategory;
use Modules\Inventory\Models\InventoryItem;
use Modules\Inventory\Models\InventorySupplier;
use Modules\Inventory\Models\ProcurementOrder;
use Modules\Inventory\Models\ProcurementOrderItem;
use Modules\Inventory\Models\ProcurementRequest;
use Modules\Inventory\Models\ProcurementRequestItem;

class ProcurementRequestResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory & Procurement');
    }

    protected static ?string $model = ProcurementRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationLabel = 'Purchase Requests';

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
                Forms\Components\Section::make(__('Request Metadata'))
                    ->schema([
                        Forms\Components\TextInput::make('request_number')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->default(fn () => 'PR-'.now()->year.'-'.str_pad((string) rand(100, 9999), 4, '0', STR_PAD_LEFT)),
                        Forms\Components\Select::make('requester_id')
                            ->relationship('requester', 'name')
                            ->default(fn () => auth()->id())
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Forms\Components\Select::make('urgency')
                            ->options([
                                'low' => __('Low'),
                                'medium' => __('Medium'),
                                'high' => __('High'),
                                'emergency' => __('Emergency'),
                            ])
                            ->required()
                            ->default('medium'),
                        Forms\Components\Select::make('department_id')
                            ->label(__('Department'))
                            ->options(fn () => Department::query()->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder(__('Select department...')),
                    ])->columns(4),

                Forms\Components\Section::make(__('Purpose'))
                    ->schema([
                        Forms\Components\Textarea::make('purpose')
                            ->rows(2)
                            ->placeholder(__('Briefly describe why these items are required and how they will be used...'))
                            ->maxLength(2000)
                            ->helperText(__('This helps the procurement officer approve the requisition faster.')),
                    ]),

                Forms\Components\Section::make(__('Requisitioned Items'))
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\TextInput::make('item_name')
                                    ->required()
                                    ->placeholder(__('Item name/specification'))
                                    ->columnSpan(3),
                                Forms\Components\Select::make('inventory_item_id')
                                    ->relationship('inventoryItem', 'name')
                                    ->searchable()
                                    ->options(fn (?string $search = '') => inventory_item_search_options($search))
                                    ->getOptionLabelUsing(fn ($value) => inventory_item_label(\Modules\Inventory\Models\InventoryItem::find($value)))
                                    ->optionsLimit(50)
                                    ->placeholder(__('Link to catalog (optional)'))
                                    ->columnSpan(3),
                                Forms\Components\Toggle::make('is_fixed_asset')
                                    ->label(__('Fixed Asset?'))
                                    ->default(false)
                                    ->helperText(__('Check if capitalized asset'))
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('estimated_unit_cost')
                                    ->numeric()
                                    ->prefix('$')
                                    ->columnSpan(2),
                            ])->columns(12),
                    ]),

                Forms\Components\Section::make(__('Signatories'))
                    ->schema([
                        Forms\Components\TextInput::make('requester_signature')
                            ->label(__('Requester Name & Signature'))
                            ->placeholder(__('e.g., John Mwangi'))
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('officer_signature')
                            ->label(__('Procurement Officer Name'))
                            ->placeholder(__('e.g., Alice Otieno'))
                            ->columnSpan(1),
                        Forms\Components\DatePicker::make('date_signed')
                            ->label(__('Date Signed'))
                            ->default(now())
                            ->displayFormat('d/m/Y')
                            ->columnSpan(1),
                    ])->columns(3)]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('request_number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('requester.name')->searchable(),
                Tables\Columns\TextColumn::make('items')->label(__('Items'))
                    ->state(fn (ProcurementRequest $record): int => $record->items()->count())
                    ->alignRight(),
                Tables\Columns\TextColumn::make('urgency')->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'draft',
                        'primary' => 'pending_approval',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('approver.name')->label(__('Approved By'))->placeholder('-'),
                Tables\Columns\TextColumn::make('approved_at')->dateTime()->sortable()->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')->date()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => __('Draft'),
                    'pending_approval' => __('Pending Approval'),
                    'approved' => __('Approved'),
                    'rejected' => __('Rejected'),
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label(__('Approve'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (ProcurementRequest $record): bool => $record->status === 'pending_approval' || $record->status === 'draft')
                    ->requiresConfirmation()
                    ->modalHeading(__('Approve this Purchase Request?'))
                    ->modalDescription(fn (ProcurementRequest $record): string => __('On approval, a Purchase Order (LPO) is auto-generated with the items from this request. Brand-new items not yet in the store catalog are automatically added to it so the LPO can be saved.'))
                    ->modalSubmitActionLabel(__('Approve & Generate LPO'))
                    ->fillForm(fn (ProcurementRequest $record): array => [
                        'supplier_id' => $record->orders()->first()?->supplier_id,
                    ])
                    ->form([
                        Forms\Components\Select::make('supplier_id')
                            ->label(__('Preferred Supplier'))
                            ->searchable()
                            ->preload()
                            ->options(fn () => InventorySupplier::query()->orderBy('name')->pluck('name', 'id'))
                            ->placeholder(__('Select the supplier for the purchase order...')),
                    ])
                    ->action(function (ProcurementRequest $record, array $data): void {
                        self::approveRequest($record, (int) ($data['supplier_id'] ?? 0));
                    }),
                Tables\Actions\Action::make('pdf')
                    ->label(__('PDF'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(fn (ProcurementRequest $record) => self::streamRequisitionPdf($record)),
            ])
            ->bulkActions([]);
    }

    /**
     * Approve a procurement request and auto-generate the matching Purchase Order (LPO).
     */
    public static function approveRequest(ProcurementRequest $request, int $supplierId): void
    {
        if (in_array($request->status, ['approved', 'rejected'], true)) {
            Notification::make()
                ->title(__('Request already finalised'))
                ->body(__('This request is already :status and can no longer be approved.', ['status' => $request->status]))
                ->warning()
                ->send();

            return;
        }

        $items = $request->items()->with('inventoryItem')->get();

        $supplier = InventorySupplier::find($supplierId);

        if (! $supplier) {
            Notification::make()
                ->title(__('Select a supplier'))
                ->body(__('A supplier is required to generate the Purchase Order.'))
                ->danger()
                ->send();

            return;
        }

        DB::transaction(function () use ($request, $supplier, $items) {
            $total = 0.0;
            foreach ($items as $item) {
                $total += (float) $item->quantity * (float) $item->estimated_unit_cost;
            }

            $max = (int) ProcurementOrder::where('school_id', $request->school_id)->count();
            $orderNumber = 'PO-'.now()->year.'-'.str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);

            $order = ProcurementOrder::create([
                'school_id' => $request->school_id,
                'procurement_request_id' => $request->id,
                'supplier_id' => $supplier->id,
                'order_number' => $orderNumber,
                'order_date' => now()->toDateString(),
                'expected_delivery_date' => now()->addDays(7)->toDateString(),
                'status' => 'draft',
                'total_amount' => $total,
            ]);

            $autoProvisioned = [];
            $fallbackCategoryId = null;

            foreach ($items as $item) {
                // Any requester can ask for a completely new item not yet in the
                // catalog. Auto-provision it so the LPO line still has a valid FK.
                $wasProvisioned = self::ensureItemLinked($request, $item, $fallbackCategoryId);

                ProcurementOrderItem::create([
                    'procurement_order_id' => $order->id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'quantity_ordered' => $item->quantity,
                    'quantity_received' => 0,
                    'unit_cost' => (float) $item->estimated_unit_cost,
                    'is_fixed_asset' => (bool) $item->is_fixed_asset,
                ]);

                if ($wasProvisioned) {
                    $autoProvisioned[] = $item->item_name;
                }
            }

            $request->update([
                'status' => 'approved',
                'approved_by_id' => Auth::id(),
                'approved_at' => now(),
            ]);

            Notification::make()
                ->title(__('Purchase Order generated'))
                ->body(__('Purchase order :number has been created for request :req.', [
                    'number' => $orderNumber,
                    'req' => $request->request_number,
                ]))
                ->success()
                ->send();

            if ($autoProvisioned !== []) {
                Notification::make()
                    ->title(__('New items added to catalog'))
                    ->body(__('These requisitioned items did not exist yet, so they were created automatically in the store catalog so the LPO could be saved: ').implode(', ', $autoProvisioned).'.')
                    ->info()
                    ->send();
            }
        });
    }

    /**
     * Ensure a requisition item is linked to an inventory item. Reuses an
     * existing catalog item with a matching (case-insensitive) name, otherwise
     * auto-provisions a new InventoryItem and links it.
     *
     * Returns true when a brand-new item was provisioned.
     */
    protected static function ensureItemLinked(ProcurementRequest $request, ProcurementRequestItem $item, ?int &$fallbackCategoryId): bool
    {
        if ($item->inventory_item_id) {
            return false;
        }

        $name = trim((string) $item->item_name);

        if ($name === '') {
            throw new \RuntimeException(__('A requisitioned item is missing its name and cannot be added to the catalog.'));
        }

        // Reuse an existing catalog item with the same normalized name first.
        $existing = InventoryItem::withoutTenantScope()
            ->where('school_id', $request->school_id)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->orderBy('id', 'ASC')
            ->first();

        if ($existing) {
            $item->inventory_item_id = $existing->id;
            $item->save();

            return false;
        }

        if ($fallbackCategoryId === null) {
            $fallbackCategoryId = self::resolveFallbackCategoryId($request->school_id);
        }

        $slug = \Illuminate\Support\Str::slug($name);
        $sku = strtoupper(substr($slug ?: 'ITEM', 0, 24)).'-'.strtoupper(\Illuminate\Support\Str::random(4));

        $newItem = InventoryItem::create([
            'school_id' => $request->school_id,
            'category_id' => $fallbackCategoryId,
            'sku' => $sku,
            'name' => $name,
            'description' => $item->specifications ?: null,
            'received_date' => now()->toDateString(),
            'item_type' => $item->is_fixed_asset ? 'fixed_asset' : 'consumable',
            'unit_of_measure' => 'pieces',
            'reorder_level' => 10,
            'current_quantity' => 0,
            'average_unit_cost' => (float) $item->estimated_unit_cost,
            'is_saleable' => false,
            'sale_price' => 0,
            'meta_data' => ['source' => 'auto-provisioned from requisition '.$request->request_number],
        ]);

        // Capitalized purchases land in the Fixed Assets register immediately;
        // the quantity/stock ledger is fed later when the purchase order's
        // Goods Received Note is processed (see ProcurementPipelineService).
        if ($item->is_fixed_asset) {
            \Modules\Inventory\Models\FixedAsset::create([
                'school_id' => $request->school_id,
                'inventory_item_id' => $newItem->id,
                'asset_number' => 'FA-'.now()->year.'-'.str_pad((string) rand(10, 99999), 5, '0', STR_PAD_LEFT),
                'acquisition_date' => now(),
                'purchase_cost' => (float) $item->estimated_unit_cost,
                'salvage_value' => round((float) $item->estimated_unit_cost * 0.1, 2),
                'useful_life_years' => 5,
                'depreciation_method' => 'straight_line',
                'current_value' => (float) $item->estimated_unit_cost,
                'status' => 'active',
            ]);
        }

        $item->inventory_item_id = $newItem->id;
        $item->save();

        return true;
    }

    /**
     * Return an existing inventory category for the school, creating a
     * "General / Uncategorized" one if the store has none yet.
     */
    protected static function resolveFallbackCategoryId(int $schoolId): int
    {
        $category = InventoryCategory::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->orderBy('id', 'ASC')
            ->first();

        if ($category) {
            return (int) $category->id;
        }

        return (int) InventoryCategory::create([
            'school_id' => $schoolId,
            'name' => __('General / Uncategorized'),
            'description' => __('Auto-created fallback category for items provisioned from purchase requisitions.'),
        ])->id;
    }

    /**
     * Stream the printable Purchase Request PDF.
     */
    public static function streamRequisitionPdf(ProcurementRequest $request)
    {
        $school = current_tenant();

        $departmentName = $request->department_id
            ? (Department::find($request->department_id)?->name ?? $request->department_id)
            : '-';

        $pdf = Pdf::loadView('modules.inventory.purchase-requisition-pdf', [
            'school' => $school,
            'request' => $request->load(['items.inventoryItem', 'requester']),
            'departmentName' => $departmentName,
            'primaryColor' => '#5b4fe9',
        ])->setPaper('a4');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            $request->request_number.'-Requisition.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProcurementRequests::route('/'),
            'create' => Pages\CreateProcurementRequest::route('/create'),
            'edit' => Pages\EditProcurementRequest::route('/{record}/edit'),
        ];
    }
}
