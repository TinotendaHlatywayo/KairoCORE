<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Services\Csv\LeaveRequestCsvService;
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
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\LeaveType;

class LeaveRequestResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('HR & Payroll');
    }

    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'HR & Payroll';

    protected static ?string $modelLabel = 'Leave Request';

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
                    ->label(__('Employee'))
                    ->options(Employee::all()->mapWithKeys(fn ($emp) => [$emp->id => "{$emp->first_name} {$emp->last_name} ({$emp->employee_number})"]))
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('leave_type_id')
                    ->label(__('Leave Type'))
                    ->options(LeaveType::all()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('max_days_per_year')
                            ->numeric()
                            ->default(21)
                            ->required(),
                        Forms\Components\Textarea::make('description'),
                    ])
                    ->createOptionUsing(function (array $data): int {
                        return LeaveType::create([
                            'school_id' => current_tenant()?->id ?? 1,
                            'name' => $data['name'],
                            'max_days_per_year' => $data['max_days_per_year'] ?? 21,
                            'description' => $data['description'] ?? null,
                        ])->id;
                    }),
                Forms\Components\DatePicker::make('start_date')->required(),
                Forms\Components\DatePicker::make('end_date')->required(),
                Forms\Components\Textarea::make('reason')->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee')
                    ->label(__('Employee'))
                    ->getStateUsing(fn ($record) => optional($record->employee)->first_name ? "{$record->employee->first_name} {$record->employee->last_name} ({$record->employee->employee_number})" : '-')
                    ->searchable(['first_name', 'last_name', 'employee_number']),
                Tables\Columns\TextColumn::make('leaveType.name')->label(__('Type')),
                Tables\Columns\TextColumn::make('start_date')->date(),
                Tables\Columns\TextColumn::make('end_date')->date(),
                Tables\Columns\TextColumn::make('total_days'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label(__('Approve'))
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('hr_remarks')
                            ->label(__('Appraisal/HR Remarks'))
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'approved',
                            'hr_remarks' => $data['hr_remarks'],
                            'approved_by_id' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title(__('Leave Approved Successfully'))
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label(__('Reject'))
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('hr_remarks')
                            ->label(__('Rejection Reason'))
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'hr_remarks' => $data['hr_remarks'],
                            'approved_by_id' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title(__('Leave Request Rejected'))
                            ->danger()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeaveRequests::route('/'),
        ];
    }
}

class ListLeaveRequests extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = LeaveRequestResource::class;

    protected static function csvService(): string
    {
        return LeaveRequestCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('New Leave Request')),
            ...$this->csvBulkActions(),
        ];
    }
}
