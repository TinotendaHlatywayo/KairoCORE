<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\HostelAttendanceResource\Pages;
use App\Filament\App\Resources\HostelAttendanceResource\RelationManagers\StudentsRelationManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\Hostels\Models\HostelAttendance;

class HostelAttendanceResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Boarding & Welfare');
    }

    protected static ?string $model = HostelAttendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

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
                            ->required(),
                        Forms\Components\DatePicker::make('date')
                            ->default(now())
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'morning' => __('Morning Roll Call'),
                                'evening' => __('Evening Roll Call'),
                                'curfew' => __('Curfew Verification'),
                            ])
                            ->required(),
                        Forms\Components\Hidden::make('recorded_by_user_id')
                            ->default(fn () => Auth::id()),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hostel.name')->sortable(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            StudentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHostelAttendances::route('/'),
            'create' => Pages\CreateHostelAttendance::route('/create'),
            'edit' => Pages\EditHostelAttendance::route('/{record}/edit'),
        ];
    }
}
