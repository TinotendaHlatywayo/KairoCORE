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
use Illuminate\Support\Facades\Auth;
use Modules\HR\Models\DisciplinaryCase;
use Modules\HR\Models\Employee;

class DisciplinaryCaseResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('HR & Payroll');
    }

    protected static ?string $model = DisciplinaryCase::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'HR & Payroll';

    protected static ?string $modelLabel = 'Disciplinary Case';

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
                Forms\Components\Textarea::make('offense')->required(),
                Forms\Components\DatePicker::make('incident_date')->required(),
                Forms\Components\Select::make('severity')
                    ->options([
                        'verbal_warning' => __('Verbal Warning'),
                        'written_warning' => __('Written Warning'),
                        'severe_warning' => __('Severe Warning'),
                    ])->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'under_investigation' => __('Under Investigation'),
                        'warning_issued' => __('Warning Issued'),
                        'suspended' => __('Suspended'),
                        'terminated' => __('Terminated'),
                        'exonerated' => __('Exonerated'),
                    ])->required(),
                Forms\Components\Textarea::make('resolution_notes'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.first_name')->label(__('Employee')),
                Tables\Columns\TextColumn::make('incident_date')->date(),
                Tables\Columns\TextColumn::make('severity'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'under_investigation',
                        'success' => 'exonerated',
                        'danger' => 'terminated',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Action::make('conclude')
                    ->label(__('Conclude'))
                    ->icon('heroicon-o-lock-closed')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'under_investigation')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                'warning_issued' => __('Warning Issued'),
                                'exonerated' => __('Exonerated'),
                                'suspended' => __('Suspended'),
                                'terminated' => __('Terminated'),
                            ])->required(),
                        Forms\Components\Textarea::make('resolution_notes')
                            ->label(__('Hearing and Resolution Findings'))
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => $data['status'],
                            'resolution_notes' => $data['resolution_notes'],
                            'action_taken_by_id' => Auth::id(),
                        ]);

                        if (in_array($data['status'], ['suspended', 'terminated'])) {
                            $record->employee->update([
                                'status' => $data['status'],
                                'suspension_reason' => $data['resolution_notes'],
                            ]);
                        }

                        Notification::make()
                            ->title(__('Disciplinary Case Concluded'))
                            ->success()
                            ->send();
                    }),

                Action::make('escalate')
                    ->label(__('Escalate'))
                    ->icon('heroicon-o-arrow-trending-up')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'under_investigation')
                    ->form([
                        Forms\Components\Select::make('severity')
                            ->options([
                                'written_warning' => 'Written Warning',
                                'severe_warning' => 'Severe Warning',
                            ])->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update(['severity' => $data['severity']]);

                        Notification::make()
                            ->title(__('Case Severity Escalated'))
                            ->danger()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDisciplinaryCases::route('/'),
        ];
    }
}

class ListDisciplinaryCases extends ListRecords
{
    protected static string $resource = DisciplinaryCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('New Disciplinary Case')),
        ];
    }
}
