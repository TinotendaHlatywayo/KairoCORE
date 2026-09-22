<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\AssessmentTypeResource\Pages;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\AssessmentType;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Academics\Models\Term;
use Modules\Admin\Services\PermissionRegistry;

class AssessmentTypeResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Exams & Grading');
    }

    protected static ?string $model = AssessmentType::class;

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

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static ?string $navigationLabel = 'Add New Test / Assessment';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Assessment Configuration Details')
                    ->description(__('Define custom test names, marks scopes, and grade weighting configurations.'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Assessment / Test Name'))
                            ->required()
                            ->placeholder(__('e.g. End of Month 1, Topic 2 Quiz, BOT Exam')),

                        Forms\Components\Select::make('term_id')
                            ->label(__('Academic Term'))
                            ->options(function () {
                                $activeYear = AcademicYear::where('is_active', true)->first();
                                if (! $activeYear) {
                                    return [];
                                }

                                return Term::where('academic_year_id', $activeYear->id)
                                    ->pluck('name', 'id');
                            })
                            ->helperText(__('Leave empty to apply this assessment to all terms.'))
                            ->preload()
                            ->placeholder(__('All Terms')),

                        Forms\Components\TextInput::make('max_mark')
                            ->label(__('Max Attainable Mark'))
                            ->numeric()
                            ->default(100.00)
                            ->required()
                            ->placeholder(__('e.g. 50, 100')),

                        Forms\Components\TextInput::make('weight_percentage')
                            ->label(__('Layout Weight towards Final Term Grade (%)'))
                            ->numeric()
                            ->default(20.00)
                            ->required()
                            ->placeholder(__('e.g. 20, 40')),
                    ])->columns(2),

                Forms\Components\Section::make('Assessment Scope Constraints (Optional)')
                    ->description(__('Leave scope values empty if this test applies globally to all levels and subjects.'))
                    ->schema([
                        Forms\Components\Select::make('subject_id')
                            ->label(__('Restrict to Specific Subject'))
                            ->options(Subject::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder(__('All Subjects')),

                        Forms\Components\Select::make('course_id')
                            ->label(__('Restrict to Specific Grade / Form'))
                            ->options(Course::all()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder(__('All Form Levels'))
                            ->live(),

                        Forms\Components\Select::make('section_id')
                            ->label(__('Restrict to Specific Class Stream'))
                            ->options(fn (Forms\Get $get) => Section::where('course_id', $get('course_id'))->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->placeholder(__('All Streams')),
                    ])->columns(3),

                // Auto-inject system parameters silently on form creation
                Forms\Components\Hidden::make('created_by_id')
                    ->default(fn () => Auth::id()),
                Forms\Components\Hidden::make('school_id')
                    ->default(fn () => app('current_tenant')->id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // ADDED: Assessment ID Column for easy CSV mapping reference
                Tables\Columns\TextColumn::make('id')
                    ->label(__('Assessment ID'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label(__('Assessment Name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_mark')
                    ->label(__('Max Mark'))
                    ->numeric()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('weight_percentage')
                    ->label(__('Weight %'))
                    ->numeric()
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label(__('Subject Scope'))
                    ->default('Global (All Subjects)')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('term.name')
                    ->label(__('Term'))
                    ->default('All Terms')
                    ->placeholder('All Terms')
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('course.name')
                    ->label(__('Form Scope'))
                    ->default('Global (All Forms)')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('section.name')
                    ->label(__('Stream Scope'))
                    ->default('Global (All Streams)')
                    ->color('gray'),
            ])
            ->headerActions([
                // =====================================================================
                // IMPORT ASSESSMENT TYPES (EXCEL/CSV)
                // The download-template button lives INSIDE the import modal (top),
                // matching the standard "Import X from Excel or CSV" wizard used by
                // Subjects, Courses and every other CSV import.
                // Standard convention: Test 1 & Test 2 do not weigh toward the final
                // grade (0%), so the Exam carries the full 100%.
                // =====================================================================
                Tables\Actions\Action::make('importAssessmentTypes')
                    ->label(__('Import Assessment Types (Excel/CSV)'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->modalHeading(__('Import Assessment Types from Excel or CSV'))
                    ->modalDescription(__('Download the template, fill it in and upload the file with your assessment types. The system matches every column automatically.'))
                    ->modalWidth(MaxWidth::ExtraLarge)
                    ->modalSubmitActionLabel(__('Import Assessment Types'))
                    ->form([
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('downloadAssessmentTypeTemplate')
                                ->label(__('Download Excel Template'))
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('primary')
                                ->action(function () {
                                    $filename = 'Assessment_Types_Import_Template.csv';

                                    return response()->stream(function () {
                                        $handle = fopen('php://output', 'w');

                                        $columns = ['Name', 'Term', 'Max_Mark', 'Weight_Percentage', 'Subject', 'Course', 'Section', 'Status'];

                                        fputcsv($handle, $columns);
                                        // Term, Subject, Course and Section left blank => all terms, all subjects,
                                        // all forms and all streams. Blank Status defaults to 'marking' on import.
                                        fputcsv($handle, ['Test 1', '', '100', '0', '', '', '', 'marking']);
                                        fputcsv($handle, ['Test 2', '', '100', '0', '', '', '', 'marking']);
                                        fputcsv($handle, ['Exam', '', '100', '100', '', '', '', 'marking']);

                                        fclose($handle);
                                    }, 200, [
                                        'Content-Type' => 'text/csv',
                                        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                                    ]);
                                }),
                        ]),
                        Forms\Components\FileUpload::make('csv_file')
                            ->label(__('Excel / CSV File'))
                            ->helperText(__('The template above contains the exact system columns. Replace the example rows with your assessment types.'))
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'text/x-csv', 'application/csv', 'application/vnd.ms-excel'])
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

                        // Validate minimum header structure (tolerate a UTF-8 BOM)
                        if (! $headers || count($headers) < 8 || trim($headers[0], "\xEF\xBB\xBF") !== 'Name') {
                            fclose($handle);
                            Notification::make()
                                ->title(__('Invalid Template Format'))
                                ->body('The uploaded CSV file does not match the official Schoolcore assessment types template structure.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $rowNum = 1;
                        $errors = [];
                        $recordsToSave = [];

                        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                            $rowNum++;

                            if (empty($row) || count($row) < 8) {
                                continue;
                            }

                            $name = trim($row[0]);
                            $termName = trim($row[1]);
                            $maxMark = trim($row[2]);
                            $weight = trim($row[3]);
                            $subjectName = trim($row[4]);
                            $courseName = trim($row[5]);
                            $sectionName = trim($row[6]);
                            $status = trim($row[7]);

                            // Skip completely empty spacer lines
                            if ($name === '') {
                                continue;
                            }

                            // Resolve Term by name; blank means "All Terms"
                            $termId = null;
                            if ($termName !== '') {
                                $term = Term::where('school_id', $schoolId)
                                    ->where('name', $termName)
                                    ->first();
                                if (! $term) {
                                    $errors[] = "Row {$rowNum}: Term '{$termName}' not found in the system.";

                                    continue;
                                }
                                $termId = $term->id;
                            }

                            // Resolve optional Subject by name
                            $subjectId = null;
                            if ($subjectName !== '') {
                                $subject = Subject::where('school_id', $schoolId)
                                    ->where('name', $subjectName)
                                    ->first();
                                if (! $subject) {
                                    $errors[] = "Row {$rowNum}: Subject '{$subjectName}' not found in the system.";

                                    continue;
                                }
                                $subjectId = $subject->id;
                            }

                            // Resolve optional Course by name
                            $courseId = null;
                            if ($courseName !== '') {
                                $course = Course::where('school_id', $schoolId)
                                    ->where('name', $courseName)
                                    ->first();
                                if (! $course) {
                                    $errors[] = "Row {$rowNum}: Form / Grade '{$courseName}' not found in the system.";

                                    continue;
                                }
                                $courseId = $course->id;
                            }

                            // Resolve optional Section by name
                            $sectionId = null;
                            if ($sectionName !== '') {
                                $section = Section::where('school_id', $schoolId)
                                    ->where('name', $sectionName)
                                    ->first();
                                if (! $section) {
                                    $errors[] = "Row {$rowNum}: Class Stream '{$sectionName}' not found in the system.";

                                    continue;
                                }
                                $sectionId = $section->id;
                            }

                            // Validator: Max Mark numeric and positive
                            $maxMarkValue = $maxMark === '' ? 100 : (float) $maxMark;
                            if (! is_numeric($maxMark) && $maxMark !== '') {
                                $errors[] = "Row {$rowNum}: Max Mark '{$maxMarkValue}' must be a valid number.";

                                continue;
                            }
                            if ($maxMarkValue <= 0) {
                                $errors[] = "Row {$rowNum}: Max Mark must be greater than 0.";

                                continue;
                            }

                            // Validator: Weight 0-100 (0 = does not count toward the final grade)
                            $weightValue = $weight === '' ? 100 : (float) $weight;
                            if (! is_numeric($weight) && $weight !== '') {
                                $errors[] = "Row {$rowNum}: Weight Percentage '{$weightValue}' must be a valid number.";

                                continue;
                            }
                            if ($weightValue < 0 || $weightValue > 100) {
                                $errors[] = "Row {$rowNum}: Weight Percentage must be between 0 and 100.";

                                continue;
                            }

                            // Validator: Status must be a known workflow state
                            $allowedStatuses = ['draft', 'scheduled', 'open', 'marking', 'review', 'reviewed', 'submitted', 'locked', 'published'];
                            $statusValue = $status === '' ? 'marking' : strtolower($status);
                            if (! in_array($statusValue, $allowedStatuses, true)) {
                                $errors[] = "Row {$rowNum}: Status '{$status}' is not a recognised workflow state.";

                                continue;
                            }

                            $recordsToSave[] = [
                                'name' => $name,
                                'term_id' => $termId,
                                'max_mark' => $maxMarkValue,
                                'weight_percentage' => $weightValue,
                                'subject_id' => $subjectId,
                                'course_id' => $courseId,
                                'section_id' => $sectionId,
                                'status' => $statusValue,
                            ];
                        }

                        fclose($handle);

                        // Dispatch red-alert notification lists (bypasses hard SQL crashes)
                        if (! empty($errors)) {
                            $errorList = implode('<br>', array_slice($errors, 0, 10));
                            if (count($errors) > 10) {
                                $errorList .= '<br>...and '.(count($errors) - 10).' more mismatch errors found.';
                            }

                            Notification::make()
                                ->title(__('Assessment Types Template Mismatches Found'))
                                ->body(new HtmlString('<div style="font-size: 11px; text-align: left; max-height: 250px; overflow-y: auto; color: #b91c1c;">'.$errorList.'</div>'))
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        // Secure batch upsert saving (same name + scope updates, never duplicates)
                        $savedCount = 0;
                        foreach ($recordsToSave as $recordData) {
                            AssessmentType::updateOrCreate([
                                'school_id' => $schoolId,
                                'name' => $recordData['name'],
                                'term_id' => $recordData['term_id'],
                                'subject_id' => $recordData['subject_id'],
                                'course_id' => $recordData['course_id'],
                                'section_id' => $recordData['section_id'],
                            ], [
                                'max_mark' => $recordData['max_mark'],
                                'weight_percentage' => $recordData['weight_percentage'],
                                'status' => $recordData['status'],
                                'created_by_id' => auth()->id(),
                            ]);
                            $savedCount++;
                        }

                        Notification::make()
                            ->title(__('Assessment Types Uploaded Successfully'))
                            ->body("Imported and saved {$savedCount} assessment type(s).")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssessmentTypes::route('/'),
            'create' => Pages\CreateAssessmentType::route('/create'),
            'edit' => Pages\EditAssessmentType::route('/{record}/edit'),
        ];
    }
}
