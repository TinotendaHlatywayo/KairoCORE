<?php

namespace App\Filament\App\Resources;

use App\Events\ClinicVisitLogged;
use App\Filament\App\Resources\ClinicVisitResource\Pages\CreateClinicVisit;
use App\Filament\App\Resources\ClinicVisitResource\Pages\EditClinicVisit;
use App\Filament\App\Resources\ClinicVisitResource\Pages\ListClinicVisits;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Clinic\Models\ClinicVisit;
use Modules\Clinic\Services\ClinicVisitService;

class ClinicVisitResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Health & Safety');
    }

    protected static ?string $model = ClinicVisit::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Health & Safety';

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        if (! ModuleVisibilityManager::isVisible('clinic')) {
            return false;
        }

        if (class_exists('\Modules\Admin\Services\PermissionRegistry')) {
            return PermissionRegistry::checkPermission('clinic.view_module');
        }

        return true;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('student_id')
                            ->relationship('student', 'first_name')
                            ->searchable()
                            ->required(),
                        Forms\Components\DateTimePicker::make('visit_time')
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('temperature_celsius')
                            ->numeric()
                            ->step(0.1)
                            ->label(__('Body Temperature (°C)')),
                        Forms\Components\TextInput::make('blood_pressure')
                            ->placeholder(__('120/80'))
                            ->maxLength(20),
                        Forms\Components\Textarea::make('symptoms')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('diagnosis'),
                        Forms\Components\Textarea::make('treatment_given'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'checked_in' => __('Checked In'),
                                'treatment' => __('Under Active Treatment'),
                                'admitted' => __('Admitted to Clinic Ward'),
                                'referred' => __('Referred to General Hospital'),
                                'discharged' => __('Discharged'),
                            ])
                            ->required()
                            ->default('checked_in'),
                        Forms\Components\TextInput::make('referral_destination')
                            ->placeholder(__('Hospital/Clinic Name')),
                        Forms\Components\Hidden::make('recorded_by_user_id')
                            ->default(fn () => Auth::id()),
                    ])->columns(2),

                Forms\Components\Section::make('Treatment Prescriptions')
                    ->schema([
                        Forms\Components\Repeater::make('prescriptions')
                            ->relationship('prescriptions')
                            ->schema([
                                Forms\Components\Select::make('inventory_item_id')
                                    ->label(__('Link Stock Product'))
                                    ->relationship('inventoryItem', 'name')
                                    ->nullable()
                                    ->searchable(),
                                Forms\Components\TextInput::make('medicine_name')
                                    ->required(),
                                Forms\Components\TextInput::make('dosage')
                                    ->required()
                                    ->placeholder(__('Paracetamol 500mg')),
                                Forms\Components\TextInput::make('frequency')
                                    ->required()
                                    ->placeholder(__('3 times daily')),
                                Forms\Components\TextInput::make('quantity_prescribed')
                                    ->numeric()
                                    ->required(),
                            ])->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.first_name')->searchable(),
                Tables\Columns\TextColumn::make('student.last_name')->searchable(),
                Tables\Columns\TextColumn::make('visit_time')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('temperature_celsius')->suffix(' °C'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('discharge')
                    ->label(__('Discharge Student'))
                    ->color('success')
                    ->icon('heroicon-o-home')
                    ->visible(fn ($record) => $record->status !== 'discharged')
                    ->form([
                        Forms\Components\Textarea::make('treatment_given')
                            ->label(__('Final Treatment Actions'))
                            ->required(),
                        Forms\Components\Textarea::make('diagnosis')
                            ->label(__('Final Diagnosis'))
                            ->required(),
                    ])
                    ->action(function ($record, $data) {
                        app(ClinicVisitService::class)->discharge(
                            $record->school_id,
                            $record->id,
                            $data['treatment_given'],
                            $data['diagnosis']
                        );

                        event(new ClinicVisitLogged($record));
                        Notification::make()->title(__('Student discharged. Parent notice dispatched.'))->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClinicVisits::route('/'),
            'create' => CreateClinicVisit::route('/create'),
            'edit' => EditClinicVisit::route('/{record}/edit'),
        ];
    }
}
