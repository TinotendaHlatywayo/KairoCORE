<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\FixedAssetResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Modules\Inventory\Models\DepreciationMethod;
use Modules\Inventory\Models\FixedAsset;
use Modules\Inventory\Models\InventoryLocation;
use Modules\Inventory\Services\DepreciationEngine;

class FixedAssetResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory & Procurement');
    }

    protected static ?string $model = FixedAsset::class;

    protected static ?string $navigationGroup = 'Inventory & Procurement';

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $recordTitleAttribute = 'asset_number';

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Asset Identification'))
                    ->schema([
                        Forms\Components\TextInput::make('asset_number')
                            ->required()
                            ->default(fn () => 'SC-'.now()->year.'-FA-'.str_pad((string) rand(1, 99999), 5, '0', STR_PAD_LEFT))
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('asset_name')
                            ->label(__('Asset Name'))
                            ->placeholder(__('e.g., Dell Latitude 5440 Laptop'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('serial_number'),
                        Forms\Components\Textarea::make('description')
                            ->label(__('Description'))
                            ->placeholder(__('Condition, specifications, included accessories...'))
                            ->rows(2)
                            ->maxLength(2000)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('inventory_item_id')
                            ->label(__('Linked Catalog Item (optional)'))
                            ->helperText(__('A fixed asset does not have to be a store item. Link one only when the asset is tracked as inventory.'))
                            ->relationship('inventoryItem', 'name')
                            ->searchable()
                            ->preload()
                            ->columnSpan(2),
                    ])->columns(3),

                Forms\Components\Section::make(__('Acquisition & Valuation'))
                    ->schema([
                        Forms\Components\DatePicker::make('acquisition_date')
                            ->required()
                            ->helperText(__('The day the asset was bought — every depreciation schedule is counted from this date.')),
                        Forms\Components\TextInput::make('purchase_cost')
                            ->label(__('Purchase Cost'))
                            ->numeric()
                            ->required()
                            ->prefix('$')
                            ->helperText(__('What the school paid for the asset, and the value depreciation starts from.')),
                        Forms\Components\TextInput::make('salvage_value')
                            ->label(__('Salvage Value'))
                            ->numeric()
                            ->default(0.00)
                            ->prefix('$')
                            ->helperText(__('What the asset is expected to be worth once its useful life ends.')),
                        Forms\Components\TextInput::make('current_value')
                            ->label(__('Current Value'))
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->helperText(__('Book value today. Leave blank to start at the purchase cost; it then moves with each posted depreciation.')),
                        Forms\Components\TextInput::make('useful_life_years')
                            ->numeric()
                            ->label(__('Useful Life (Years)'))
                            ->minValue(0)
                            ->helperText(__('Optional. Required before a depreciation schedule can be posted.')),
                        Forms\Components\Select::make('depreciation_method')
                            ->options(fn (): array => DepreciationMethod::optionsForSchool(self::currentSchoolId()))
                            ->default('straight_line')
                            ->searchable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('Method Name'))
                                    ->placeholder(__('e.g., Sum of the Years Digits'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionModalHeading(__('New depreciation method'))
                            ->createOptionAction(fn (FormAction $action) => $action->label(__('Add Method'))->modalSubmitActionLabel(__('Add Method')))
                            ->createOptionUsing(fn (array $data): string => DepreciationMethod::createForSchool(
                                self::currentSchoolId(),
                                (string) $data['name'],
                            )->key)
                            ->helperText(__('Straight Line and Double Declining are calculated automatically; other methods are recorded only.')),
                    ])->columns(5),

                Forms\Components\Section::make(__('Location & Stewardship'))
                    ->schema([
                        Forms\Components\Select::make('assigned_location_id')
                            ->label(__('Location'))
                            ->relationship('location', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('Location Name'))
                                    ->placeholder(__('e.g., Computer Lab, Head Office'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('code')
                                    ->label(__('Location Code'))
                                    ->placeholder(__('e.g., LAB-ICT1'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('type')
                                    ->options(self::locationTypeOptions())
                                    ->default('general')
                                    ->required(),
                            ])
                            ->createOptionModalHeading(__('New location'))
                            ->createOptionAction(fn (FormAction $action) => $action->label(__('Add Location'))->modalSubmitActionLabel(__('Add Location')))
                            ->createOptionUsing(fn (array $data): int => self::createLocation($data)),
                        Forms\Components\Select::make('custodian_id')
                            ->label(__('Custodian'))
                            ->relationship('custodian', 'name', fn (Builder $query): Builder => self::applyCustodianScope($query))
                            ->searchable()
                            ->preload()
                            ->helperText(__('Only registered staff members are listed — start typing a name to filter.'))
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('Custodian Name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label(__('Email'))
                                    ->email()
                                    ->maxLength(191),
                                Forms\Components\TextInput::make('phone')
                                    ->label(__('Phone'))
                                    ->tel()
                                    ->maxLength(60),
                            ])
                            ->createOptionModalHeading(__('New custodian'))
                            ->createOptionAction(fn (FormAction $action) => $action->label(__('Add Custodian'))->modalSubmitActionLabel(__('Add Custodian')))
                            ->createOptionUsing(fn (array $data): int => self::createCustodian($data)),
                        Forms\Components\Select::make('funding_source')
                            ->label(__('Funding Source'))
                            ->options(fn (): array => self::fundingSourceOptions())
                            ->searchable()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('Funding Source'))
                                    ->placeholder(__('e.g., NGO Sponsorship'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionModalHeading(__('New funding source'))
                            ->createOptionAction(fn (FormAction $action) => $action->label(__('Add Funding Source'))->modalSubmitActionLabel(__('Add Funding Source')))
                            ->createOptionUsing(fn (array $data): string => trim((string) $data['name'])),
                    ])->columns(3),
            ]);
    }

    protected static function currentSchoolId(): int
    {
        return (int) (current_tenant()?->id ?? auth()->user()?->school_id ?? 0);
    }

    /**
     * Narrow the custodian list to people who can actually hold an asset:
     * users linked to an HR employee record, users registered under a staff
     * role, and anyone already responsible for an asset so an existing
     * assignment never renders as a blank field. Students, parents and
     * platform accounts stay out of the list.
     */
    protected static function applyCustodianScope(Builder $query): Builder
    {
        $schoolId = self::currentSchoolId();

        return $query
            ->where(function (Builder $query) use ($schoolId): void {
                $query->whereHas('employee')
                    ->orWhereIn('users.requested_role', ['administrator', 'teaching_staff', 'non_teaching_staff'])
                    ->orWhereIn('users.id', FixedAsset::withoutTenantScope()
                        ->where('school_id', $schoolId)
                        ->whereNotNull('custodian_id')
                        ->select('custodian_id'));
            })
            ->orderBy('users.name');
    }

    /**
     * The shipped funding sources plus any source a school typed in through
     * the create form or the Excel import, so a custom value still renders as
     * a label instead of a blank field when the asset is reopened.
     *
     * @return array<string, string>
     */
    protected static function fundingSourceOptions(): array
    {
        $options = [
            'school_funds' => __('School Funds'),
            'government' => __('Government Grant'),
            'donor' => __('Donor Funded'),
            'pta' => __('PTA Contribution'),
        ];

        $used = FixedAsset::withoutTenantScope()
            ->where('school_id', self::currentSchoolId())
            ->whereNotNull('funding_source')
            ->distinct()
            ->pluck('funding_source');

        foreach ($used as $value) {
            $value = trim((string) $value);

            if ($value !== '' && ! isset($options[$value])) {
                $options[$value] = Str::headline($value);
            }
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    protected static function locationTypeOptions(): array
    {
        return [
            'general' => __('General'),
            'hostel' => __('Hostel'),
            'canteen' => __('Canteen'),
            'laboratory' => __('Laboratory'),
            'ict' => __('ICT / Computer Lab'),
            'sports' => __('Sports'),
            'clinic' => __('Clinic'),
        ];
    }

    /**
     * A location is created for the acting school. Filament's built-in
     * relationship create would leave school_id to the ambient tenant, so set
     * it here to keep the new row explicitly scoped.
     */
    protected static function createLocation(array $data): int
    {
        return (int) InventoryLocation::create([
            'school_id' => self::currentSchoolId(),
            'name' => trim((string) $data['name']),
            'code' => trim((string) $data['code']),
            'type' => (string) ($data['type'] ?? 'general'),
        ])->id;
    }

    /**
     * A custodian is a user account. Only the fields a steward needs are asked
     * for; the account is created active so the asset can be assigned to them
     * straight away, and the default password applies until they reset it.
     */
    protected static function createCustodian(array $data): int
    {
        $schoolId = self::currentSchoolId();

        $email = trim((string) ($data['email'] ?? ''));

        if ($email === '') {
            $email = 'custodian+'.Str::random(10).'@'.($schoolId ? $schoolId : 'school').'.local';
        }

        return (int) User::create([
            'school_id' => $schoolId,
            'name' => trim((string) $data['name']),
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'password' => 'Password@1',
            'account_status' => 'active',
        ])->id;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('asset_number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('asset_name')
                    ->label(__('Asset Name'))
                    ->searchable()
                    ->placeholder(fn (FixedAsset $record): string => $record->inventoryItem?->name ?? '—'),
                Tables\Columns\TextColumn::make('purchase_cost')->money('USD'),
                Tables\Columns\TextColumn::make('current_value')->money('USD'),
                Tables\Columns\TextColumn::make('depreciation_method')->badge(),
                Tables\Columns\TextColumn::make('location.name')->label(__('Current Room')),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('Post Depreciation')
                    ->label(__('Post Depreciation'))
                    ->icon('heroicon-o-calculator')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (FixedAsset $record) {
                        $engine = app(DepreciationEngine::class);

                        // Schools may pick a method the engine has no formula for,
                        // or leave the useful life blank. Say so instead of
                        // failing the request.
                        if (! $engine->supportsMethod($record->depreciation_method)) {
                            Notification::make()
                                ->title(__('Depreciation not posted'))
                                ->body(__('The method ":method" has no automatic formula. Choose Straight Line or Double Declining to post a schedule.', [
                                    'method' => (string) $record->depreciation_method,
                                ]))
                                ->warning()
                                ->send();

                            return;
                        }

                        if ((int) $record->useful_life_years <= 0) {
                            Notification::make()
                                ->title(__('Depreciation not posted'))
                                ->body(__('Set a useful life greater than zero on the asset before posting a schedule.'))
                                ->warning()
                                ->send();

                            return;
                        }

                        $engine->postScheduleToLedger($record);

                        Notification::make()
                            ->title(__('Depreciation Posted'))
                            ->body(__('The asset valuation schedule was recalculated and updated.'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFixedAssets::route('/'),
            'create' => Pages\CreateFixedAsset::route('/create'),
            'edit' => Pages\EditFixedAsset::route('/{record}/edit'),
        ];
    }
}
