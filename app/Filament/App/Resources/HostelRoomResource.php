<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\HostelRoomResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Hostels\Models\Hostel;
use Modules\Hostels\Models\HostelRoom;

class HostelRoomResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Boarding & Welfare');
    }

    protected static ?string $model = HostelRoom::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationGroup = 'Boarding & Welfare';

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('hostel_id')
                            ->relationship('hostel', 'name')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($set) {
                                $set('floor_id', null);
                                $set('wing_id', null);
                            }),
                        Forms\Components\Select::make('floor_id')
                            ->relationship('floor', 'floor_name', fn ($query, $get) => $query->whereHas('building', fn ($q) => $q->where('hostel_id', $get('hostel_id')))
                            )
                            ->placeholder(__('Optional'))
                            ->reactive()
                            ->afterStateUpdated(fn ($set) => $set('wing_id', null)),
                        Forms\Components\Select::make('wing_id')
                            ->relationship('wing', 'name', function ($query, $get) {
                                $floorId = $get('floor_id');

                                if ($floorId) {
                                    return $query->where('floor_id', $floorId);
                                }

                                return $query->whereHas(
                                    'floor.building',
                                    fn ($q) => $q->where('hostel_id', $get('hostel_id'))
                                );
                            })
                            ->placeholder(__('Optional')),
                        Forms\Components\TextInput::make('room_number')
                            ->required(),
                        Forms\Components\TextInput::make('name'),
                        Forms\Components\Select::make('room_type')
                            ->options([
                                'dormitory' => __('Dormitory'),
                                'single' => __('Single'),
                                'double' => __('Double'),
                                'triple' => __('Triple'),
                                'quad' => __('Quad'),
                                'isolation' => __('Isolation Room'),
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('capacity')
                            ->numeric()
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('room_number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('hostel.name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('floor.floor_name')->sortable(),
                Tables\Columns\TextColumn::make('wing.name')->sortable(),
                Tables\Columns\TextColumn::make('room_type'),
                Tables\Columns\TextColumn::make('capacity')->numeric(),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('hostel_id')
                    ->label(__('Hostel'))
                    ->options(fn () => Hostel::query()->pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostelRooms::route('/'),
            'create' => Pages\CreateHostelRoom::route('/create'),
            'edit' => Pages\EditHostelRoom::route('/{record}/edit'),
        ];
    }
}
