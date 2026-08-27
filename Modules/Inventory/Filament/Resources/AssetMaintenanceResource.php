<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Inventory\Filament\Resources\AssetMaintenanceResource\Pages;
use Modules\Inventory\Models\AssetMaintenanceLog;

class AssetMaintenanceResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Inventory & Procurement');
    }

    protected static ?string $model = AssetMaintenanceLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Asset Maintenance';

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
                Forms\Components\Section::make(__('Service Log Details'))
                    ->schema([
                        Forms\Components\Select::make('fixed_asset_id')
                            ->relationship('fixedAsset', 'asset_number')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->placeholder(__('e.g., 10,000 KM Engine Oil Change')),
                        Forms\Components\Select::make('type')
                            ->options([
                                'preventive' => __('Preventive (Regular schedule)'),
                                'corrective' => __('Corrective (Breakdown repair)'),
                                'calibration' => __('Calibration'),
                            ])
                            ->required()
                            ->default('preventive'),
                    ])->columns(3),

                Forms\Components\Section::make(__('Schedule Frequency'))
                    ->schema([
                        Forms\Components\Select::make('schedule_type')
                            ->options([
                                'one_time' => __('One Time Service'),
                                'recurring' => __('Recurring Cycle'),
                            ])
                            ->required()
                            ->reactive()
                            ->default('one_time'),
                        Forms\Components\TextInput::make('recurrence_interval_days')
                            ->numeric()
                            ->label(__('Recurrence Interval (Days)'))
                            ->visible(fn (Forms\Get $get) => $get('schedule_type') === 'recurring')
                            ->required(fn (Forms\Get $get) => $get('schedule_type') === 'recurring'),
                        Forms\Components\DatePicker::make('scheduled_date')
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make(__('Execution Reconciliation'))
                    ->schema([
                        Forms\Components\DatePicker::make('completed_date'),
                        Forms\Components\TextInput::make('cost')
                            ->numeric()
                            ->prefix('$')
                            ->default(0.00),
                        Forms\Components\TextInput::make('performed_by')
                            ->placeholder(__('Technician name / External garage')),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => __('Pending'),
                                'in_progress' => __('In Progress'),
                                'completed' => __('Completed'),
                                'overdue' => __('Overdue'),
                            ])
                            ->required()
                            ->default('pending'),
                        Forms\Components\Textarea::make('notes')->columnSpanFull(),
                    ])->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fixedAsset.asset_number')->label(__('Asset Code'))->searchable(),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('scheduled_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('cost')->money('USD')->alignEnd(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'overdue',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssetMaintenances::route('/'),
            'create' => Pages\CreateAssetMaintenance::route('/create'),
            'edit' => Pages\EditAssetMaintenance::route('/{record}/edit'),
        ];
    }
}
