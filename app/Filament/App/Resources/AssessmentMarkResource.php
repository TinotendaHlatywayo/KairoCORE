<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AssessmentMarkResource\Pages;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Modules\Academics\Models\AssessmentMark;
use Modules\Academics\Models\AssessmentType;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;

class AssessmentMarkResource extends Resource
{
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Student column renders enrollment→student; eager load to avoid N+1.
        return parent::getEloquentQuery()->with(['enrollment.student']);
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Exams & Grading');
    }

    protected static ?string $model = AssessmentMark::class;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        if (! ModuleVisibilityManager::isVisible('academics')) {
            return false;
        }

        if (class_exists('\Modules\Admin\Services\PermissionRegistry')) {
            return PermissionRegistry::checkPermission('academic_ops.manage_assessments');
        }

        return true;
    }

    protected static ?string $navigationGroup = 'Exams & Grading';

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationLabel = 'Record Student Marks';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('enrollment_id')
                    ->label(__('Student'))
                    ->relationship('enrollment', 'id', modifyQueryUsing: fn ($query) => $query->whereHas('student'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->student?->full_name ?? 'N/A')
                    ->searchable()
                    ->preload()
                    ->disabled(fn ($context) => $context === 'edit')
                    ->required(),

                Forms\Components\Select::make('subject_id')
                    ->label(__('Subject'))
                    ->relationship('subject', 'name')
                    ->disabled(fn ($context) => $context === 'edit')
                    ->required(),

                Forms\Components\Select::make('assessment_type_id')
                    ->label(__('Assessment Type'))
                    ->relationship('assessmentType', 'name')
                    ->disabled(fn ($context) => $context === 'edit')
                    ->required(),

                Forms\Components\TextInput::make('marks_obtained')
                    ->label(__('Marks Obtained'))
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('teacher_initials')
                    ->maxLength(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('enrollment.student.full_name')
                    ->label(__('Student'))
                    ->getStateUsing(fn ($record) => $record->enrollment?->student?->full_name ?? 'Orphaned Record (No Student)')
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('enrollment.student', function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('admission_number', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('enrollment.section.name')
                    ->label(__('Class Stream'))
                    ->getStateUsing(fn ($record) => $record->enrollment?->section
                            ? ($record->enrollment->section->course?->name.' '.$record->enrollment->section->name)
                            : 'N/A'
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label(__('Subject'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('assessmentType.name')
                    ->label(__('Assessment'))
                    ->sortable(),

                Tables\Columns\TextInputColumn::make('marks_obtained')
                    ->label(__('Marks Obtained'))
                    ->rules(['numeric', 'min:0'])
                    ->alignCenter(),

                Tables\Columns\SelectColumn::make('status')
                    ->label(__('Execution Status'))
                    ->options([
                        'present' => __('Present'),
                        'absent' => __('Absent'),
                        'excused' => __('Excused'),
                        'cheated' => __('Cheated'),
                        'late_submission' => __('Late Submission'),
                    ])
                    ->alignCenter(),
            ])
            ->headerActions([
                // DYNAMIC MARKS SHEET POPULATOR
                Tables\Actions\Action::make('populate')
                    ->label(__('Populate Class Marks Sheet'))
                    ->icon('heroicon-o-user-group')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('section_id')
                            ->label(__('Class Stream'))
                            ->options(Section::with('course')->get()->pluck('full_name', 'id'))
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('subject_id')
                            ->label(__('Subject'))
                            ->options(Subject::all()->pluck('name', 'id'))
                            ->required(),

                        Forms\Components\Select::make('assessment_type_id')
                            ->label(__('Assessment Type'))
                            ->options(fn (Forms\Get $get) => AssessmentType::where('school_id', app('current_tenant')->id)
                                ->pluck('name', 'id')
                            )
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $schoolId = app('current_tenant')->id;
                        $enrollments = Enrollment::where('section_id', $data['section_id'])->get();

                        $createdCount = 0;
                        foreach ($enrollments as $enrollment) {
                            $record = AssessmentMark::firstOrCreate([
                                'school_id' => $schoolId,
                                'enrollment_id' => $enrollment->id,
                                'assessment_type_id' => $data['assessment_type_id'],
                                'subject_id' => $data['subject_id'],
                            ]);
                            if ($record->wasRecentlyCreated) {
                                $createdCount++;
                            }
                        }

                        Notification::make()
                            ->title(__('Class Sheet Populated'))
                            ->body("Loaded {$enrollments->count()} student rows. {$createdCount} new records initialized.")
                            ->success()
                            ->send();

                        return redirect(static::getUrl('index', [
                            'tableFilters[section_id][value]' => $data['section_id'],
                            'tableFilters[subject_id][value]' => $data['subject_id'],
                            'tableFilters[assessment_type_id][value]' => $data['assessment_type_id'],
                        ]));
                    }),

                // =====================================================================
                // FEATURE 1: EXPORT CUSTOM SIZED MARKS SHEET TEMPLATE (CSV)
                // =====================================================================
                Tables\Actions\Action::make('exportTemplate')
                    ->label(__('Download Marks Template'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('scope_type')
                            ->label(__('Template Scope'))
                            ->options([
                                'class_one_subject' => __('One Class Stream (One Subject)'),
                                'class_all_subjects' => __('One Class Stream (All Subjects)'),
                                'stream_one_subject' => __('One Grade / Form Level (One Subject)'),
                                'stream_all_subjects' => __('One Grade / Form Level (All Subjects)'),
                                'school_one_subject' => __('Whole School (One Subject)'),
                                'school_all_subjects' => __('Whole School (All Subjects)'),
                            ])
                            ->default('class_one_subject')
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('section_id')
                            ->label(__('Class Stream'))
                            ->options(Section::with('course')->get()->pluck('full_name', 'id'))
                            ->visible(fn (Forms\Get $get) => in_array($get('scope_type'), ['class_one_subject', 'class_all_subjects']))
                            ->required(fn (Forms\Get $get) => in_array($get('scope_type'), ['class_one_subject', 'class_all_subjects'])),

                        Forms\Components\Select::make('course_id')
                            ->label(__('Grade / Form Level'))
                            ->options(Course::all()->pluck('name', 'id'))
                            ->visible(fn (Forms\Get $get) => in_array($get('scope_type'), ['stream_one_subject', 'stream_all_subjects']))
                            ->required(fn (Forms\Get $get) => in_array($get('scope_type'), ['stream_one_subject', 'stream_all_subjects'])),

                        Forms\Components\Select::make('subject_id')
                            ->label(__('Subject'))
                            ->options(Subject::all()->pluck('name', 'id'))
                            ->visible(fn (Forms\Get $get) => in_array($get('scope_type'), ['class_one_subject', 'stream_one_subject', 'school_one_subject']))
                            ->required(fn (Forms\Get $get) => in_array($get('scope_type'), ['class_one_subject', 'stream_one_subject', 'school_one_subject'])),

                        Forms\Components\Select::make('assessment_type_id')
                            ->label(__('Assessment / Test Type'))
                            ->options(fn () => AssessmentType::where('school_id', app('current_tenant')->id)->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $schoolId = app('current_tenant')->id;
                        $scope = $data['scope_type'];

                        // 1. Gather targeted enrollments
                        $enrollmentQuery = Enrollment::where('school_id', $schoolId)->with('student');
                        if (str_contains($scope, 'class_')) {
                            $enrollmentQuery->where('section_id', $data['section_id']);
                        } elseif (str_contains($scope, 'stream_')) {
                            $enrollmentQuery->whereHas('section', fn ($q) => $q->where('course_id', $data['course_id']));
                        }
                        $enrollments = $enrollmentQuery->get();

                        // 2. Gather targeted subjects
                        $subjectQuery = Subject::where('school_id', $schoolId);
                        if (str_contains($scope, '_one_subject')) {
                            $subjectQuery->where('id', $data['subject_id']);
                        }
                        $subjects = $subjectQuery->get();

                        $assessment = AssessmentType::find($data['assessment_type_id']);
                        $assessmentName = $assessment ? $assessment->name : 'N/A';

                        // 3. Build streamed CSV array rows
                        $csvRows = [];
                        $csvRows[] = ['Student_ID', 'Student_Name', 'Subject_Code', 'Subject_Name', 'Assessment_ID', 'Assessment_Name', 'Marks_Obtained', 'Initials'];

                        foreach ($enrollments as $enrollment) {
                            $student = $enrollment->student;
                            if (! $student) {
                                continue;
                            }

                            foreach ($subjects as $subject) {
                                // Fetch existing mark if already recorded
                                $existingMark = AssessmentMark::where([
                                    'enrollment_id' => $enrollment->id,
                                    'assessment_type_id' => $data['assessment_type_id'],
                                    'subject_id' => $subject->id,
                                ])->first();

                                $csvRows[] = [
                                    $student->student_id_number,
                                    $student->full_name,
                                    $subject->code,
                                    $subject->name,
                                    $data['assessment_type_id'],
                                    $assessmentName,
                                    $existingMark ? $existingMark->marks_obtained : '',
                                    $existingMark ? $existingMark->teacher_initials : '',
                                ];
                            }
                        }

                        // 4. Download file stream
                        $filename = 'Marks_Template_'.date('Ymd_His').'.csv';

                        return response()->stream(function () use ($csvRows) {
                            $handle = fopen('php://output', 'w');
                            foreach ($csvRows as $row) {
                                fputcsv($handle, $row);
                            }
                            fclose($handle);
                        }, 200, [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                        ]);
                    }),

                // =====================================================================
                // FEATURE 2: SECURE VALIDATED CSV MARKS IMPORTER
                // =====================================================================
                Tables\Actions\Action::make('importMarks')
                    ->label(__('Import Marks (CSV)'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->form([
                        Forms\Components\FileUpload::make('csv_file')
                            ->label(__('Upload Completed CSV Template'))
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $schoolId = app('current_tenant')->id;
                        $filePath = public_path('storage/'.$data['csv_file']);

                        if (! file_exists($filePath)) {
                            Notification::make()
                                ->title(__('File Error'))
                                ->body('The uploaded spreadsheet could not be loaded. Please try again.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $handle = fopen($filePath, 'r');
                        $headers = fgetcsv($handle, 1000, ',');

                        // Validate minimum header structure
                        if (! $headers || count($headers) < 8 || trim($headers[0]) !== 'Student_ID') {
                            fclose($handle);
                            Notification::make()
                                ->title(__('Invalid Template Format'))
                                ->body('The uploaded CSV file does not match the official Schoolcore grading template structure.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $rowNum = 1;
                        $errors = [];
                        $recordsToSave = [];

                        // 1. COMPREHENSIVE ROW-BY-ROW VALIDATION LOOP (Bypasses crashes)
                        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                            $rowNum++;

                            if (empty($row) || count($row) < 8) {
                                continue;
                            }

                            $studentId = trim($row[0]);
                            $studentName = trim($row[1]);
                            $subjectCode = trim($row[2]);
                            $subjectName = trim($row[3]);
                            $assessmentId = trim($row[4]);
                            $assessmentName = trim($row[5]);
                            $marksObtained = trim($row[6]);
                            $initials = trim($row[7]);

                            // Skip completely empty spacer lines
                            if (empty($studentId) && empty($subjectCode)) {
                                continue;
                            }

                            // Validator A: Verify Student ID exists
                            $student = Student::where('school_id', $schoolId)
                                ->where('student_id_number', $studentId)
                                ->first();
                            if (! $student) {
                                $errors[] = "Row {$rowNum}: Student ID '{$studentId}' not found in the system.";

                                continue;
                            }

                            // Validator B: Verify Enrollment exists
                            $enrollment = Enrollment::where('student_id', $student->id)->latest()->first();
                            if (! $enrollment) {
                                $errors[] = "Row {$rowNum}: Student '{$studentName}' does not have an active enrollment.";

                                continue;
                            }

                            // Validator C: Verify Subject Code exists
                            $subject = Subject::where('school_id', $schoolId)
                                ->where('code', $subjectCode)
                                ->first();
                            if (! $subject) {
                                $errors[] = "Row {$rowNum}: Subject Code '{$subjectCode}' not found in the system.";

                                continue;
                            }

                            // Validator D: Verify Assessment ID exists
                            $assessment = AssessmentType::where('school_id', $schoolId)
                                ->find($assessmentId);
                            if (! $assessment) {
                                $errors[] = "Row {$rowNum}: Assessment ID '{$assessmentId}' not found in the system.";

                                continue;
                            }

                            // Validator E: Verify Marks obtained are numeric and inside bounds
                            if ($marksObtained !== '') {
                                if (! is_numeric($marksObtained)) {
                                    $errors[] = "Row {$rowNum}: Marks Obtained '{$marksObtained}' must be a valid number.";

                                    continue;
                                }

                                if ($marksObtained < 0 || $marksObtained > $assessment->max_mark) {
                                    $errors[] = "Row {$rowNum}: Marks Obtained '{$marksObtained}' cannot be less than 0 or greater than the max test limit ({$assessment->max_mark}).";

                                    continue;
                                }
                            }

                            // Passed validation, queue for saving
                            $recordsToSave[] = [
                                'school_id' => $schoolId,
                                'enrollment_id' => $enrollment->id,
                                'assessment_type_id' => $assessment->id,
                                'subject_id' => $subject->id,
                                'marks_obtained' => $marksObtained !== '' ? floatval($marksObtained) : null,
                                'teacher_initials' => ! empty($initials) ? substr($initials, 0, 5) : null,
                            ];
                        }

                        fclose($handle);

                        // 2. DISPATCH RED-ALERT NOTIFICATION LISTS (Bypasses hard SQL crashes)
                        if (! empty($errors)) {
                            // Display up to 10 detailed errors inside a scrollable notification block
                            $errorList = implode('<br>', array_slice($errors, 0, 10));
                            if (count($errors) > 10) {
                                $errorList .= '<br>...and '.(count($errors) - 10).' more mismatch errors found.';
                            }

                            Notification::make()
                                ->title(__('Grading Template Mismatches Found'))
                                ->body(new HtmlString('<div style="font-size: 11px; text-align: left; max-height: 250px; overflow-y: auto; color: #b91c1c;">'.$errorList.'</div>'))
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        // 3. SECURE BATCH UPSERT SAVING
                        $savedCount = 0;
                        foreach ($recordsToSave as $recordData) {
                            AssessmentMark::updateOrCreate([
                                'school_id' => $recordData['school_id'],
                                'enrollment_id' => $recordData['enrollment_id'],
                                'assessment_type_id' => $recordData['assessment_type_id'],
                                'subject_id' => $recordData['subject_id'],
                            ], [
                                'marks_obtained' => $recordData['marks_obtained'],
                                'teacher_initials' => $recordData['teacher_initials'],
                            ]);
                            $savedCount++;
                        }

                        Notification::make()
                            ->title(__('Marks Uploaded Successfully'))
                            ->body("Processed and loaded {$savedCount} student grading rows.")
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('section_id')
                    ->label(__('Class Stream'))
                    ->options(fn () => Section::with('course')->get()->pluck('full_name', 'id'))
                    ->query(function ($query, array $data) {
                        return $query->when($data['value'], function ($q, $sectionId) {
                            $q->whereHas('enrollment', fn ($e) => $e->where('section_id', $sectionId));
                        });
                    }),
                Tables\Filters\SelectFilter::make('subject_id')
                    ->label(__('Subject'))
                    ->options(fn () => Subject::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('assessment_type_id')
                    ->label(__('Assessment Type'))
                    ->options(fn () => AssessmentType::pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssessmentMarks::route('/'),
            'create' => Pages\CreateAssessmentMark::route('/create'),
            'edit' => Pages\EditAssessmentMark::route('/{record}/edit'),
        ];
    }
}
