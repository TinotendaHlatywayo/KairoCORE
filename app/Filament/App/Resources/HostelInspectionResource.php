<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\HostelInspectionResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\Hostels\Models\HostelInspection;

class HostelInspectionResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Boarding & Welfare');
    }

    protected static ?string $model = HostelInspection::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Boarding & Welfare';

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('room_id')
                            ->relationship('room', 'room_number')
                            ->required(),
                        Forms\Components\DatePicker::make('inspection_date')
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('cleanliness_score')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('inventory_status_score')
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('orderliness_score')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('passes_inspection')
                            ->options([
                                1 => __('Pass'),
                                0 => __('Fail'),
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('notes')->columnSpanFull(),
                        Forms\Components\Hidden::make('inspector_user_id')
                            ->default(fn () => Auth::id()),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('room.room_number')->sortable(),
                Tables\Columns\TextColumn::make('inspection_date')->date(),
                Tables\Columns\IconColumn::make('passes_inspection')->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostelInspections::route('/'),
            'create' => Pages\CreateHostelInspection::route('/create'),
            'edit' => Pages\EditHostelInspection::route('/{record}/edit'),
        ];
    }
}
