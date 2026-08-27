<?php

declare(strict_types=1);

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\FixedAssetResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Modules\Inventory\Models\FixedAsset;
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
                        Forms\Components\Select::make('inventory_item_id')
                            ->relationship('inventoryItem', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('serial_number'),
                    ])->columns(3),

                Forms\Components\Section::make(__('Acquisition & Valuation'))
                    ->schema([
                        Forms\Components\DatePicker::make('acquisition_date')
                            ->required(),
                        Forms\Components\TextInput::make('purchase_cost')
                            ->numeric()
                            ->required()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('salvage_value')
                            ->numeric()
                            ->default(0.00)
                            ->prefix('$'),
                        Forms\Components\TextInput::make('useful_life_years')
                            ->numeric()
                            ->required()
                            ->label(__('Useful Life (Years)')),
                        Forms\Components\Select::make('depreciation_method')
                            ->options([
                                'straight_line' => __('Straight Line'),
                                'double_declining' => __('Double Declining'),
                            ])
                            ->required(),
                    ])->columns(5),

                Forms\Components\Section::make(__('Location & Stewardship'))
                    ->schema([
                        Forms\Components\Select::make('assigned_location_id')
                            ->relationship('location', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('custodian_id')
                            ->relationship('custodian', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('funding_source')
                            ->options([
                                'school_funds' => __('School Funds'),
                                'government' => __('Government Grant'),
                                'donor' => __('Donor Funded'),
                                'pta' => __('PTA Contribution'),
                            ]),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('asset_number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('inventoryItem.name')->label(__('Asset Name'))->searchable(),
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
