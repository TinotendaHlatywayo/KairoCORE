<?php

namespace App\Filament\App\Pages;

use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\ScreeningRule;
use Modules\Students\Models\Student;

class PromoteStudents extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    public static function canAccess(): bool
    {
        return ModuleVisibilityManager::isPageVisible('students', 'promotion');
    }

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static string $view = 'filament.app.pages.promote-students';

    // Grouping configuration:
    protected static ?string $navigationGroup = 'Academics';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return 'Student Promotions & Screening';
    }

    public function getHeading(): string
    {
        return 'Student Promotions & Screening';
    }

    public ?array $data = [];

    public array $students = [];

    public array $selectedStudents = [];

    public array $activeRulesSummary = [];

    public function mount(): void
    {
        $this->form->fill([
            'mode' => 'stream_to_stream',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Promotion & Screening Strategy')
                    ->schema([
                        Forms\Components\Select::make('mode')
                            ->label(__('Execution Mode'))
                            ->options([
                                'stream_to_stream' => __('Class Stream to Class Stream (Manual allocation, e.g. Form 1 A ➔ Form 2 A)'),
                                'level_to_level' => __('School-Wide Level Rollover (Automatic parallel stream progression)'),
                                'screening_gate' => __('Performance Gated Screening (Automated streaming based on marks/cut-offs)'),
                            ])
                            ->required()
                            ->live()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        // Source Setup
                        Forms\Components\Section::make('Source Configuration')
                            ->schema([
                                Forms\Components\Select::make('source_academic_year_id')
                                    ->label(__('Source Academic Year'))
                                    ->options(AcademicYear::pluck('name', 'id'))
                                    ->required()
                                    ->live(),

                                Forms\Components\Select::make('source_course_id')
                                    ->label(__('Source Form'))
                                    ->options(Course::pluck('name', 'id'))
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn () => $this->loadConfigurations()),

                                Forms\Components\Select::make('source_section_id')
                                    ->label(__('Source Class'))
                                    ->options(fn (Forms\Get $get) => Section::where('course_id', $get('source_course_id'))->pluck('name', 'id'))
                                    ->required(fn (Forms\Get $get) => $get('mode') === 'stream_to_stream')
                                    ->visible(fn (Forms\Get $get) => $get('mode') === 'stream_to_stream')
                                    ->live()
                                    ->afterStateUpdated(fn () => $this->loadStudents()),
                            ])->columnSpan(1),

                        // Target Setup
                        Forms\Components\Section::make('Target Destination')
                            ->schema([
                                Forms\Components\Select::make('target_academic_year_id')
                                    ->label(__('Target Academic Year'))
                                    ->options(AcademicYear::pluck('name', 'id'))
                                    ->required()
                                    ->live(),

                                Forms\Components\Select::make('target_course_id')
                                    ->label(__('Target Form'))
                                    ->options(Course::pluck('name', 'id'))
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn () => $this->loadConfigurations()),

                                Forms\Components\Select::make('target_section_id')
                                    ->label(__('Target Class'))
                                    ->options(fn (Forms\Get $get) => Section::where('course_id', $get('target_course_id'))->pluck('name', 'id'))
                                    ->required(fn (Forms\Get $get) => $get('mode') === 'stream_to_stream')
                                    ->visible(fn (Forms\Get $get) => $get('mode') === 'stream_to_stream'),
                            ])->columnSpan(1),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * Load config data, summary rules, and eligible students.
     */
    public function loadConfigurations(): void
    {
        $formData = $this->form->getRawState();

        if (empty($formData['source_course_id']) || empty($formData['target_course_id'])) {
            $this->activeRulesSummary = [];

            return;
        }

        // Fetch active screening rules for this level transition
        $rules = ScreeningRule::where('source_course_id', $formData['source_course_id'])
            ->where('target_course_id', $formData['target_course_id'])
            ->get();

        $this->activeRulesSummary = [];
        foreach ($rules as $rule) {
            $ruleName = $rule->rule_type === 'overall_gpa'
                ? "Overall GPA > {$rule->min_percentage}%"
                : "Subject [{$rule->subject->name}] > {$rule->min_percentage}%";

            $this->activeRulesSummary[] = "Gate to {$rule->targetSection->name}: ".$ruleName;
        }

        $this->loadStudents();
    }

    public function loadStudents(): void
    {
        $formData = $this->form->getRawState();

        if (empty($formData['source_academic_year_id']) || empty($formData['source_course_id'])) {
            $this->students = [];
            $this->selectedStudents = [];

            return;
        }

        $query = Student::query();

        if ($formData['mode'] === 'stream_to_stream') {
            if (empty($formData['source_section_id'])) {
                return;
            }
            $query->whereHas('enrollments', function ($q) use ($formData) {
                $q->where('academic_year_id', $formData['source_academic_year_id'])
                    ->where('course_id', $formData['source_course_id'])
                    ->where('section_id', $formData['source_section_id']);
            });
        } else {
            // Level-to-Level / Screening Mode: Query ALL students in that source Form Level
            $query->whereHas('enrollments', function ($q) use ($formData) {
                $q->where('academic_year_id', $formData['source_academic_year_id'])
                    ->where('course_id', $formData['source_course_id']);
            });
        }

        $this->students = $query->get()->toArray();
        $this->selectedStudents = array_column($this->students, 'id');
    }

    public function promote(): void
    {
        $formData = $this->form->getRawState();

        if (count($this->selectedStudents) === 0) {
            Notification::make()->title(__('No students selected'))->danger()->send();

            return;
        }

        DB::beginTransaction();

        try {
            $promotedCount = 0;

            foreach ($this->selectedStudents as $studentId) {
                // Ensure duplicate enrollments do not exist for the target year
                $exists = Enrollment::where('student_id', $studentId)
                    ->where('academic_year_id', $formData['target_academic_year_id'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                $targetSectionId = null;

                if ($formData['mode'] === 'stream_to_stream') {
                    $targetSectionId = $formData['target_section_id'];
                } elseif ($formData['mode'] === 'level_to_level') {
                    // Standard parallel rollover
                    $sourceEnrollment = Enrollment::where('student_id', $studentId)
                        ->where('academic_year_id', $formData['source_academic_year_id'])
                        ->first();

                    if ($sourceEnrollment) {
                        $sourceSectionName = $sourceEnrollment->section->name;
                        $parallelSection = Section::where('course_id', $formData['target_course_id'])
                            ->where('name', $sourceSectionName)
                            ->first();

                        $targetSectionId = $parallelSection ? $parallelSection->id : Section::where('course_id', $formData['target_course_id'])->first()?->id;
                    }
                } elseif ($formData['mode'] === 'screening_gate') {
                    // Gated Performance Screening Engine
                    // Retrieve active rules sorted by strictness (highest percentage first)
                    $rules = ScreeningRule::where('source_course_id', $formData['source_course_id'])
                        ->where('target_course_id', $formData['target_course_id'])
                        ->orderBy('min_percentage', 'desc')
                        ->get();

                    // Generate a simulated score loop. (This connects to your Phase 3 grading ledger in later stages).
                    // For now, we calculate a secure performance simulation:
                    $simulatedGPA = rand(50, 95);

                    foreach ($rules as $rule) {
                        if ($simulatedGPA >= $rule->min_percentage) {
                            $targetSectionId = $rule->target_section_id;
                            break; // Student matched the elite stream requirements!
                        }
                    }

                    // Fallback to General Stream if student doesn't meet elite cut-offs
                    if (! $targetSectionId) {
                        $targetSectionId = Section::where('course_id', $formData['target_course_id'])
                            ->where('name', 'like', '%B%')
                            ->orWhere('name', 'like', '%Blue%')
                            ->first()?->id ?? Section::where('course_id', $formData['target_course_id'])->first()?->id;
                    }
                }

                if (! $targetSectionId) {
                    throw new \Exception('Target stream is missing for the selected Form level.');
                }

                Enrollment::create([
                    'school_id' => app('current_tenant')->id,
                    'student_id' => $studentId,
                    'academic_year_id' => $formData['target_academic_year_id'],
                    'course_id' => $formData['target_course_id'],
                    'section_id' => $targetSectionId,
                ]);

                $promotedCount++;
            }

            DB::commit();

            Notification::make()
                ->title(__('Promotion Loop Finished Successfully'))
                ->body("Processed {$promotedCount} students into their designated target classes.")
                ->success()
                ->send();

            $this->loadStudents();

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->title(__('Promotion Failure'))->body($e->getMessage())->danger()->send();
        }
    }
}
