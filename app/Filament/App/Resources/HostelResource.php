<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\HostelResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Hostels\Models\Hostel;

class HostelResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Boarding & Welfare');
    }

    use ModuleAwareActiveNavigation;

    protected static ?string $model = Hostel::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationGroup = 'Boarding & Welfare';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->options([
                                'boys' => __('Boys Hostel'),
                                'girls' => __('Girls Hostel'),
                                'mixed' => __('Mixed Dormitory'),
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('capacity')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => __('Active'),
                                'maintenance' => __('Under Maintenance'),
                                'inactive' => __('Deactivated'),
                            ])
                            ->required()
                            ->default('active'),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('buildings')
                            ->relationship('buildings')
                            ->label(__('Buildings, Floors & Wings'))
                            ->collapsible()
                            ->defaultItems(0)
                            ->columnSpanFull()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('Building Name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('description')
                                    ->label(__('Building Description')),
                                Forms\Components\Repeater::make('floors')
                                    ->relationship('floors')
                                    ->label(__('Floors'))
                                    ->collapsible()
                                    ->schema([
                                        Forms\Components\TextInput::make('floor_number')
                                            ->label(__('Floor Number'))
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('floor_name')
                                            ->label(__('Floor Name'))
                                            ->maxLength(255),
                                        Forms\Components\Repeater::make('wings')
                                            ->relationship('wings')
                                            ->label(__('Wings'))
                                            ->collapsible()
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label(__('Wing Name'))
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('description')
                                                    ->label(__('Wing Description')),
                                            ]),
                                    ]),
                            ]),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('capacity')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostels::route('/'),
            'create' => Pages\CreateHostel::route('/create'),
            'edit' => Pages\EditHostel::route('/{record}/edit'),
        ];
    }
}
