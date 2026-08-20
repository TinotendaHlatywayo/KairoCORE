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
                    ])->required(),
                Forms\Components\TextInput::make('principal_amount')
                    ->numeric()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => $set('balance_remaining', $state)),
                Forms\Components\TextInput::make('balance_remaining')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('monthly_deduction')
                    ->numeric()
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
                Tables\Columns\TextColumn::make('monthly_deduction')->money('USD'),
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
                        ]);

                        Notification::make()
                            ->title(__('Loan Approved and Disbursed'))
                            ->success()
                            ->send();
                    }),

                Action::make('repay')
                    ->label(__('Pay'))
                    ->color('primary')
                    ->icon('heroicon-o-currency-dollar')
                    ->visible(fn ($record) => $record->status === 'active')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label(__('Repayment Amount (USD)'))
                            ->numeric()
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $newBalance = max(0, $record->balance_remaining - $data['amount']);

                        $record->update([
                            'balance_remaining' => $newBalance,
                            'status' => $newBalance <= 0 ? 'settled' : 'active',
                        ]);

                        Notification::make()
                            ->title(__('Manual Repayment Recorded'))
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
