<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\AccountResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Finance\Models\Account;

class AccountResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Finance');
    }

    protected static ?string $model = Account::class;

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Chart of Accounts';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 1;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Ledger Account')
                    ->description(__('Define accounts for assets, liabilities, equity, revenue, and operating expenses.'))
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(50)
                            ->placeholder(__('e.g., 1000, 4000, 5000')),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('e.g., Cash on Hand, Tuition Revenue, Laboratory Expenses')),
                        Forms\Components\Select::make('type')
                            ->options([
                                'asset' => __('Asset'),
                                'liability' => __('Liability'),
                                'equity' => __('Equity'),
                                'revenue' => __('Revenue'),
                                'expense' => __('Expense'),
                            ])
                            ->required(),
                        Forms\Components\Select::make('normal_balance')
                            ->options([
                                'debit' => __('Debit'),
                                'credit' => __('Credit'),
                            ])
                            ->default('debit')
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->placeholder(__('Account description and accounting notes...')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable()->sortable()->badge()->color('primary'),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable()->weight('bold'),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'asset',
                        'warning' => 'liability',
                        'info' => 'equity',
                        'primary' => 'revenue',
                        'danger' => 'expense',
                    ]),
                Tables\Columns\TextColumn::make('normal_balance')->badge(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label(__('Active')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'asset' => __('Asset'),
                        'liability' => __('Liability'),
                        'equity' => __('Equity'),
                        'revenue' => __('Revenue'),
                        'expense' => __('Expense'),
                    ]),
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
            'index' => Pages\ListAccounts::route('/'),
            'create' => Pages\CreateAccount::route('/create'),
            'edit' => Pages\EditAccount::route('/{record}/edit'),
        ];
    }
}
