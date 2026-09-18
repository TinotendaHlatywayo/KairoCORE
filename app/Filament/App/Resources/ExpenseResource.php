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
use Modules\Finance\Models\SchoolBankAccount;

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
                    ->description(__('Record procurement and operating expenses. Salaries and procured inventory/asset items are posted here automatically; use this form for ad-hoc manual expenses.'))
                    ->schema([
                        Forms\Components\TextInput::make('expense_name')
                            ->label(__('Expense Name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('e.g., Classroom Chalk Purchase, Electricity Bill')),
                        Forms\Components\Select::make('expense_category_id')
                            ->label(__('Expense Category'))
                            ->relationship('expenseCategory', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionAction(fn (Forms\Components\Actions\Action $action) => $action
                                ->modalHeading(__('New Expense Category'))
                                ->modalSubmitActionLabel(__('Save Category')))
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('Category Name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder(__('e.g., Textbooks, Repairs, Utilities')),
                                Forms\Components\Textarea::make('description')
                                    ->label(__('Description'))
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\Select::make('expense_type_id')
                            ->label(__('Expense Type (optional)'))
                            ->relationship('expenseType', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText(__('Optional legacy type for extra classification.')),
                        Forms\Components\Select::make('bank_account_id')
                            ->label(__('Bank Account'))
                            ->options(fn () => SchoolBankAccount::query()
                                ->where('school_id', current_tenant()?->id ?? auth()->user()?->school_id)
                                ->orderByDesc('is_default')
                                ->get()
                                ->mapWithKeys(fn ($account) => [$account->id => trim(implode(' — ', array_filter([
                                    $account->bank_name,
                                    $account->account_name,
                                    $account->account_number,
                                ])))])
                                ->all())
                            ->default(fn () => SchoolBankAccount::query()
                                ->where('school_id', current_tenant()?->id ?? auth()->user()?->school_id)
                                ->where('is_default', true)
                                ->value('id'))
                            ->searchable()
                            ->preload()
                            ->placeholder(__('Defaults to the school bank account'))
                            ->helperText(__('The account this expense is paid out of; used for per-account financial views.')),
                        Forms\Components\Select::make('supplier_id')
                            ->label(__('Supplier / Vendor'))
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder(__('Optional supplier'))
                            ->createOptionAction(fn (Forms\Components\Actions\Action $action) => $action
                                ->modalHeading(__('New Supplier / Vendor'))
                                ->modalSubmitActionLabel(__('Save Supplier')))
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('Company / Supplier Name'))
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('contact_person')
                                    ->label(__('Contact Person'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->label(__('Phone'))
                                    ->tel()
                                    ->maxLength(60),
                                Forms\Components\TextInput::make('email')
                                    ->label(__('Email'))
                                    ->email()
                                    ->maxLength(191),
                                Forms\Components\TextInput::make('website')
                                    ->label(__('Website (optional)'))
                                    ->url()
                                    ->maxLength(191),
                                Forms\Components\TextInput::make('address')
                                    ->label(__('Address'))
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('tax_number')
                                    ->label(__('Tax / VAT Number'))
                                    ->maxLength(60),
                            ]),
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
                Tables\Columns\TextColumn::make('expense_name')->label(__('Expense'))->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('expenseCategory.name')->label(__('Category'))->searchable()->badge(),
                Tables\Columns\TextColumn::make('bankAccount.bank_name')
                    ->label(__('Bank Account'))
                    ->searchable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state ?? __('—')),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label(__('Supplier / Company'))
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) => $state ?? __('—')),
                Tables\Columns\TextColumn::make('supplier')
                    ->label(__('Supplier Contact'))
                    ->state(fn (Expense $record) => $record->supplier)
                    ->formatStateUsing(function (Expense $record): string {
                        $supplier = $record->supplier;

                        if (! $supplier) {
                            return __('—');
                        }

                        $lines = array_filter([
                            $supplier->contact_person ? __('Contact: ').$supplier->contact_person : null,
                            $supplier->phone ? __('Tel: ').$supplier->phone : null,
                            $supplier->email ? $supplier->email : null,
                            $supplier->address ? $supplier->address : null,
                        ]);

                        return $lines !== [] ? implode('  •  ', array_values($lines)) : __('—');
                    })
                    ->icon('heroicon-o-phone')
                    ->toggleable()
                    ->wrap(),
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
                Tables\Filters\SelectFilter::make('expense_category_id')
                    ->label(__('Category'))
                    ->relationship('expenseCategory', 'name'),
                Tables\Filters\SelectFilter::make('bank_account_id')
                    ->label(__('Bank Account'))
                    ->relationship('bankAccount', 'bank_name')
                    ->query(function ($query, array $state) {
                        if (! ($state['value'] ?? null)) {
                            return $query;
                        }

                        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id ?? 1;

                        $query->when(
                            (int) $state['value'],
                            SchoolBankAccount::filterClosure((int) $state['value'], $schoolId, 'expenses.bank_account_id')
                        );
                    }),
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
