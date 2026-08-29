<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Resources\StudentResource\Pages;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Students\Models\Student;

class StudentResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Students');
    }

    use ModuleAwareActiveNavigation;

    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Students';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        if (! ModuleVisibilityManager::isVisible('students')) {
            return false;
        }

        if (class_exists('\Modules\Admin\Services\PermissionRegistry')) {
            return PermissionRegistry::checkPermission('academic_ops.manage_enrolment');
        }

        return true;
    }

    public static function getNavigationLabel(): string
    {
        return __('Student Directory');
    }

    public static function getModelLabel(): string
    {
        return __('Student');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Students');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make(__('Student Profile'))
                    ->tabs([
                        Tab::make(__('Personal Details'))
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Section::make(__('Student Information'))
                                            ->schema([
                                                Forms\Components\TextInput::make('first_name')
                                                    ->required()
                                                    ->placeholder(__('e.g., John')),
                                                Forms\Components\TextInput::make('last_name')
                                                    ->required()
                                                    ->placeholder(__('e.g., Smith')),
                                                Forms\Components\Select::make('gender')
                                                    ->options(['male' => __('Male'), 'female' => __('Female'), 'other' => __('Other')])
                                                    ->required(),
                                                Forms\Components\DatePicker::make('date_of_birth')
                                                    ->required()
                                                    ->maxDate(now()),
                                                Forms\Components\TextInput::make('national_id')
                                                    ->label(__('National ID'))
                                                    ->placeholder(__('e.g., 63-123456A78')),
                                            ])->columns(2),

                                        Forms\Components\Section::make(__('Photo'))
                                            ->schema([
                                                Forms\Components\FileUpload::make('photo_path')
                                                    ->label(__('Student Photo'))
                                                    ->image()
                                                    ->disk('public')
                                                    ->directory('student-photos')
                                                    ->imageEditor()
                                                    ->helperText(__('Used on ID cards, invoices and reports.')),
                                            ])->columns(1),

                                        Forms\Components\Section::make(__('Guardian & Emergency Contact'))
                                            ->schema([
                                                Forms\Components\TextInput::make('parent_email')
                                                    ->label(__('Parent / Guardian Email'))
                                                    ->email(),
                                                Forms\Components\TextInput::make('emergency_contact_name')
                                                    ->label(__('Emergency Contact Name')),
                                                Forms\Components\TextInput::make('emergency_contact_phone')
                                                    ->label(__('Emergency Contact Phone'))
                                                    ->tel(),
                                            ])->columns(2),

                                        Forms\Components\Section::make(__('Boarding & Health'))
                                            ->schema([
                                                Forms\Components\TextInput::make('house')
                                                    ->placeholder(__('e.g., Chiadzwa, Nyanga, Bvumba')),
                                                Forms\Components\Select::make('boarding_status')
                                                    ->options([
                                                        'day_scholar' => __('Day Scholar'),
                                                        'boarder' => __('Boarder'),
                                                    ])
                                                    ->default('day_scholar'),
                                                Forms\Components\Select::make('blood_group')
                                                    ->options([
                                                        'A+' => __('A+'), 'A-' => __('A-'),
                                                        'B+' => __('B+'), 'B-' => __('B-'),
                                                        'AB+' => __('AB+'), 'AB-' => __('AB-'),
                                                        'O+' => __('O+'), 'O-' => __('O-'),
                                                    ]),
                                                Forms\Components\Textarea::make('medical_notes')
                                                    ->placeholder(__('Allergies, chronic conditions, medication...'))
                                                    ->columnSpanFull(),
                                            ])->columns(2),

                                        Forms\Components\Section::make(__('Admission Details'))
                                            ->columnSpan(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('student_id_number')
                                                    ->label(__('Student ID'))
                                                    ->disabled()
                                                    ->helperText(__('Auto-generated on creation.')),
                                                Forms\Components\TextInput::make('admission_number')
                                                    ->label(__('Admission Number'))
                                                    ->disabled()
                                                    ->helperText(__('Auto-generated on creation.')),
                                                Forms\Components\DatePicker::make('admission_date')
                                                    ->default(now())
                                                    ->required(),
                                                Forms\Components\Select::make('status')
                                                    ->options([
                                                        'active' => __('Active'),
                                                        'inactive' => __('Inactive'),
                                                        'suspended' => __('Suspended'),
                                                        'graduated' => __('Graduated'),
                                                    ])
                                                    ->default('active')
                                                    ->required(),
                                            ])->columns(4),
                                    ]),
                            ]),

                        Tab::make(__('Enrollment'))
                            ->schema([
                                Forms\Components\Section::make(__('Current Enrollment'))
                                    ->description(__('Assign the student to a form / grade and stream for the active academic year.'))
                                    ->schema([
                                        Forms\Components\Select::make('academic_year_id')
                                            ->label(__('Academic Year'))
                                            ->options(AcademicYear::pluck('name', 'id'))
                                            ->required()
                                            ->default(fn () => AcademicYear::where('is_active', true)->first()?->id),
                                        Forms\Components\Select::make('course_id')
                                            ->label(__('Form / Grade (Level)'))
                                            ->options(Course::pluck('name', 'id'))
                                            ->required()
                                            ->live(),
                                        Forms\Components\Select::make('section_id')
                                            ->label(__('Stream / Class'))
                                            ->options(fn (Forms\Get $get) => Section::where('course_id', $get('course_id'))->pluck('name', 'id'))
                                            ->required()
                                            ->live(),
                                        Forms\Components\TextInput::make('roll_number')
                                            ->label(__('Roll Number'))
                                            ->numeric(),
                                    ])->columns(2),
                            ]),

                        Tab::make(__('ID Card'))
                            ->schema([
                                Forms\Components\Section::make(__('Card Status'))
                                    ->schema([
                                        Forms\Components\Select::make('card_status')
                                            ->options([
                                                'pending_issuance' => __('Pending Issuance'),
                                                'active' => __('Active'),
                                                'lost' => __('Lost'),
                                                'stolen' => __('Stolen'),
                                                'reissued' => __('Reissued'),
                                            ])
                                            ->default('pending_issuance'),
                                        Forms\Components\DatePicker::make('card_expiry_date')
                                            ->label(__('Card Expiry Date')),
                                    ])->columns(2),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_path')
                    ->label(__('Photo'))
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => asset(($record->gender === 'female') ? 'images/no_profile_female.jpg' : 'images/no_profile_male.png'))
                    ->hiddenLabel(),
                Tables\Columns\TextColumn::make('student_id_number')
                    ->label(__('Student ID'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->full_name),
                Tables\Columns\TextColumn::make('gender')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'male' => 'info',
                        'female' => 'pink',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('currentEnrollment.course.name')
                    ->label(__('Form')),
                Tables\Columns\TextColumn::make('currentEnrollment.section.name')
                    ->label(__('Stream')),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'suspended' => 'danger',
                        'graduated' => 'purple',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('admission_date')
                    ->label(__('Enrolled'))
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => __('Active'),
                        'inactive' => __('Inactive'),
                        'suspended' => __('Suspended'),
                        'graduated' => __('Graduated'),
                    ]),
                Tables\Filters\SelectFilter::make('gender')
                    ->options([
                        'male' => __('Male'),
                        'female' => __('Female'),
                        'other' => __('Other'),
                    ]),
                Tables\Filters\SelectFilter::make('course_id')
                    ->label(__('Form / Grade'))
                    ->options(Course::pluck('name', 'id'))
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['value'], fn (Builder $q, $courseId) => $q->whereHas('currentEnrollment', fn ($enq) => $enq->where('course_id', $courseId)))),
                Tables\Filters\SelectFilter::make('boarding_status')
                    ->options([
                        'day_scholar' => __('Day Scholar'),
                        'boarder' => __('Boarder'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('print')
                    ->label(__('Print ID'))
                    ->icon('heroicon-o-identification')
                    ->color('success')
                    ->url(fn (Student $record) => route('students.print-cards', [
                        'ids' => $record->id,
                        'layout' => 'pvc',
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('printSelected')
                        ->label(__('Print Selected ID Cards'))
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $ids = $records->pluck('id')->toArray();

                            return redirect()->route('students.print-cards', [
                                'ids' => implode(',', $ids),
                                'layout' => 'pvc',
                            ]);
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->content(fn () => view('filament.app.resources.student.student-cards'))
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['currentEnrollment.course', 'currentEnrollment.section']))
            ->paginated([8, 16, 24, 48, 'all'])
            ->defaultPaginationPageOption(8)
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'view' => Pages\ViewStudent::route('/{record}'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
