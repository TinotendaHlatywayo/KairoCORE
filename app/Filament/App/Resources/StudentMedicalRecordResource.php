<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Resources\StudentMedicalRecordResource\Pages\CreateStudentMedicalRecord;
use App\Filament\App\Resources\StudentMedicalRecordResource\Pages\EditStudentMedicalRecord;
use App\Filament\App\Resources\StudentMedicalRecordResource\Pages\ListStudentMedicalRecords;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Clinic\Models\StudentMedicalRecord;

class StudentMedicalRecordResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Health & Safety');
    }

    use ModuleAwareActiveNavigation;

    protected static ?string $model = StudentMedicalRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Health & Safety';

    public static function canAccess(): bool
    {
        if (! ModuleVisibilityManager::isVisible('clinic')) {
            return false;
        }

        if (class_exists('\Modules\Admin\Services\PermissionRegistry')) {
            return PermissionRegistry::checkPermission('clinic.view_medical_profiles');
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
                        Forms\Components\Select::make('blood_group')
                            ->options([
                                'A+' => __('A+'), 'A-' => __('A-'), 'B+' => __('B+'), 'B-' => __('B-'),
                                'AB+' => __('AB+'), 'AB-' => __('AB-'), 'O+' => __('O+'), 'O-' => __('O-'),
                            ]),
                        Forms\Components\Textarea::make('allergies')
                            ->placeholder(__('List allergies or none...')),
                        Forms\Components\Textarea::make('chronic_conditions')
                            ->placeholder(__('Asthma, Diabetes...')),
                        Forms\Components\Repeater::make('immunization_history')
                            ->schema([
                                Forms\Components\TextInput::make('vaccine_name')->required(),
                                Forms\Components\DatePicker::make('date_administered')->required(),
                            ])
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.first_name')->searchable(),
                Tables\Columns\TextColumn::make('student.last_name')->searchable(),
                Tables\Columns\TextColumn::make('blood_group'),
                Tables\Columns\TextColumn::make('allergies')->limit(50),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStudentMedicalRecords::route('/'),
            'create' => CreateStudentMedicalRecord::route('/create'),
            'edit' => EditStudentMedicalRecord::route('/{record}/edit'),
        ];
    }
}
