<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\RevenueCategoryResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\RevenueCategory;

class RevenueCategoryResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Finance');
    }

    protected static ?string $model = RevenueCategory::class;

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Revenue Categories';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 2;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Revenue Category Setup'))
                    ->description(__('Manage unlimited income streams such as Tuition, Transport, Boarding, School Shop, Grants, etc.'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('e.g., Student Fees, Transport, Uniform Sales')),
                        Forms\Components\Select::make('account_id')
                            ->label(__('Default Ledger Account'))
                            ->options(Account::where('type', 'revenue')->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder(__('Select General Ledger Revenue Account')),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->placeholder(__('Category notes and accounting rules...')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('account.name')->label(__('Ledger Account'))->badge()->color('success'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label(__('Active')),
                Tables\Columns\TextColumn::make('streams_count')->counts('streams')->label(__('Income Streams'))->badge(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRevenueCategories::route('/'),
            'create' => Pages\CreateRevenueCategory::route('/create'),
            'edit' => Pages\EditRevenueCategory::route('/{record}/edit'),
        ];
    }
}
