<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\AssessmentMarkResource;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Modules\Academics\Models\AssessmentMark;
use Modules\Academics\Models\AssessmentType;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Students\Models\Enrollment;

class AssessmentWorkspace extends Page implements HasForms
{
    use InteractsWithForms;
    use ModuleAwareActiveNavigation;
    use ModulePermissionAccess;

    protected static ?string $model = AssessmentType::class;

    // Grouping configuration matches the customized Exams & Grading workflow group
    protected static ?string $navigationGroup = 'Exams & Grading';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $navigationLabel = 'Assessment Workspace';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static string $view = 'filament.app.pages.assessment-workspace';

    // Form data array representation to bind live changes immediately
    public ?array $data = [];

    public static array $workflowStates = [
        'draft' => ['title' => 'Draft', 'color' => 'gray'],
        'scheduled' => ['title' => 'Scheduled', 'color' => 'blue'],
        'open' => ['title' => 'Open', 'color' => 'indigo'],
        'marking' => ['title' => 'Marking', 'color' => 'warning'],
        'submitted' => ['title' => 'Submitted', 'color' => 'info'],
        'reviewed' => ['title' => 'Reviewed', 'color' => 'success'],
        'locked' => ['title' => 'Locked', 'color' => 'danger'],
        'published' => ['title' => 'Published', 'color' => 'success'],
    ];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('section_id')
                            ->label(__('Class Stream'))
                            ->options(Section::with('course')->get()->pluck('full_name', 'id'))
                            ->required()
                            ->live()
                            ->placeholder(__('Select Class Stream (e.g. Form 2A)...')),

                        Forms\Components\Select::make('subject_id')
                            ->label(__('Subject'))
                            ->options(Subject::pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->placeholder(__('Select Subject...')),
                    ]),
            ])
            ->statePath('data'); // FIXED: Explicitly registers form state to the data array for live reactivity
    }

    /**
     * Resolves and compiles active assessment cards and real-time student grades statistics
     */
    public function getKanbanCards(): array
    {
        $sectionId = $this->data['section_id'] ?? null;
        $subjectId = $this->data['subject_id'] ?? null;

        if (! $sectionId || ! $subjectId) {
            return [];
        }

        $schoolId = app('current_tenant')->id;
        $totalStudents = Enrollment::where('section_id', $sectionId)->count();

        $section = Section::with('course')->find($sectionId);
        if (! $section) {
            return [];
        }

        // Fetch active assessments scoped globally or specifically for this stream/subject
        $assessments = AssessmentType::where('school_id', $schoolId)
            ->where(function ($q) use ($section) {
                $q->whereNull('section_id')->orWhere('section_id', $section->id);
            })
            ->where(function ($q) use ($section) {
                $q->whereNull('course_id')->orWhere('course_id', $section->course_id);
            })
            ->where(function ($q) use ($subjectId) {
                $q->whereNull('subject_id')->orWhere('subject_id', $subjectId);
            })
            ->get();

        // Resolve the enrolled student ids for this section once, instead of
        // re-querying them inside the loop for every assessment.
        $enrollmentIds = Enrollment::where('section_id', $sectionId)->pluck('id');

        // Aggregate marks across all relevant assessments in one query so the
        // per-card average/highest/lowest don't issue a query each.
        $marksStats = AssessmentMark::where('subject_id', $subjectId)
            ->whereIn('enrollment_id', $enrollmentIds)
            ->whereNotNull('marks_obtained')
            ->selectRaw('assessment_type_id, COUNT(*) as marked_count, AVG(marks_obtained) as avg, MAX(marks_obtained) as highest, MIN(marks_obtained) as lowest')
            ->groupBy('assessment_type_id')
            ->get()
            ->keyBy('assessment_type_id');

        $cards = [];
        foreach (self::$workflowStates as $stateKey => $stateConfig) {
            $cards[$stateKey] = [];
        }

        foreach ($assessments as $assessment) {
            $stats = $marksStats->get($assessment->id);

            $markedCount = (int) ($stats->marked_count ?? 0);
            $average = $stats->avg !== null ? round((float) $stats->avg, 1) : 0;
            $highest = $stats->highest ?? 0;
            $lowest = $stats->lowest ?? 0;
            $missing = max(0, $totalStudents - $markedCount);

            $progressPercentage = $totalStudents > 0 ? round(($markedCount / $totalStudents) * 100) : 0;

            // Generate direct filtered routing link to standard Record Marks list
            $recordMarksUrl = AssessmentMarkResource::getUrl('index', [
                'tableFilters[section_id][value]' => $sectionId,
                'tableFilters[subject_id][value]' => $subjectId,
                'tableFilters[assessment_type_id][value]' => $assessment->id,
            ]);

            if (isset($cards[$assessment->status])) {
                $cards[$assessment->status][] = [
                    'id' => $assessment->id,
                    'name' => $assessment->name,
                    'date' => $assessment->created_at ? $assessment->created_at->format('d-M-Y') : 'N/A',
                    'marked_progress' => "{$markedCount}/{$totalStudents}",
                    'progress_percent' => $progressPercentage,
                    'avg' => $average,
                    'highest' => $highest,
                    'lowest' => $lowest,
                    'missing' => $missing,
                    'is_complete' => $missing === 0,
                    'record_marks_url' => $recordMarksUrl, // Dynamic URL injection
                ];
            }
        }

        return $cards;
    }

    /**
     * Shifts an assessment card to a new workflow status state inside the active table
     */
    public function moveCard(int $assessmentId, string $newStatus): void
    {
        $assessment = AssessmentType::find($assessmentId);
        if ($assessment) {
            $assessment->update(['status' => $newStatus]);

            Notification::make()
                ->title(__('Assessment Moved'))
                ->body("'{$assessment->name}' status updated to ".ucfirst($newStatus).'.')
                ->success()
                ->send();
        }
    }
}
