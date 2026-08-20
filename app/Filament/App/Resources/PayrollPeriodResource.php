<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Modules\HR\Models\PayrollPeriod;
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
                    ->label(__('Calculate'))
                    ->icon('heroicon-o-cpu-chip')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'calculated']))
                    ->action(function ($record) {
                        app(PayrollCalculationService::class)->executeRun($record);

                        Notification::make()
                            ->title(__('Payroll Calculated'))
                            ->success()
                            ->send();
                    }),

                Action::make('approve')
                    ->label(__('Approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'calculated')
                    ->action(function ($record) {
                        $record->update(['status' => 'approved']);
                        $record->runs()->update(['status' => 'approved']);

                        Notification::make()
                            ->title(__('Payroll Period Locked & Approved'))
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrollPeriods::route('/'),
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
