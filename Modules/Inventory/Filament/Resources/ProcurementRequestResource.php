<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Inventory\Filament\Resources\ProcurementRequestResource\Pages;
use Modules\Inventory\Models\ProcurementRequest;

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
                Forms\Components\Section::make('Request Metadata')
                    ->schema([
                        Forms\Components\TextInput::make('request_number')
                            ->required()
                            ->disabled()
                            ->default(fn () => 'PR-'.now()->year.'-'.str_pad((string) rand(100, 9999), 4, '0', STR_PAD_LEFT)),
                        Forms\Components\Select::make('requester_id')
                            ->relationship('requester', 'name')
                            ->default(fn () => auth()->id())
                            ->disabled()
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
                        Forms\Components\TextInput::make('department_id')
                            ->placeholder(__('e.g., Science, Admin')),
                    ])->columns(4),

                Forms\Components\Section::make('Requisitioned Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\TextInput::make('item_name')
                                    ->required()
                                    ->placeholder(__('Item name/specification'))
                                    ->columnSpan(4),
                                Forms\Components\Select::make('inventory_item_id')
                                    ->relationship('inventoryItem', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder(__('Link to catalog (optional)'))
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('estimated_unit_cost')
                                    ->numeric()
                                    ->prefix('$')
                                    ->columnSpan(3),
                            ])->columns(12),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('request_number')->searchable(),
                Tables\Columns\TextColumn::make('requester.name')->searchable(),
                Tables\Columns\TextColumn::make('urgency')->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'draft',
                        'primary' => 'pending_approval',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->date(),
            ]);
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
