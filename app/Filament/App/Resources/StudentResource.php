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
                                                 Forms\Components\TextInput::make('phone')
                                                     ->label(__('Contact Number'))
                                                     ->tel()
                                                     ->placeholder(__('e.g., +263 77 123 4567')),
                                                 Forms\Components\Textarea::make('physical_address')
                                                     ->label(__('Physical Address'))
                                                     ->placeholder(__('e.g., 14 Links Lane, Borrowdale, Harare'))
                                                     ->columnSpanFull(),
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
Forms\Components\Toggle::make('apply_waiver')
                                                      ->label(__('Apply Fee Waiver'))
                                                      ->reactive()
                                                      ->helperText(__('Turn on to grant this student a tuition waiver or scholarship.')),
                                                   Forms\Components\Select::make('fee_waiver_id')
                                                       ->label(__('Fee Waiver / Scholarship'))
                                                       ->options(\Modules\Finance\Models\FeeWaiver::pluck('name', 'id'))
                                                       ->searchable()
                                                       ->preload()
                                                       ->nullable()
                                                       ->visible(fn (Forms\Get $get): bool => (bool) $get('apply_waiver'))
                                                       ->required(fn (Forms\Get $get): bool => (bool) $get('apply_waiver'))
                                                       ->helperText(__('Waivers apply to all open (unpaid) invoices and are factored into expected revenue.')),
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
                    ->defaultImageUrl(fn ($record) => asset(($record->gender === 'female') ? 'images/no_profile_female.jpg' : 'images/no_profile_male.png')),
                Tables\Columns\TextColumn::make('student_id_number')
                    ->label(__('Student ID'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label(__('Name'))
                    ->searchable(query: function (Builder $query, string $search) {
                        return $query->where(function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%")
                              ->orWhere('admission_number', 'like', "%{$search}%")
                              ->orWhere('student_id_number', 'like', "%{$search}%");
                        });
                    })
                    ->sortable()
                    ->formatStateUsing(fn ($record) => $record->full_name),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable(query: fn (Builder $query, string $search) => $query->where('parent_email', 'like', "%{$search}%")->orWhereHas('application', fn ($q) => $q->where('parent_email', 'like', "%{$search}%")))
                    ->formatStateUsing(fn ($record) => $record->email ?? '—'),
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

                Tables\Actions\Action::make('approvePhoto')
                    ->label(__('Approve Photo'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('Approve Student Photo'))
                    ->modalDescription(__('Approving this photo will lock it and prevent the student from replacing or uploading new photos in their portal.'))
                    ->action(function (Student $record) {
                        $record->update([
                            'photo_approved_at' => now(),
                            'photo_approved_by' => auth()->id(),
                            'photo_rejected_at' => null,
                            'photo_rejected_reason' => null,
                        ]);
                        Notification::make()
                            ->title(__('Photo Approved & Locked'))
                            ->body(__('The student photo has been approved and locked successfully.'))
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Student $record) => filled($record->photo_path) && ! $record->photo_approved_at),

                Tables\Actions\Action::make('removeStudentPhoto')
                    ->label(__('Remove / Replace Photo'))
                    ->icon('heroicon-o-photo')
                    ->color('danger')
                    ->visible(fn (Student $record) => filled($record->photo_path))
                    ->requiresConfirmation()
                    ->modalHeading(__('Remove Profile Photo'))
                    ->modalDescription(__('The photo will be removed and the default placeholder will be used. The student will be notified and asked to upload a new passport-style photo. You can add a note explaining why it was removed.'))
                    ->modalSubmitActionLabel(__('Remove Photo'))
                    ->form([
                                                Forms\Components\Textarea::make('reason')
                                                    ->label(__('Reason'))
                                                    ->placeholder(__('e.g. Photo was blurry / not a clear single face'))
                                                    ->rows(3)
                                                    ->maxLength(500)
                                                    ->required(),
                    ])
                    ->action(function (array $data, Student $record) {
                        app(\App\Services\ProfilePhotoService::class)->rejectPhoto(
                            $record,
                            $data['reason'] ?? null,
                            'photo_path'
                        );

                        if ($record->user_id) {
                            $user = \App\Models\User::find($record->user_id);
                            if ($user) {
                                $user->notify(new \App\Notifications\ProfilePhotoRejectedNotification(
                                    subject: __('Your profile photo was removed'),
                                    reason: $data['reason'] ?? null,
                                    url: \App\Filament\Student\Pages\StudentProfile::getUrl(panel: 'student'),
                                ));
                            }
                        }

                        Notification::make()
                            ->title(__('Photo Removed'))
                            ->body(__('The profile photo has been removed and the user was notified.'))
                            ->success()
                            ->send();
                    }),
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
                    Tables\Actions\BulkAction::make('downloadPngs')
                        ->label(__('Download ID Cards as PNG/ZIP'))
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('primary')
                        ->action(function (Collection $records) {
                            $ids = $records->pluck('id')->toArray();

                            return redirect()->route('students.download-pngs', [
                                'ids' => implode(',', $ids),
                            ]);
                        }),
                    Tables\Actions\BulkAction::make('downloadFinancialHistory')
                        ->label(__('Download Financial History'))
                        ->icon('heroicon-o-book-open')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('scope')
                                ->label(__('History Period'))
                                ->options([
                                    'term' => __('Current Term (This Year)'),
                                    'full' => __('Whole Financial History (from Enrolment)'),
                                ])
                                ->default('term')
                                ->required(),
                            Forms\Components\Select::make('format')
                                ->label(__('Output Format'))
                                ->options([
                                    'pdf' => __('Single Combined PDF'),
                                    'zip' => __('ZIP Archive (Individual PDFs)'),
                                    'csv' => __('CSV'),
                                ])
                                ->default('pdf')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $scope = $data['scope'] ?? 'full';
                            $format = $data['format'] ?? 'pdf';
                            $mode = $format === 'zip' ? 'zip' : 'combined';

                            return redirect()->route('finance.students.history.bulk', [
                                'ids' => $records->pluck('id')->join(','),
                                'scope' => $scope,
                                'format' => $format,
                                'mode' => $mode,
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
