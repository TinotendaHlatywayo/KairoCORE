<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\RevenueStreamResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\RevenueStream;

class RevenueStreamResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Finance');
    }

    protected static ?string $model = RevenueStream::class;

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Revenue Streams';

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
                Forms\Components\Section::make(__('Revenue Stream Configuration'))
                    ->description(__('Specific income items or billing channels linked to revenue categories.'))
                    ->schema([
                        Forms\Components\Select::make('revenue_category_id')
                            ->label(__('Revenue Category'))
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('e.g., Bus Route A, Uniform Sales, Grade 1 Tuition')),
                        Forms\Components\TextInput::make('default_amount')
                            ->numeric()
                            ->prefix('$')
                            ->default(0)
                            ->required(),
                        Forms\Components\Select::make('account_id')
                            ->label(__('Ledger Account Override'))
                            ->options(Account::where('type', 'revenue')->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder(__('Optional override account')),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.name')->label(__('Category'))->badge()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('default_amount')->money('USD')->sortable(),
                Tables\Columns\TextColumn::make('account.name')->label(__('Ledger Account'))->toggleable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label(__('Active')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('revenue_category_id')
                    ->relationship('category', 'name')
                    ->label(__('Category')),
                Tables\Filters\TernaryFilter::make('is_active')->label(__('Active Status')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRevenueStreams::route('/'),
            'create' => Pages\CreateRevenueStream::route('/create'),
            'edit' => Pages\EditRevenueStream::route('/{record}/edit'),
        ];
    }
}
