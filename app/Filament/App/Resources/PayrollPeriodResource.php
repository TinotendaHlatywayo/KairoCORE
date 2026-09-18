<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\PayrollPeriodResource\Pages;
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
use Modules\HR\Models\PayrollPeriod;
use Modules\HR\Models\SalaryGrade;
use Modules\HR\Services\PayrollCalculationService;

class PayrollPeriodResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('HR & Payroll');
    }

    protected static ?string $model = PayrollPeriod::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'HR & Payroll';

    protected static ?string $modelLabel = 'Payroll Period';

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
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\DatePicker::make('start_date')->required(),
                Forms\Components\DatePicker::make('end_date')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('start_date')->date(),
                Tables\Columns\TextColumn::make('end_date')->date(),
                Tables\Columns\TextColumn::make('salary_expense')
                    ->label(__('Salary Expense'))
                    ->money('USD')
                    ->state(fn (PayrollPeriod $record) => app(PayrollCalculationService::class)->periodSalaryExpense($record)),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'primary' => 'calculated',
                        'warning' => 'approved',
                        'success' => 'released',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Action::make('calculate')
                    ->label(__('Calculate & Populate Salaries'))
                    ->icon('heroicon-o-cpu-chip')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('current_grade_id')
                            ->label(__('Salary Grade'))
                            ->options(SalaryGrade::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->placeholder(__('— All Salary Grades —')),
                        Forms\Components\Select::make('department')
                            ->label(__('Department'))
                            ->options(Employee::whereNotNull('department')->distinct()->pluck('department', 'department'))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->placeholder(__('— All Departments —')),
                        Forms\Components\Select::make('employment_type')
                            ->label(__('Employment Type'))
                            ->options([
                                'full_time' => __('Full Time'),
                                'part_time' => __('Part Time'),
                                'contract' => __('Contract'),
                            ])
                            ->nullable()
                            ->placeholder(__('— All Employment Types —')),
                        Forms\Components\Select::make('designation')
                            ->label(__('Designation / Job Title'))
                            ->options(Employee::whereNotNull('designation')->distinct()->pluck('designation', 'designation'))
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->placeholder(__('— All Designations —')),
                        Forms\Components\Select::make('gender')
                            ->label(__('Gender'))
                            ->options([
                                'male' => __('Male'),
                                'female' => __('Female'),
                            ])
                            ->nullable()
                            ->placeholder(__('— All Genders —')),
                    ])
                    ->action(function ($record, array $data) {
                        app(PayrollCalculationService::class)->executeRun($record, array_filter($data));

                        Notification::make()
                            ->title(__('Salaries Populated & Calculated Successfully'))
                            ->success()
                            ->send();
                    }),

                Action::make('approve')
                    ->label(__('Approve & Deduct Salaries'))
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'calculated')
                    ->form([
                        Forms\Components\Select::make('bank_account_id')
                            ->label(__('Bank Account to Deduct Salaries From'))
                            ->helperText(__('Default account is preselected. Net pay plus tax-type deductions leave this account.'))
                            ->options(fn () => SchoolBankAccount::where('school_id', Auth::user()->school_id)->pluck('bank_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->default(fn () => SchoolBankAccount::where('school_id', Auth::user()->school_id)->where('is_default', true)->value('id'))
                            ->required(),
                    ])
                    ->modalHeading(__('Approve Payroll Period'))
                    ->modalDescription(fn (PayrollPeriod $record) => __('Net pay $').number_format((float) app(PayrollCalculationService::class)->periodSalaryExpense($record), 2).__(' will be deducted from the selected bank account and recorded as a salary expense.'))
                    ->action(function ($record, array $data) {
                        $result = app(PayrollCalculationService::class)->approvePeriod($record, $data['bank_account_id']);

                        if (! empty($result['skipped'])) {
                            Notification::make()
                                ->title(__('No Bank Account Available'))
                                ->body(__('Salaries were not deducted — add a bank account first.'))
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title(__('Payroll Period Locked & Approved'))
                            ->body(__('Deducted $').number_format($result['outflow'], 2).__(' from ').($result['bank_account'] ?? ''))
                            ->success()
                            ->send();
                    }),

                // UPGRADED STAGE 3 ACTION: RELEASE & PAY
                Action::make('release')
                    ->label(__('Release & Pay'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'approved')
                    ->action(function ($record) {
                        // Call the corrected release run method
                        app(PayrollCalculationService::class)->releaseRun($record);

                        Notification::make()
                            ->title(__('Payslips Released & Loan Amortizations Processed'))
                            ->success()
                            ->send();
                    }),

                Action::make('breakdown')
                    ->label(__('View Ledger & Breakdown'))
                    ->icon('heroicon-o-table-cells')
                    ->color('info')
                    ->url(fn (PayrollPeriod $record): string => static::getUrl('breakdown', ['record' => $record])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrollPeriods::route('/'),
            'breakdown' => Pages\PayrollPeriodBreakdownPage::route('/{record}/breakdown'),
        ];
    }
}

class ListPayrollPeriods extends ListRecords
{
    protected static string $resource = PayrollPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('New Payroll Period')),
        ];
    }
}
