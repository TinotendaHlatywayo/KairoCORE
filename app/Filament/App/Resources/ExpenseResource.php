<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\ExpenseResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Finance\Models\Expense;

class ExpenseResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Finance');
    }

    protected static ?string $model = Expense::class;

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationIcon = 'heroicon-o-receipt-refund';

    protected static ?string $navigationLabel = 'Recorded Expenses';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 4;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Expense Transaction')
                    ->description(__('Record procurement and operating expenses linked to suppliers and expense types.'))
                    ->schema([
                        Forms\Components\Select::make('expense_type_id')
                            ->label(__('Expense Type'))
                            ->relationship('expenseType', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('supplier_id')
                            ->label(__('Supplier / Vendor'))
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder(__('Optional supplier')),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('$')
                            ->required(),
                        Forms\Components\DatePicker::make('expense_date')
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('reference_number')
                            ->label(__('Invoice / Receipt #'))
                            ->maxLength(100),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => __('Pending Approval'),
                                'approved' => __('Approved'),
                                'paid' => __('Paid'),
                            ])
                            ->default('paid')
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull()
                            ->placeholder(__('Expense particulars, project code, or justification...')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('expenseType.name')->label(__('Expense Type'))->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('supplier.name')->label(__('Supplier'))->searchable(),
                Tables\Columns\TextColumn::make('amount')->money('USD')->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'approved',
                        'success' => 'paid',
                    ]),
                Tables\Columns\TextColumn::make('reference_number')->label(__('Ref #'))->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => __('Pending'),
                        'approved' => __('Approved'),
                        'paid' => __('Paid'),
                    ]),
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
