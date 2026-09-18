<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Services\Csv\StaffLoanCsvService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\HR\Models\Employee;
use Modules\HR\Models\StaffLoan;

class StaffLoanResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('HR & Payroll');
    }

    protected static ?string $model = StaffLoan::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'HR & Payroll';

    protected static ?string $modelLabel = 'Staff Loan';

    public static function getModelLabel(): string
    {
        return __(static::$modelLabel);
    }

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        $applyTotals = function (Forms\Get $get, Forms\Set $set) {
            $principal = (float) $get('principal_amount');

            if ($get('repayment_method') === 'reducing_balance') {
                // Interest accrues on the remaining balance each payroll period.
                $set('total_repayable', round($principal, 2));
                $set('balance_remaining', round($principal, 2));

                return;
            }

            $rate = (float) $get('interest_rate');
            $type = $get('interest_type');
            $total = $type === 'fixed_amount' ? ($principal + $rate) : ($principal + ($principal * ($rate / 100)));
            $set('total_repayable', round($total, 2));
            $set('balance_remaining', round($total, 2));
        };

        return $form
            ->schema([
                Forms\Components\Select::make('employee_id')
                    ->label(__('Employee Name'))
                    ->options(fn () => Employee::all()->mapWithKeys(function ($emp) {
                        return [$emp->id => "{$emp->first_name} {$emp->last_name} ({$emp->employee_number})"];
                    }))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('loan_type')
                    ->options([
                        'salary_advance' => __('Salary Advance'),
                        'emergency' => __('Emergency Loan'),
                        'device_loan' => __('Device Loan'),
                        'other' => __('Other (Specify below)'),
                    ])
                    ->reactive()
                    ->required(),
                Forms\Components\TextInput::make('loan_type_other')
                    ->label(__('Specify Other Loan Type'))
                    ->required()
                    ->visible(fn (Forms\Get $get) => $get('loan_type') === 'other'),
                Forms\Components\Select::make('bank_account_id')
                    ->label(__('Bank Account'))
                    ->helperText(__('Loan is funded out of this account. Repayments flow back into it.'))
                    ->options(fn () => SchoolBankAccount::where('school_id', Auth::user()->school_id)->pluck('bank_name', 'id'))
                    ->searchable()
                    ->preload()
                    ->default(fn () => SchoolBankAccount::where('school_id', Auth::user()->school_id)->where('is_default', true)->value('id'))
                    ->required(),
                Forms\Components\TextInput::make('principal_amount')
                    ->numeric()
                    ->prefix('$')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, $state) => $applyTotals($get, $set)),
                Forms\Components\Select::make('repayment_method')
                    ->label(__('Interest System'))
                    ->options([
                        'fixed' => __('Fixed Total Repayable'),
                        'reducing_balance' => __('Reducing Balance (interest on remaining balance)'),
                    ])
                    ->helperText(__('Fixed: total payable is set once (e.g. $100 + 5%). Reducing balance: interest applies to what is still owed each period, so paying off early costs less.'))
                    ->default('fixed')
                    ->reactive()
                    ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, $state) => $applyTotals($get, $set)),
                Forms\Components\TextInput::make('interest_rate')
                    ->numeric()
                    ->default(0)
                    ->reactive()
                    ->visible(fn (Forms\Get $get) => $get('repayment_method') !== 'reducing_balance')
                    ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, $state) => $applyTotals($get, $set)),
                Forms\Components\Select::make('interest_type')
                    ->options([
                        'percentage' => __('Percentage (%)'),
                        'fixed_amount' => __('Fixed Amount ($)'),
                    ])
                    ->default('percentage')
                    ->reactive()
                    ->visible(fn (Forms\Get $get) => $get('repayment_method') !== 'reducing_balance')
                    ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set, $state) => $applyTotals($get, $set)),
                Forms\Components\TextInput::make('total_repayable')
                    ->label(__('Total Repayable'))
                    ->numeric()
                    ->prefix('$')
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                Forms\Components\TextInput::make('balance_remaining')
                    ->label(__('Balance Remaining'))
                    ->numeric()
                    ->prefix('$')
                    ->required(),
                Forms\Components\Select::make('monthly_deduction_type')
                    ->label(__('Monthly Deduction Basis'))
                    ->options([
                        'fixed' => __('Fixed Amount ($)'),
                        'percentage' => __('Percentage of Total Payable (%)'),
                    ])
                    ->default('fixed')
                    ->reactive(),
                Forms\Components\TextInput::make('monthly_deduction')
                    ->label(__('Monthly Deduction Amount'))
                    ->numeric()
                    ->prefix(fn (Forms\Get $get) => $get('monthly_deduction_type') === 'percentage' ? '%' : '$')
                    ->helperText(fn (Forms\Get $get) => $get('monthly_deduction_type') === 'percentage'
                        ? __('A percentage of the total payable is recovered from each payslip.')
                        : __('A fixed dollar amount is recovered from each payslip.'))
                    ->reactive()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.first_name')->label(__('Staff Member')),
                Tables\Columns\TextColumn::make('loan_type')->label(__('Type')),
                Tables\Columns\TextColumn::make('principal_amount')->money('USD'),
                Tables\Columns\TextColumn::make('balance_remaining')->money('USD'),
                Tables\Columns\TextColumn::make('monthly_deduction')
                    ->label(__('Monthly Deduction'))
                    ->formatStateUsing(fn ($record, $state) => $record->monthly_deduction_type === 'percentage'
                        ? number_format((float) $state, 2).'%'
                        : '$'.number_format((float) $state, 2))
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('repayment_method')
                    ->label(__('Interest'))
                    ->badge()
                    ->color(fn ($state) => $state === 'reducing_balance' ? 'warning' : 'gray'),
                Tables\Columns\TextColumn::make('bankAccount.bank_name')->label(__('Bank Account')),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                        'secondary' => 'settled',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label(__('Disburse'))
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'active',
                            'approved_by_id' => Auth::id(),
                            'funded_at' => now(),
                        ]);

                        // Money leaves the school: debit the loan account.
                        $accountId = $record->bank_account_id
                            ?? SchoolBankAccount::where('school_id', $record->school_id)->where('is_default', true)->value('id');

                        if ($accountId && (float) $record->principal_amount > 0) {
                            SchoolBankAccount::where('school_id', $record->school_id)
                                ->where('id', $accountId)
                                ->decrement('balance', (float) $record->principal_amount);
                        }

                        Notification::make()
                            ->title(__('Loan Approved and Disbursed'))
                            ->body(__('The loan account has been debited with the principal amount.'))
                            ->success()
                            ->send();
                    }),

                Action::make('repay')
                    ->label(__('Pay'))
                    ->color('primary')
                    ->icon('heroicon-o-currency-dollar')
                    ->visible(fn ($record) => $record->status === 'active')
                    ->form([
                        Forms\Components\Placeholder::make('current_balance')
                            ->label(__('Balance Remaining'))
                            ->content(fn ($record) => '$'.number_format((float) $record->balance_remaining, 2)),
                        Forms\Components\TextInput::make('amount')
                            ->label(__('Payment Amount Today (USD)'))
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(fn ($record) => (float) $record->balance_remaining)
                            ->helperText(__('The remaining balance is recalculated automatically after this payment.')),
                    ])
                    ->action(function ($record, array $data) {
                        $paid = (float) $data['amount'];
                        $newBalance = max(0, (float) $record->balance_remaining - $paid);

                        $record->update([
                            'balance_remaining' => $newBalance,
                            'status' => $newBalance <= 0 ? 'settled' : 'active',
                        ]);

                        // Money returns into the loan's bank account.
                        $accountId = $record->bank_account_id
                            ?? SchoolBankAccount::where('school_id', $record->school_id)->where('is_default', true)->value('id');

                        if ($accountId && $paid > 0) {
                            SchoolBankAccount::where('school_id', $record->school_id)
                                ->where('id', $accountId)
                                ->increment('balance', $paid);
                        }

                        Notification::make()
                            ->title(__('Manual Repayment Recorded'))
                            ->body(__('Balance remaining: $').number_format($newBalance, 2))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStaffLoans::route('/'),
        ];
    }
}

class ListStaffLoans extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = StaffLoanResource::class;

    protected static function csvService(): string
    {
        return StaffLoanCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('New Staff Loan')),
            ...$this->csvBulkActions(),
        ];
    }
}
