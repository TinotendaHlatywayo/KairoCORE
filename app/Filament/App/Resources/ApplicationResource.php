<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Resources\ApplicationResource\Pages;
use App\Models\User;
use App\Services\AdmissionNotificationService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\ApplicationDocument;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;
use Modules\Students\Models\StudentDocument;

class ApplicationResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Admissions');
    }

    use ModuleAwareActiveNavigation;

    protected static ?string $model = Application::class;

    public static function canAccess(): bool
    {
        return true;
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Admissions';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('Online Admissions');
    }

    public static function getModelLabel(): string
    {
        return __('Application');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Applications');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make(__('Admissions Workflow'))
                    ->tabs([
                        Tab::make(__('Application'))
                            ->label(__('1. Application'))
                            ->schema([
                                Forms\Components\Section::make(__('Student Information'))
                                    ->schema([
                                        Forms\Components\TextInput::make('application_number')
                                            ->disabled()
                                            ->label(__('Application Number')),
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
                                            ->required(),
                                        Forms\Components\Select::make('course_id')
                                            ->label(__('Applying Level / Form'))
                                            ->options(fn () => Course::pluck('name', 'id'))
                                            ->required()
                                            ->searchable(),
                                    ])->columns(3),

                                Forms\Components\Section::make(__('Guardian Details'))
                                    ->schema([
                                        Forms\Components\TextInput::make('parent_name')
                                            ->required()
                                            ->label(__('Parent/Guardian Name')),
                                        Forms\Components\TextInput::make('parent_email')
                                            ->email()
                                            ->required()
                                            ->label(__('Parent/Guardian Email')),
                                        Forms\Components\TextInput::make('parent_phone')
                                            ->required()
                                            ->label(__('Parent/Guardian Phone')),
                                        Forms\Components\TextInput::make('parent_relationship')
                                            ->placeholder(__('e.g., Mother, Father, Guardian'))
                                            ->label(__('Relationship')),
                                    ])->columns(2),
                            ]),

                        Tab::make(__('Review'))
                            ->label(__('2. Review & Verification'))
                            ->schema([
                                Forms\Components\Section::make(__('Documents'))
                                    ->schema([
                                        Forms\Components\Repeater::make('documents')
                                            ->relationship('documents')
                                            ->label(__('Supporting Documents'))
                                            ->helperText(__('Documents submitted with the online application (birth certificate, certificates, result slips). Upload any missing files here.'))
                                            ->schema([
                                                Forms\Components\TextInput::make('title')
                                                    ->label(__('Document Title / Name'))
                                                    ->placeholder(__('e.g., Birth Certificate, Report Card'))
                                                    ->maxLength(255)
                                                    ->columnSpan(6),
                                                Forms\Components\Select::make('document_type')
                                                    ->label(__('Document Type'))
                                                    ->options(ApplicationDocument::$documentTypes)
                                                    ->searchable()
                                                    ->columnSpan(3),
                                                Forms\Components\FileUpload::make('file_path')
                                                    ->label(__('Document File'))
                                                    ->disk('public')
                                                    ->directory('application-docs')
                                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                                    ->maxSize(5120)
                                                    ->required()
                                                    ->columnSpan(3),
                                            ])
                                            ->columns(6)
                                            ->collapsible()
                                            ->defaultItems(0)
                                            ->addActionLabel(__('Add Document')),
                                        Forms\Components\Checkbox::make('documents_verified')
                                            ->label(__('Documents Verified'))
                                            ->helperText(__('Mark when all documents have been checked.')),
                                    ]),

                                Forms\Components\Section::make(__('Interview'))
                                    ->schema([
                                        Forms\Components\DatePicker::make('interview_date')
                                            ->label(__('Interview Date')),
                                        Forms\Components\Textarea::make('interview_notes')
                                            ->label(__('Interview Notes')),
                                        Forms\Components\Select::make('interview_status')
                                            ->label(__('Interview Outcome'))
                                            ->options([
                                                'passed' => __('Passed'),
                                                'failed' => __('Failed'),
                                                'pending' => __('Pending'),
                                            ])
                                            ->default('pending'),
                                    ]),
                            ]),

                        Tab::make(__('Approval'))
                            ->label(__('3. Approval'))
                            ->schema([
                                Forms\Components\Section::make(__('Decision'))
                                    ->schema([
                                        Forms\Components\Select::make('status')
                                            ->options([
                                                'pending' => __('Pending Review'),
                                                'verified' => __('Verified'),
                                                'confirmed' => __('Confirmed'),
                                                'rejected' => __('Rejected'),
                                                'enrolled' => __('Enrolled'),
                                                'waiting_list' => __('Waiting List'),
                                            ])
                                            ->required(),
                                        Forms\Components\Textarea::make('decision_notes')
                                            ->label(__('Decision Notes')),
                                    ]),
                            ]),

                        Tab::make(__('Enrollment'))
                            ->label(__('4. Enrollment'))
                            ->schema([
                                Forms\Components\Section::make(__('Enrollment Details'))
                                    ->schema([
                                        Forms\Components\Select::make('academic_year_id')
                                            ->label(__('Active Academic Year'))
                                            ->options(AcademicYear::pluck('name', 'id'))
                                            ->required()
                                            ->default(fn () => AcademicYear::where('is_active', true)->first()?->id),

                                        Forms\Components\Select::make('course_id')
                                            ->label(__('Form / Grade (Level)'))
                                            ->options(Course::pluck('name', 'id'))
                                            ->required()
                                            ->live(),

                                        Forms\Components\Select::make('enrollment.section_id')
                                            ->label(__('Class'))
                                            ->options(fn (Forms\Get $get) => Section::where('course_id', $get('course_id'))->pluck('name', 'id'))
                                            ->required(),

                                        Forms\Components\TextInput::make('enrollment.admission_number')
                                            ->label(__('Assign Admission Number')),
                                        // ->required(),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('application_number')
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('first_name')
                    ->searchable()
                    ->description(fn ($record) => $record->last_name),

                Tables\Columns\TextColumn::make('course.name')
                    ->label(term('label.course', 'Form')),

                Tables\Columns\TextColumn::make('parent_phone')
                    ->label(__('Phone'))
                    ->icon('heroicon-o-phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('date_of_birth')
                    ->label(__('Age'))
                    ->date()
                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->age.' '.__('years') : '-'),

                Tables\Columns\BadgeColumn::make('status')
                    ->color(fn (string $state): string => match ($state) {
                        'enrolled' => 'success',
                        'pending' => 'warning',
                        'verified' => 'info',
                        'confirmed' => 'blue',
                        'rejected' => 'danger',
                        'waiting_list' => 'purple',
                        default => 'gray',
                    }),

                // FIXED: Changed from TextColumn to IconColumn
                Tables\Columns\IconColumn::make('documents_verified')
                    ->label(__('Docs'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Applied'))
                    ->date(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => __('Pending Review'),
                        'verified' => __('Verified'),
                        'confirmed' => __('Confirmed'),
                        'enrolled' => __('Enrolled'),
                        'rejected' => __('Rejected'),
                        'waiting_list' => __('Waiting List'),
                    ]),
                Tables\Filters\Filter::make('unverified_docs')
                    ->label(__('Documents Not Verified'))
                    ->query(fn ($query) => $query->where('documents_verified', false)),
                Tables\Filters\Filter::make('applied_this_month')
                    ->label(__('Applied This Month'))
                    ->query(fn ($query) => $query->whereMonth('created_at', now()->month)),
                Tables\Filters\Filter::make('applied_between')
                    ->label(__('Applied Between Dates'))
                    ->form([
                        Forms\Components\DatePicker::make('applied_from')
                            ->label(__('From')),
                        Forms\Components\DatePicker::make('applied_until')
                            ->label(__('Until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['applied_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['applied_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('viewDocuments')
                    ->label(__('Documents'))
                    ->icon('heroicon-o-paper-clip')
                    ->color('success')
                    ->modalHeading(__('Application Documents'))
                    ->modalDescription(fn (Application $record) => __('Supporting documents for').' '.$record->full_name)
                    ->modalContent(fn (Application $record) => view('filament.app.resources.application-documents-modal', [
                        'application' => $record,
                        'documents' => $record->documents,
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Close')),
                Tables\Actions\Action::make('enroll')
                    ->label(__('Enroll Student'))
                    ->color('success')
                    ->icon('heroicon-o-user-plus')
                    ->visible(fn (Application $record): bool => $record->status !== 'enrolled')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('academic_year_id')
                            ->label(__('Active Academic Year'))
                            ->options(AcademicYear::pluck('name', 'id'))
                            ->required(),
                        Forms\Components\Select::make('course_id')
                            ->label(__('Form / Grade (Level)'))
                            ->options(Course::pluck('name', 'id'))
                            ->default(fn (Application $record) => $record->course_id)
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('section_id')
                            ->label(__('Class'))
                            ->options(fn (Forms\Get $get) => Section::where('course_id', $get('course_id'))->pluck('name', 'id'))
                            ->required(),
                        Forms\Components\TextInput::make('admission_number')
                            ->label(__('Assign Admission Number'))
                            ->required()
                            ->default(fn () => date('Y').'/'.rand(100, 999)),
                    ])
                    ->action(function (Application $record, array $data) {
                        $record->update([
                            'status' => 'enrolled',
                            'course_id' => $data['course_id'],
                        ]);

                        $studentUser = static::resolveOrCreateStudentUser($record);

                        $student = Student::create([
                            'school_id' => $record->school_id,
                            'user_id' => $studentUser->id,
                            'application_id' => $record->id,
                            'admission_number' => $data['admission_number'],
                            'first_name' => $record->first_name,
                            'last_name' => $record->last_name,
                            'gender' => $record->gender,
                            'date_of_birth' => $record->date_of_birth,
                            'admission_date' => now(),
                            'status' => 'active',
                            'photo_path' => $record->photo_path,
                            'emergency_contact_name' => $record->parent_name,
                            'emergency_contact_phone' => $record->parent_phone,
                        ]);

                        Enrollment::create([
                            'school_id' => $record->school_id,
                            'student_id' => $student->id,
                            'academic_year_id' => $data['academic_year_id'],
                            'course_id' => $data['course_id'],
                            'section_id' => $data['section_id'],
                        ]);

                        // Copy submitted application documents onto the student record.
                        static::copyApplicationDocuments($record, $student);

                        // Send the admission confirmation email to the registered address.
                        app(AdmissionNotificationService::class)->send($student, $record->parent_email, $record->school_id);

                        Notification::make()
                            ->title(__('Student Enrolled Successfully!'))
                            ->body(__('Portal account created for')." {$studentUser->name}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulkEnroll')
                        ->label(__('Bulk Enroll'))
                        ->icon('heroicon-o-user-plus')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Select::make('academic_year_id')
                                ->label(__('Active Academic Year'))
                                ->options(AcademicYear::pluck('name', 'id'))
                                ->required(),
                            Forms\Components\Select::make('course_id')
                                ->label(__('Default Form / Grade (Level)'))
                                ->options(Course::pluck('name', 'id'))
                                ->required()
                                ->live(),
                            Forms\Components\Select::make('section_id')
                                ->label(__('Default Class'))
                                ->options(fn (Forms\Get $get) => Section::where('course_id', $get('course_id'))->pluck('name', 'id'))
                                ->required(),
                        ])
                        ->action(function ($records, $data) {
                            foreach ($records as $record) {
                                if ($record->status === 'enrolled') {
                                    continue;
                                }

                                $studentUser = static::resolveOrCreateStudentUser($record);

                                $student = Student::create([
                                    'school_id' => $record->school_id,
                                    'user_id' => $studentUser->id,
                                    'application_id' => $record->id,
                                    'admission_number' => date('Y').'/'.rand(100, 999),
                                    'first_name' => $record->first_name,
                                    'last_name' => $record->last_name,
                                    'gender' => $record->gender,
                                    'date_of_birth' => $record->date_of_birth,
                                    'admission_date' => now(),
                                    'status' => 'active',
                                    'photo_path' => $record->photo_path,
                                    'emergency_contact_name' => $record->parent_name,
                                    'emergency_contact_phone' => $record->parent_phone,
                                ]);

                                Enrollment::create([
                                    'school_id' => $record->school_id,
                                    'student_id' => $student->id,
                                    'academic_year_id' => $data['academic_year_id'],
                                    'course_id' => $data['course_id'] ?? $record->course_id,
                                    'section_id' => $data['section_id'],
                                ]);

                                // Copy submitted application documents onto the student record.
                                static::copyApplicationDocuments($record, $student);

                                // Send the admission confirmation email to the registered address.
                                app(AdmissionNotificationService::class)->send($student, $record->parent_email, $record->school_id);

                                $record->update([
                                    'status' => 'enrolled',
                                    'course_id' => $data['course_id'] ?? $record->course_id,
                                ]);
                            }

                            Notification::make()
                                ->title(__('Bulk Enrollment Complete'))
                                ->body(count($records).' '.__('students enrolled successfully.'))
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->content(fn () => view('filament.app.resources.application.application-cards'))
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['course']))
            ->paginated([8, 16, 24, 48, 'all'])
            ->defaultPaginationPageOption(8)
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * Finds an existing user by email or creates a new portal account for
     * the student. The account starts locked — an activation email is sent
     * so the student can set their own password securely.
     */
    protected static function resolveOrCreateStudentUser(Application $record): User
    {
        $school = app('current_tenant');

        $base = strtolower($record->first_name.'.'.$record->last_name.'@'.($school->subdomain ?? 'school').'.schoolcore.test');

        $email = $base;
        $suffix = 1;
        while (User::withoutTenantScope()->where('email', $email)->exists()) {
            $email = preg_replace('/@/', ($suffix++).'@', $base, 1);
        }

        $user = User::create([
            'school_id' => $record->school_id,
            'name' => "{$record->first_name} {$record->last_name}",
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
            'account_status' => User::STATUS_PENDING,
        ]);

        return $user;
    }

    /**
     * Copy supporting documents from an online application onto the
     * newly enrolled student's record (files are copied on disk).
     */
    protected static function copyApplicationDocuments(Application $application, Student $student): void
    {
        foreach ($application->documents as $document) {
            if (! $document->file_path || ! Storage::disk('public')->exists($document->file_path)) {
                continue;
            }

            $newPath = 'student-docs/'.$student->id.'/'.basename($document->file_path);

            Storage::disk('public')->copy($document->file_path, $newPath);

            StudentDocument::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'document_type' => $document->document_type,
                'file_path' => $newPath,
                'original_name' => $document->original_name,
                'mime_type' => $document->mime_type,
                'file_size' => $document->file_size,
                'notes' => $document->title ?: $document->document_type_label,
            ]);
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'create' => Pages\CreateApplication::route('/create'),
            'view' => Pages\ViewApplication::route('/{record}'),
            'edit' => Pages\EditApplication::route('/{record}/edit'),
        ];
    }

    // this method to handle successful creation
    public static function afterCreate($record): void
    {
        Notification::make()
            ->title(__('🎉 Application Submitted Successfully!'))
            ->body(__('Application #').$record->application_number.' '.__('has been received. We will review it shortly.'))
            ->success()
            ->duration(10000)
            ->send();
    }
}
