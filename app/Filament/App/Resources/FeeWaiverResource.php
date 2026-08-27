<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\FeeWaiverResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Finance\Models\FeeWaiver;

class FeeWaiverResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Finance');
    }

    protected static ?string $model = FeeWaiver::class;

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Fee Waivers';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 3;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Waiver Details'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->placeholder(__('e.g. Sibling Discount (50%), Academic Scholarship')),

                        Forms\Components\Select::make('type')
                            ->options(['percentage' => __('Percentage (%)'), 'fixed' => __('Fixed Amount ($)')])
                            ->required()
                            ->live(), // Triggers reactive prefix change

                        // Dynamic prefix/suffix based on type selection
                        Forms\Components\TextInput::make('value')
                            ->numeric()
                            ->required()
                            ->prefix(fn (Forms\Get $get) => $get('type') === 'fixed' ? '$' : null)
                            ->suffix(fn (Forms\Get $get) => $get('type') === 'percentage' ? '%' : null)
                            ->placeholder(__('e.g. 50.00')),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('value')
                    ->formatStateUsing(fn ($record) => $record->type === 'percentage' ? $record->value.'%' : '$'.$record->value),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeeWaivers::route('/'),
            'create' => Pages\CreateFeeWaiver::route('/create'),
            'edit' => Pages\EditFeeWaiver::route('/{record}/edit'),
        ];
    }
}
