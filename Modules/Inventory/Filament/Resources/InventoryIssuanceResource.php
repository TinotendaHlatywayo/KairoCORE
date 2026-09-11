<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Filament\Resources\InventoryIssuanceResource\Pages;
use Modules\Inventory\Models\InventoryIssuance;
use Modules\Inventory\Models\InventoryStockMovement;
use Modules\Students\Models\Student;

class InventoryIssuanceResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory & Procurement');
    }

    protected static ?string $model = InventoryIssuance::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Stock Issuances';

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
                Forms\Components\Section::make(__('Distribution Details'))
                    ->schema([
                        Forms\Components\Select::make('inventory_item_id')
                            ->label(__('Inventory Item'))
                            ->options(fn (?string $search = '') => inventory_item_search_options($search))
                            ->getOptionLabelUsing(fn ($value) => inventory_item_label(\Modules\Inventory\Models\InventoryItem::find($value)))
                            ->searchable()
                            ->optionsLimit(50)
                            ->required()
                            ->reactive()
                            ->placeholder(__('Type to search (matches name, SKU or type — typos are OK)...'))
                            ->searchPrompt(__('Search by item name, SKU or type...'))
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('inventory_batch_id', null);
                                $set('quantity', null);
                            }),

                        Forms\Components\Select::make('inventory_location_id')
                            ->relationship('location', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->placeholder(__('Type to search or create a storage location...'))
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->placeholder(__('e.g., Room 104, Lab Cabinet B'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->placeholder(__('e.g., LOC-RM104'))
                                    ->default(fn () => 'LOC-'.str_pad((string) rand(100, 9999), 4, '0', STR_PAD_LEFT))
                                    ->unique('inventory_locations', 'code', ignoreRecord: true),
                            ]),

                        Forms\Components\Select::make('inventory_batch_id')
                            ->label(__('Batch Number (Optional)'))
                            ->relationship('batch', 'batch_number', modifyQueryUsing: function ($query, Forms\Get $get) {
                                return $query->where('inventory_item_id', $get('inventory_item_id'))->where('current_quantity', '>', 0);
                            })
                            ->searchable()
                            ->preload()
                            ->placeholder(__('Select a tracked lot batch...')),

                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(1)
                            ->helperText(fn (Forms\Get $get): ?string => $get('inventory_item_id') ? __('Available on hand: ').(\Modules\Inventory\Models\InventoryItem::find($get('inventory_item_id'))?->current_quantity ?? 0).' unit(s).' : null)
                            ->rule(function (Forms\Get $get): \Closure {
                                return function (string $attribute, $value, \Closure $fail) use ($get): void {
                                    $itemId = $get('inventory_item_id');

                                    if (! $itemId) {
                                        $fail(__('Select an inventory item before entering a quantity.'));

                                        return;
                                    }

                                    $item = \Modules\Inventory\Models\InventoryItem::find($itemId);
                                    $available = (int) ($item?->current_quantity ?? 0);

                                    if ((int) $value > $available) {
                                        $fail(__('You can only issue ').$available.__(' unit(s) of "').($item?->name ?? '').__('" — this item does not have enough on hand. Please reduce the quantity or contact the storekeeper.'));
                                    }
                                };
                            })
                            ->placeholder(__('Enter quantity to issue...')),
                    ])->columns(4),

                Forms\Components\Section::make(__('Recipient Assignment'))
                    ->schema([
                        Forms\Components\Select::make('issued_to_type')
                            ->required()
                            ->options([
                                User::class => __('Staff Member'),
                                Student::class => __('Student'),
                            ])
                            ->reactive()
                            ->placeholder(__('Select recipient category...')),

                        Forms\Components\Select::make('issued_to_id')
                            ->label(__('Select Recipient'))
                            ->searchable()
                            ->required()
                            ->placeholder(__('Search and select recipient...'))
                            ->searchPrompt(__('Type part of the name — typos are OK (e.g. "crls" finds "Charles")...'))
                            ->options(function (Forms\Get $get, ?string $search = '') {
                                $type = $get('issued_to_type');

                                if (! $type) {
                                    return [];
                                }

                                if ($type === Student::class) {
                                    $query = Student::query();
                                    fuzzy_search_where($query, ['name', 'student_id_number', 'admission_number'], $search);

                                    return $query->limit(50)->get()->mapWithKeys(fn ($s) => [
                                        $s->id => $s->name.' ('.$s->student_id_number.($s->currentEnrollment?->grade?->name ? ', '.$s->currentEnrollment->grade->name : '').')',
                                    ])->all();
                                }

                                $query = User::query();
                                fuzzy_search_where($query, ['name', 'email', 'phone'], $search);

                                return $query->limit(50)->get()->mapWithKeys(fn ($u) => [
                                    $u->id => $u->name.($u->employee?->employee_number ? ' ('.$u->employee->employee_number.')' : ''),
                                ])->all();
                            })
                            ->getOptionLabelUsing(function (Forms\Get $get, $value): ?string {
                                $type = $get('issued_to_type');

                                if (! $type) {
                                    return null;
                                }

                                if ($type === Student::class) {
                                    return optional(Student::find($value))->name;
                                }

                                return optional(User::find($value))->name;
                            }),
                    ])->columns(2),

                Forms\Components\Section::make(__('Return Tracker'))
                    ->schema([
                        Forms\Components\Toggle::make('is_returnable')
                            ->label(__('Is Returnable Asset (e.g., textbook, laptop)'))
                            ->reactive(),
                        Forms\Components\DatePicker::make('expected_return_date')
                            ->visible(fn (Forms\Get $get) => (bool) $get('is_returnable'))
                            ->required(fn (Forms\Get $get) => (bool) $get('is_returnable'))
                            ->placeholder(__('Select due return date...')),
                        Forms\Components\Select::make('condition_on_issue')
                            ->options([
                                'excellent' => __('Excellent'),
                                'good' => __('Good'),
                                'fair' => __('Fair'),
                                'poor' => __('Poor'),
                            ])
                            ->required()
                            ->default('good')
                            ->placeholder(__('Select initial condition...')),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('inventoryItem.name')->label(__('Item'))->searchable(),
                Tables\Columns\TextColumn::make('location.name')
                    ->label(__('Source Location'))
                    ->default(__('Default General Store')),
                Tables\Columns\TextColumn::make('quantity')->alignEnd(),
                Tables\Columns\TextColumn::make('issued_to_type')
                    ->label(__('Recipient Type'))
                    ->formatStateUsing(fn ($state) => class_basename($state)),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'primary' => 'issued',
                        'success' => 'returned',
                        'danger' => 'lost',
                        'warning' => 'damaged',
                    ]),
                Tables\Columns\TextColumn::make('expected_return_date')->date(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('Return Asset')
                    ->label(__('Return Asset'))
                    ->icon('heroicon-o-arrow-down-left-icon')
                    ->color('success')
                    ->visible(fn (InventoryIssuance $record) => $record->is_returnable && $record->status === 'issued')
                    ->form([
                        Forms\Components\Select::make('condition_on_return')
                            ->options([
                                'excellent' => __('Excellent'),
                                'good' => __('Good'),
                                'fair' => __('Fair'),
                                'poor' => __('Poor'),
                                'damaged' => __('Damaged/Broken'),
                            ])
                            ->required()
                            ->default('good'),
                        Forms\Components\TextInput::make('remarks')->placeholder(__('Optional notes on return condition')),
                    ])
                    ->action(function (InventoryIssuance $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $record->update([
                                'status' => $data['condition_on_return'] === 'damaged' ? 'damaged' : 'returned',
                                'condition_on_return' => $data['condition_on_return'],
                                'actual_return_date' => now(),
                                'remarks' => $data['remarks'],
                            ]);

                            $item = $record->inventoryItem;

                            if ($data['condition_on_return'] !== 'damaged') {
                                $item->increment('current_quantity', $record->quantity);

                                InventoryStockMovement::create([
                                    'school_id' => $record->school_id,
                                    'inventory_item_id' => $item->id,
                                    'inventory_location_id' => $record->inventory_location_id ?? $this->getDefaultWarehouse($record->school_id),
                                    'inventory_batch_id' => $record->inventory_batch_id,
                                    'type' => 'return',
                                    'quantity' => $record->quantity,
                                    'unit_cost' => $item->average_unit_cost,
                                    'remarks' => "Returned asset: {$item->name} from ".class_basename($record->issued_to_type)." ID: {$record->issued_to_id}",
                                ]);
                            }
                        });

                        Notification::make()
                            ->title(__('Asset Returned'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected static function getDefaultWarehouse(int $schoolId): int
    {
        return (int) DB::table('inventory_locations')
            ->where('school_id', $schoolId)
            ->where('type', 'general')
            ->orderBy('id', 'ASC')
            ->value('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryIssuances::route('/'),
            'create' => Pages\CreateInventoryIssuance::route('/create'),
            'edit' => Pages\EditInventoryIssuance::route('/{record}/edit'),
        ];
    }
}
