<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\LeaveRequestResource\Pages\CreateLeaveRequest;
use App\Filament\App\Resources\LeaveRequestResource\Pages\ListLeaveRequests;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\Admin\Services\PermissionRegistry;
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

    /**
     * May the signed-in person file leave on behalf of another employee?
     *
     * Decided by role rather than by a permission key, because this is a
     * question about whose record is being touched. Answering it with a
     * capability would mean a teacher's role could grow it by accident, and then
     * every teacher would be able to approve somebody else's absence.
     */
    public static function mayFileForOthers(): bool
    {
        return PermissionRegistry::userHasRole(Auth::user(), ['hr']);
    }

    /**
     * The employee record belonging to the signed-in person, if the school has
     * one for them.
     */
    public static function ownEmployeeId(): ?int
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        return Employee::where('school_id', $user->school_id)
            ->where('user_id', $user->id)
            ->value('id');
    }

    /**
     * @return array<int, string>
     */
    protected static function employeeOptions(): array
    {
        $user = Auth::user();

        if (! $user) {
            return [];
        }

        // Everyone else is shown only themselves, so the dropdown can never
        // become a way to enumerate the staff list.
        if (! static::mayFileForOthers()) {
            $own = static::ownEmployeeId();

            return $own === null ? [] : [$own => static::employeeLabel(Employee::find($own))];
        }

        return Employee::where('school_id', $user->school_id)
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(fn ($emp) => [$emp->id => static::employeeLabel($emp)])
            ->all();
    }

    protected static function employeeLabel(?Employee $emp): string
    {
        if (! $emp) {
            return __('Unknown Employee');
        }

        return "{$emp->first_name} {$emp->last_name} ({$emp->employee_number})";
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Who the leave is for.
                //
                // Filing your own leave is something every member of staff does,
                // so it is granted to everyone as self-service. Filing it for
                // somebody else is an HR duty: it reads and decides on another
                // person's record. So the employee is a free choice for the
                // administrator and for HR, and for everyone else it is fixed to
                // their own name — shown rather than hidden, so the form still
                // reads as a complete leave request instead of a field that
                // mysteriously will not move.
                Forms\Components\Select::make('employee_id')
                    ->label(__('Employee'))
                    ->options(fn () => static::employeeOptions())
                    ->default(fn () => Auth::user()?->id ? static::ownEmployeeId() : null)
                    ->searchable(fn () => static::mayFileForOthers())
                    ->preload()
                    ->disabled(fn () => ! static::mayFileForOthers())
                    // The employee is always submitted. When the person cannot
                    // choose, the record still has to say whose leave it is —
                    // dropping the field would file it against nobody, and the
                    // approver would see an unattributed request. Server-side
                    // enforcement lives in CreateLeaveRequest::mutateFormData
                    // BeforeCreate, which overrides whatever arrives.
                    ->dehydrated(true)
                    ->required(),
                Forms\Components\Select::make('leave_type_id')
                    ->label(__('Leave Type'))
                    ->options(fn () => LeaveType::where('school_id', current_tenant()?->id)
                        ->orderBy('name')
                        ->pluck('name', 'id'))
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
            // The list screen offers "New Leave Request", so the page it points
            // at has to exist. Without this the button resolved to
            // `...leave-requests.create`, which is not a registered route, and
            // every user who pressed it got a 500.
            'create' => CreateLeaveRequest::route('/create'),
        ];
    }
}
