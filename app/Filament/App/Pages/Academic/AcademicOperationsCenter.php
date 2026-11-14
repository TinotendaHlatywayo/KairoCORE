<?php

namespace App\Filament\App\Pages\Academic;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\AcademicYearResource;
use App\Filament\App\Resources\ClassroomResource;
use App\Filament\App\Resources\CourseResource;
use App\Filament\App\Resources\SubjectResource;
use App\Models\AcademicWorkflowHistory;
use App\Services\Academic\AcademicReadinessScorer;
use App\Services\Academic\AcademicValidationEngine;
use App\Services\Academic\AcademicWorkflowEngine;
use App\Support\TeacherOptions;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Assessment;
use Modules\Academics\Models\Classroom;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\GradingScale;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Academics\Models\Term;
use Modules\Students\Models\Enrollment;

class AcademicOperationsCenter extends Page
{
    use HasPageHelp;
    use ModuleAwareActiveNavigation;
    use ModulePermissionAccess;

    protected function getHeaderActions(): array
    {
        return array_filter([
            $this->getHelpAction(),
        ]);
    }

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Academics';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.app.pages.academic.operations-center';

    protected static ?string $title = 'Academic Operations Center';

    protected ?string $heading = '';

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    protected static ?string $breadcrumb = 'Operations Center';

    protected static bool $shouldCacheUnallocatedResources = false;

    public string $tab = 'overview';

    public string $search = '';

    public string $filter = 'all';

    public ?string $sortField = null;

    public string $sortDirection = 'asc';

    public int $perPage = 10;

    public int $currentPage = 1;

    public array $selected = [];

    public ?string $drawer = null;

    public ?int $editingId = null;

    public array $form = [];

    public ?array $confirmingDelete = null;

    public array $teacherOptions = [];

    public array $progressData = [];

    public array $readinessData = [];

    public array $validationIssues = [];

    public array $timeline = [];

    public array $kpis = [];

    public array $quickActions = [];

    public array $workflowCards = [];

    public array $recentActivity = [];

    public array $suggestions = [];

    public array $deadlines = [];

    public array $stats = [];

    public array $phases = [];

    public ?string $activeYearName = null;

    public ?int $activeYearId = null;

    public ?string $activeYearStart = null;

    public ?string $activeYearEnd = null;

    public ?string $activeYearRange = null;

    public float $activeYearProgress = 0;

    public float $activeYearTodayPercent = 0;

    public ?string $nextTermName = null;

    public ?string $nextTermEnd = null;

    public ?int $nextTermDays = null;

    public int $activeTerms = 0;

    public int $enrolledStudents = 0;

    public int $unassignedCount = 0;

    public int $readinessScore = 0;

    public bool $showSetupWizard = false;

    public function mount(): void
    {
        $this->loadState();
    }

    protected function loadState(): void
    {
        $schoolId = $this->schoolId();

        $engine = new AcademicWorkflowEngine($schoolId);
        $scorer = new AcademicReadinessScorer($schoolId);
        $validator = new AcademicValidationEngine($schoolId);

        $engine->syncWorkflowSteps($schoolId);

        $this->progressData = $engine->getWorkflowProgress();
        $this->readinessData = $scorer->calculateReadinessScore();
        $this->readinessScore = (int) round($this->readinessData['score'] ?? 0);
        $this->validationIssues = $validator->validateAll()->toArray();
        $this->timeline = $engine->getWorkflowSteps()->map(function ($step, $key) use ($engine) {
            return [
                'key' => $key,
                'title' => $step['title'],
                'description' => $step['description'],
                'status' => $this->progressData['status_by_step'][$key] ?? 'pending',
                'dependencies' => $engine->getStepDependencies($key),
                'next_step' => $engine->getNextStep($key),
            ];
        })->values()->toArray();
        $this->kpis = $scorer->getAcademicKPIs()->toArray();
        $this->quickActions = $scorer->getQuickActions();
        $this->workflowCards = $this->buildWorkflowCards($engine);
        $this->suggestions = $this->generateSuggestions($engine, $scorer);
        $this->recentActivity = $this->getRecentActivity();
        $this->teacherOptions = TeacherOptions::options();
        $this->loadYearContext($schoolId);
        $this->deadlines = $this->getUpcomingDeadlines($schoolId);
        $this->buildOverview($schoolId);
    }

    protected function schoolId(): int
    {
        return (int) (auth()->user()?->school_id
            ?? current_tenant()?->id
            ?? config('tenancy.single_tenant_id')
            ?? 1);
    }

    protected function loadYearContext(?int $schoolId): void
    {
        $activeYear = AcademicYear::where('school_id', $schoolId)
            ->where('is_active', true)
            ->first();

        $this->activeYearName = $activeYear?->name;
        $this->activeYearId = $activeYear?->id;
        $this->activeTerms = $activeYear?->terms()->count() ?? 0;
        $this->enrolledStudents = Enrollment::where('school_id', $schoolId)->count();
    }

    protected function buildOverview(?int $schoolId): void
    {
        $activeYear = AcademicYear::where('school_id', $schoolId)
            ->where('is_active', true)
            ->first();

        $this->unassignedCount = Course::where('school_id', $schoolId)
            ->where(function (Builder $query) {
                $query->whereNull('teacher_id')->orWhere('teacher_id', 0);
            })
            ->count();

        $this->activeYearStart = $activeYear?->start_date?->toDateString();
        $this->activeYearEnd = $activeYear?->end_date?->toDateString();
        $this->activeYearRange = $activeYear
            ? $activeYear->start_date->format('M j').' – '.$activeYear->end_date->format('M j')
            : null;

        $this->activeYearProgress = $this->dateProgress(
            $activeYear?->start_date,
            $activeYear?->end_date
        );

        $this->activeYearTodayPercent = $this->activeYearProgress;

        $nextTerm = $activeYear
            ? $activeYear->terms()->whereDate('end_date', '>=', now()->toDateString())->orderBy('end_date')->first()
            : null;

        $this->nextTermName = $nextTerm?->name;
        $this->nextTermEnd = $nextTerm?->end_date?->toDateString();
        $this->nextTermDays = $nextTerm?->end_date
            ? (int) now()->startOfDay()->diffInDays($nextTerm->end_date->startOfDay(), false)
            : null;

        $this->stats = [
            [
                'label' => __('Active academic year'),
                'value' => $activeYear?->name ?? __('Not set'),
                'detail' => $this->activeYearRange ?? __('Create one to begin'),
                'icon' => 'calendar',
                'color' => '#4f46e5',
                'tab' => 'years',
                'featured' => true,
            ],
            [
                'label' => __('Terms'),
                'value' => $activeYear?->terms()->count() ?? 0,
                'detail' => __('Configured this year'),
                'icon' => 'chart',
                'color' => '#8b5cf6',
                'tab' => 'years',
                'featured' => false,
            ],
            [
                'label' => __('Forms'),
                'value' => Course::where('school_id', $schoolId)->count(),
                'detail' => __('Levels in use'),
                'icon' => 'doc',
                'color' => '#0ea5e9',
                'tab' => 'levels',
                'featured' => false,
            ],
            [
                'label' => __('Streams'),
                'value' => Section::where('school_id', $schoolId)->count(),
                'detail' => __('Class groups'),
                'icon' => 'grid',
                'color' => '#14b8a6',
                'tab' => 'levels',
                'featured' => false,
            ],
            [
                'label' => __('Subjects'),
                'value' => Subject::where('school_id', $schoolId)->count(),
                'detail' => __('Offered across forms'),
                'icon' => 'cap',
                'color' => '#f59e0b',
                'tab' => 'subjects',
                'featured' => false,
            ],
            [
                'label' => __('Classrooms'),
                'value' => Classroom::where('school_id', $schoolId)->count(),
                'detail' => __('Teaching spaces'),
                'icon' => 'home',
                'color' => '#f43f5e',
                'tab' => 'classrooms',
                'featured' => false,
            ],
            [
                'label' => __('Grading scales'),
                'value' => GradingScale::where('school_id', $schoolId)->count(),
                'detail' => __('In use'),
                'icon' => 'check',
                'color' => '#10b981',
                'tab' => null,
                'featured' => false,
            ],
        ];

        $groups = [
            __('Foundation') => ['academic_year', 'terms', 'levels'],
            __('Structure') => ['forms', 'streams', 'subjects', 'classrooms'],
            __('Teaching') => ['teachers', 'time_slots', 'timetable', 'assessment'],
            __('Go live') => ['admissions_open', 'student_enrolment', 'attendance_ready', 'report_cards_ready'],
        ];

        $statuses = $this->progressData['status_by_step'] ?? [];
        $this->phases = [];

        foreach ($groups as $label => $keys) {
            $steps = [];
            $done = 0;

            foreach ($keys as $key) {
                $definition = AcademicWorkflowEngine::SETUP_WORKFLOW[$key] ?? null;

                if (! $definition) {
                    continue;
                }

                $status = $statuses[$key] ?? 'pending';
                $complete = in_array($status, ['completed', 'skipped'], true);

                if ($complete) {
                    $done++;
                }

                $steps[] = [
                    'key' => $key,
                    'title' => __($definition['title']),
                    'description' => __($definition['description']),
                    'status' => $status,
                    'complete' => $complete,
                ];
            }

            $this->phases[] = [
                'label' => $label,
                'steps' => $steps,
                'done' => $done,
                'total' => count($steps),
                'percent' => count($steps) > 0 ? (int) round($done / count($steps) * 100) : 0,
            ];
        }
    }

    protected function dateProgress($start, $end): float
    {
        if (! $start || ! $end) {
            return 0;
        }

        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();
        $total = $end->timestamp - $start->timestamp;

        if ($total <= 0) {
            return 100;
        }

        return (float) max(0, min(100, round((now()->timestamp - $start->timestamp) / $total * 100, 1)));
    }

    public function getTabsProperty(): array
    {
        $schoolId = $this->schoolId();

        return [
            'overview' => ['label' => __('Overview'), 'count' => null],
            'levels' => ['label' => __('Levels'), 'count' => Course::where('school_id', $schoolId)->count()],
            'subjects' => ['label' => __('Subjects'), 'count' => Subject::where('school_id', $schoolId)->count()],
            'classrooms' => ['label' => __('Classrooms'), 'count' => Classroom::where('school_id', $schoolId)->count()],
            'years' => ['label' => __('Academic years'), 'count' => AcademicYear::where('school_id', $schoolId)->count()],
        ];
    }

    public function getNounProperty(): string
    {
        return match ($this->tab) {
            'levels' => __('level'),
            'subjects' => __('subject'),
            'classrooms' => __('classroom'),
            'years' => __('academic year'),
            default => __('record'),
        };
    }

    public function getColumnsProperty(): array
    {
        return match ($this->tab) {
            'levels' => [
                ['key' => 'name', 'label' => __('Level / form'), 'sortable' => true],
                ['key' => 'code', 'label' => __('Code'), 'sortable' => true],
                ['key' => 'teacher', 'label' => __('Teacher'), 'sortable' => false],
                ['key' => 'sections_count', 'label' => __('Streams'), 'sortable' => true],
                ['key' => 'workflow_status', 'label' => __('Stage'), 'sortable' => true],
            ],
            'subjects' => [
                ['key' => 'name', 'label' => __('Subject'), 'sortable' => true],
                ['key' => 'code', 'label' => __('Code'), 'sortable' => true],
                ['key' => 'type', 'label' => __('Type'), 'sortable' => true],
                ['key' => 'credit_weight', 'label' => __('Credits'), 'sortable' => true],
                ['key' => 'is_elective', 'label' => __('Elective'), 'sortable' => false],
                ['key' => 'workflow_status', 'label' => __('Stage'), 'sortable' => true],
            ],
            'classrooms' => [
                ['key' => 'name', 'label' => __('Classroom'), 'sortable' => true],
                ['key' => 'location', 'label' => __('Building'), 'sortable' => true],
                ['key' => 'capacity', 'label' => __('Capacity'), 'sortable' => true],
                ['key' => 'sections_count', 'label' => __('In use'), 'sortable' => true],
            ],
            'years' => [
                ['key' => 'name', 'label' => __('Year'), 'sortable' => true],
                ['key' => 'start_date', 'label' => __('Starts'), 'sortable' => true],
                ['key' => 'end_date', 'label' => __('Ends'), 'sortable' => true],
                ['key' => 'is_active', 'label' => __('Current'), 'sortable' => false],
                ['key' => 'workflow_status', 'label' => __('Stage'), 'sortable' => true],
                ['key' => 'terms_count', 'label' => __('Terms'), 'sortable' => true],
            ],
            default => [],
        };
    }

    public function getFiltersProperty(): array
    {
        return match ($this->tab) {
            'levels' => [
                ['key' => 'all', 'label' => __('All')],
                ['key' => 'needs_teacher', 'label' => __('Needs teacher')],
                ['key' => 'active', 'label' => __('Active')],
            ],
            'subjects' => [
                ['key' => 'all', 'label' => __('All')],
                ['key' => 'pending', 'label' => __('Pending')],
                ['key' => 'active', 'label' => __('Active')],
                ['key' => 'elective', 'label' => __('Elective')],
            ],
            'classrooms' => [
                ['key' => 'all', 'label' => __('All')],
                ['key' => 'unused', 'label' => __('Unused')],
            ],
            'years' => [
                ['key' => 'all', 'label' => __('All')],
                ['key' => 'current', 'label' => __('Current')],
            ],
            default => [['key' => 'all', 'label' => __('All')]],
        };
    }

    protected function baseQuery(): Builder
    {
        $schoolId = $this->schoolId();
        $search = trim($this->search);

        $query = match ($this->tab) {
            'levels' => Course::query()->where('school_id', $schoolId)->with('teacher')->withCount('sections'),
            'subjects' => Subject::query()->where('school_id', $schoolId),
            'classrooms' => Classroom::query()->where('school_id', $schoolId)->withCount('sections'),
            'years' => AcademicYear::query()->where('school_id', $schoolId)->withCount('terms'),
            default => Course::query()->where('school_id', $schoolId)->with('teacher')->withCount('sections'),
        };

        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('name', 'like', '%'.$search.'%');

                if (in_array($this->tab, ['levels', 'subjects'], true)) {
                    $inner->orWhere('code', 'like', '%'.$search.'%');
                }

                if ($this->tab === 'classrooms') {
                    $inner->orWhere('location', 'like', '%'.$search.'%');
                }
            });
        }

        match ($this->tab) {
            'levels' => match ($this->filter) {
                'needs_teacher' => $query->where(function (Builder $inner) {
                    $inner->whereNull('teacher_id')->orWhere('teacher_id', 0);
                }),
                'active' => $query->where('workflow_status', 'active'),
                default => $query,
            },
            'subjects' => match ($this->filter) {
                'pending' => $query->where('workflow_status', 'pending'),
                'active' => $query->where('workflow_status', 'active'),
                'elective' => $query->where('is_elective', true),
                default => $query,
            },
            'classrooms' => $this->filter === 'unused'
                ? $query->whereDoesntHave('sections')
                : $query,
            'years' => $this->filter === 'current'
                ? $query->where('is_active', true)
                : $query,
            default => $query,
        };

        $allowedSorts = match ($this->tab) {
            'levels' => ['name', 'code', 'sections_count', 'workflow_status', 'sequence_order'],
            'subjects' => ['name', 'code', 'type', 'credit_weight', 'workflow_status'],
            'classrooms' => ['name', 'location', 'capacity', 'sections_count'],
            'years' => ['name', 'start_date', 'end_date', 'workflow_status', 'terms_count'],
            default => ['name'],
        };

        if ($this->sortField !== null && in_array($this->sortField, $allowedSorts, true)) {
            $query->orderBy($this->sortField, $this->sortDirection === 'desc' ? 'desc' : 'asc');
        } else {
            match ($this->tab) {
                'levels' => $query->orderBy('sequence_order')->orderBy('name'),
                'years' => $query->orderByDesc('start_date'),
                default => $query->orderBy('name'),
            };
        }

        return $query;
    }

    public function getRowsProperty(): Collection
    {
        return $this->baseQuery()->get();
    }

    public function getTotalProperty(): int
    {
        return $this->rows->count();
    }

    public function getTotalPagesProperty(): int
    {
        return max(1, (int) ceil($this->total / max(1, $this->perPage)));
    }

    public function getPagedRowsProperty(): Collection
    {
        return $this->rows->slice(($this->currentPage - 1) * $this->perPage, $this->perPage)->values();
    }

    public function getYearOptionsProperty(): Collection
    {
        return AcademicYear::where('school_id', $this->schoolId())
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'is_active']);
    }

    public function getUnassignedCoursesProperty(): Collection
    {
        return Course::where('school_id', $this->schoolId())
            ->where(function (Builder $query) {
                $query->whereNull('teacher_id')->orWhere('teacher_id', 0);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    public function setTab(string $tab): void
    {
        if (! array_key_exists($tab, $this->getTabsProperty())) {
            return;
        }

        $this->tab = $tab;
        $this->search = '';
        $this->filter = 'all';
        $this->sortField = null;
        $this->sortDirection = 'asc';
        $this->currentPage = 1;
        $this->selected = [];
        $this->drawer = null;
        $this->editingId = null;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->currentPage = 1;
        $this->selected = [];
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->currentPage = 1;
    }

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
        $this->selected = [];
    }

    public function updatedPerPage(): void
    {
        $this->currentPage = 1;
    }

    public function goToPage(int $page): void
    {
        $this->currentPage = max(1, min($page, $this->totalPages));
    }

    public function previousPage(): void
    {
        $this->goToPage($this->currentPage - 1);
    }

    public function nextPage(): void
    {
        $this->goToPage($this->currentPage + 1);
    }

    public function toggleSelect(int $id): void
    {
        $id = (int) $id;
        $selected = array_map('intval', $this->selected);

        if (in_array($id, $selected, true)) {
            $this->selected = array_values(array_diff($selected, [$id]));
        } else {
            $this->selected = array_values(array_unique([...$selected, $id]));
        }
    }

    public function toggleSelectAll(): void
    {
        $ids = $this->pagedRows->pluck('id')->map(fn ($id) => (int) $id)->all();
        $selected = array_map('intval', $this->selected);

        if (count($ids) > 0 && count(array_intersect($ids, $selected)) === count($ids)) {
            $this->selected = array_values(array_diff($selected, $ids));
        } else {
            $this->selected = array_values(array_unique([...$selected, ...$ids]));
        }
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function openCreate(?string $tab = null): void
    {
        if ($tab !== null) {
            $this->setTab($tab);
        }

        if ($this->tab === 'overview') {
            $this->setTab('levels');
        }

        $this->editingId = null;
        $this->form = $this->defaultForm();
        $this->drawer = 'form';
    }

    public function openEdit(int $id): void
    {
        $record = $this->findRecord($id);

        if (! $record || ! $this->canManage('update', $record)) {
            $this->dispatch('acad-toast', message: __('You do not have permission to edit this record.'));

            return;
        }

        $this->editingId = $record->getKey();
        $this->drawer = 'form';
        $this->form = match ($this->tab) {
            'levels' => [
                'name' => $record->name,
                'code' => $record->code,
                'teacher_id' => $record->teacher_id,
                'streams' => $record->sections_count ?? $record->sections()->count(),
            ],
            'subjects' => [
                'name' => $record->name,
                'code' => $record->code,
                'type' => $record->type ?: 'theory',
                'credit_weight' => (float) $record->credit_weight,
                'is_elective' => (bool) $record->is_elective,
            ],
            'classrooms' => [
                'name' => $record->name,
                'location' => $record->location,
                'capacity' => (int) $record->capacity,
            ],
            'years' => [
                'name' => $record->name,
                'start_date' => $record->start_date?->toDateString(),
                'end_date' => $record->end_date?->toDateString(),
            ],
            default => [],
        };
    }

    public function openAssign(): void
    {
        $this->drawer = 'assign';
        $this->editingId = null;
        $this->form = [];
    }

    public function closeDrawer(): void
    {
        $this->drawer = null;
        $this->editingId = null;
        $this->form = [];
    }

    protected function defaultForm(): array
    {
        return match ($this->tab) {
            'levels' => ['name' => '', 'code' => '', 'teacher_id' => '', 'streams' => 1],
            'subjects' => ['name' => '', 'code' => '', 'type' => 'theory', 'credit_weight' => 1, 'is_elective' => false],
            'classrooms' => ['name' => '', 'location' => '', 'capacity' => 40],
            'years' => ['name' => '', 'start_date' => now()->startOfYear()->toDateString(), 'end_date' => now()->endOfYear()->toDateString()],
            default => [],
        };
    }

    public function save(): void
    {
        if (! in_array($this->tab, ['levels', 'subjects', 'classrooms', 'years'], true)) {
            return;
        }

        $this->validate($this->rulesForTab(), [], $this->validationAttributes());

        $schoolId = $this->schoolId();
        $data = $this->form;
        $editing = $this->editingId ? $this->findRecord($this->editingId) : null;

        if ($editing && ! $this->canManage('update', $editing)) {
            $this->dispatch('acad-toast', message: __('You do not have permission to edit this record.'));

            return;
        }

        if (! $editing && ! $this->canManage('create')) {
            $this->dispatch('acad-toast', message: __('You do not have permission to create records here.'));

            return;
        }

        match ($this->tab) {
            'levels' => $this->saveLevel($data, $schoolId, $editing),
            'subjects' => $this->saveSubject($data, $schoolId, $editing),
            'classrooms' => $this->saveClassroom($data, $schoolId, $editing),
            'years' => $this->saveYear($data, $schoolId, $editing),
        };

        $this->closeDrawer();
        $this->selected = [];
        $this->refreshAcademicState();
        $this->dispatch('acad-toast', message: $editing ? __('Changes saved.') : __(':name created.', ['name' => $data['name']]));
    }

    protected function saveLevel(array $data, int $schoolId, ?Model $editing): void
    {
        $teacherId = filled($data['teacher_id'] ?? null) ? (int) $data['teacher_id'] : null;

        if ($teacherId !== null && ! array_key_exists($teacherId, $this->teacherOptions)) {
            $teacherId = null;
        }

        $payload = [
            'school_id' => $schoolId,
            'name' => trim((string) $data['name']),
            'code' => filled($data['code'] ?? null) ? trim((string) $data['code']) : null,
            'teacher_id' => $teacherId,
        ];

        if ($editing) {
            $editing->update($payload);
            $course = $editing;
        } else {
            $payload['workflow_status'] = 'pending';
            $payload['sequence_order'] = ((int) Course::where('school_id', $schoolId)->max('sequence_order')) + 1;
            $course = Course::create($payload);
        }

        $streams = max(0, min(20, (int) ($data['streams'] ?? 0)));
        $existing = $course->sections()->count();

        for ($index = $existing; $index < $streams; $index++) {
            $letter = $this->streamName($index);

            Section::create([
                'school_id' => $schoolId,
                'course_id' => $course->id,
                'name' => $letter,
                'code' => mb_strtoupper(($course->code ?: $course->name).'-'.$letter),
                'capacity' => 40,
                'target_size' => 40,
                'rank_order' => $index + 1,
            ]);
        }
    }

    protected function saveSubject(array $data, int $schoolId, ?Model $editing): void
    {
        $payload = [
            'school_id' => $schoolId,
            'name' => trim((string) $data['name']),
            'code' => filled($data['code'] ?? null) ? trim((string) $data['code']) : null,
            'type' => $data['type'] ?? 'theory',
            'credit_weight' => (float) ($data['credit_weight'] ?? 1),
            'is_elective' => (bool) ($data['is_elective'] ?? false),
        ];

        if ($editing) {
            $editing->update($payload);
        } else {
            $payload['workflow_status'] = 'pending';
            Subject::create($payload);
        }
    }

    protected function saveClassroom(array $data, int $schoolId, ?Model $editing): void
    {
        $payload = [
            'school_id' => $schoolId,
            'name' => trim((string) $data['name']),
            'location' => filled($data['location'] ?? null) ? trim((string) $data['location']) : null,
            'capacity' => (int) ($data['capacity'] ?? 0),
        ];

        if ($editing) {
            $editing->update($payload);
        } else {
            Classroom::create($payload);
        }
    }

    protected function saveYear(array $data, int $schoolId, ?Model $editing): void
    {
        $payload = [
            'school_id' => $schoolId,
            'name' => trim((string) $data['name']),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ];

        if ($editing) {
            $editing->update($payload);
        } else {
            $payload['is_active'] = false;
            $payload['workflow_status'] = 'pending';
            AcademicYear::create($payload);
        }
    }

    protected function streamName(int $index): string
    {
        $letters = range('A', 'Z');

        return $letters[$index % 26].($index >= 26 ? (string) (intdiv($index, 26) + 1) : '');
    }

    protected function rulesForTab(): array
    {
        return match ($this->tab) {
            'levels' => [
                'form.name' => ['required', 'string', 'max:255'],
                'form.code' => ['nullable', 'string', 'max:50'],
                'form.teacher_id' => ['nullable'],
                'form.streams' => ['nullable', 'integer', 'min:0', 'max:20'],
            ],
            'subjects' => [
                'form.name' => ['required', 'string', 'max:255'],
                'form.code' => ['nullable', 'string', 'max:50'],
                'form.type' => ['required', 'in:theory,practical'],
                'form.credit_weight' => ['required', 'numeric', 'min:0', 'max:99'],
                'form.is_elective' => ['boolean'],
            ],
            'classrooms' => [
                'form.name' => ['required', 'string', 'max:255'],
                'form.location' => ['nullable', 'string', 'max:255'],
                'form.capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            ],
            'years' => [
                'form.name' => ['required', 'string', 'max:255'],
                'form.start_date' => ['required', 'date'],
                'form.end_date' => ['required', 'date', 'after:form.start_date'],
            ],
            default => [],
        };
    }

    protected function validationAttributes(): array
    {
        return [
            'form.name' => __('name'),
            'form.code' => __('code'),
            'form.teacher_id' => __('teacher'),
            'form.streams' => __('streams'),
            'form.type' => __('type'),
            'form.credit_weight' => __('credits'),
            'form.location' => __('building'),
            'form.capacity' => __('capacity'),
            'form.start_date' => __('start date'),
            'form.end_date' => __('end date'),
        ];
    }

    public function askDelete(int $id): void
    {
        $this->confirmingDelete = ['tab' => $this->tab, 'ids' => [(int) $id]];
    }

    public function askDeleteSelected(): void
    {
        $ids = array_map('intval', $this->selected);

        if ($ids === []) {
            return;
        }

        $this->confirmingDelete = ['tab' => $this->tab, 'ids' => $ids];
    }

    public function cancelDelete(): void
    {
        $this->confirmingDelete = null;
    }

    public function deleteConfirmed(): void
    {
        if (! $this->confirmingDelete) {
            return;
        }

        $tab = $this->confirmingDelete['tab'] ?? $this->tab;
        $ids = array_map('intval', $this->confirmingDelete['ids'] ?? []);
        $deleted = 0;

        foreach ($ids as $id) {
            $record = $this->findRecord($id, $tab);

            if (! $record || ! $this->canManage('delete', $record, $tab)) {
                continue;
            }

            try {
                if ($tab === 'years' && $record->terms()->exists()) {
                    $this->dispatch('acad-toast', message: __('Remove this year’s terms before deleting it.'));

                    continue;
                }

                $record->delete();
                $deleted++;
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $this->selected = array_values(array_diff(array_map('intval', $this->selected), $ids));
        $this->confirmingDelete = null;
        $this->refreshAcademicState();

        $this->dispatch('acad-toast', message: $deleted === 1
            ? __('Record deleted.')
            : __(':count records deleted.', ['count' => $deleted]));
    }

    public function assignTeacher(int $courseId, $teacherId): void
    {
        $course = Course::where('school_id', $this->schoolId())->find($courseId);

        if (! $course || ! CourseResource::canEdit($course)) {
            $this->dispatch('acad-toast', message: __('You do not have permission to assign this teacher.'));

            return;
        }

        $teacherId = filled($teacherId) ? (int) $teacherId : null;

        if ($teacherId !== null && ! array_key_exists($teacherId, $this->teacherOptions)) {
            $this->dispatch('acad-toast', message: __('That teacher could not be found.'));

            return;
        }

        $course->update(['teacher_id' => $teacherId]);
        $this->refreshAcademicState();

        $this->dispatch('acad-toast', message: $teacherId
            ? __('Teacher assigned to :name.', ['name' => $course->name])
            : __('Teacher removed from :name.', ['name' => $course->name]));

        if ($this->getUnassignedCoursesProperty()->isEmpty()) {
            $this->dispatch('acad-confetti');
        }
    }

    public function activateYear(int $yearId): void
    {
        $year = AcademicYear::where('school_id', $this->schoolId())->find($yearId);

        if (! $year || ! AcademicYearResource::canEdit($year)) {
            $this->dispatch('acad-toast', message: __('You do not have permission to change the current year.'));

            return;
        }

        $year->update(['is_active' => true]);
        $this->refreshAcademicState();
        $this->dispatch('acad-toast', message: __(':name is now the current academic year.', ['name' => $year->name]));
    }

    public function toggleWorkflowStatus(int $id): void
    {
        $record = $this->findRecord($id);

        if (! $record || ! $this->canManage('update', $record)) {
            return;
        }

        if (! in_array($this->tab, ['levels', 'subjects', 'years'], true)) {
            return;
        }

        $record->update([
            'workflow_status' => $record->workflow_status === 'active' ? 'pending' : 'active',
        ]);

        $this->refreshAcademicState();
        $this->dispatch('acad-toast', message: __('Stage updated.'));
    }

    public function toggleSubjectElective(int $id): void
    {
        $subject = Subject::where('school_id', $this->schoolId())->find($id);

        if (! $subject || ! SubjectResource::canEdit($subject)) {
            return;
        }

        $subject->update(['is_elective' => ! $subject->is_elective]);
        $this->dispatch('acad-toast', message: __('Subject updated.'));
    }

    public function exportCsv()
    {
        $rows = $this->rows;
        $columns = $this->columns;

        $filename = 'academics-'.$this->tab.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows, $columns) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, array_map(fn ($column) => $column['label'], $columns));

            foreach ($rows as $row) {
                $line = [];

                foreach ($columns as $column) {
                    $line[] = match ($column['key']) {
                        'teacher' => $row->teacher?->name,
                        'is_active' => $row->is_active ? __('Yes') : __('No'),
                        'is_elective' => $row->is_elective ? __('Yes') : __('No'),
                        'start_date', 'end_date' => $row->{$column['key']}?->format('Y-m-d'),
                        default => $row->{$column['key']},
                    };
                }

                fputcsv($handle, $line);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function findRecord(int $id, ?string $tab = null): ?Model
    {
        $tab ??= $this->tab;

        return match ($tab) {
            'levels' => Course::where('school_id', $this->schoolId())->find($id),
            'subjects' => Subject::where('school_id', $this->schoolId())->find($id),
            'classrooms' => Classroom::where('school_id', $this->schoolId())->find($id),
            'years' => AcademicYear::where('school_id', $this->schoolId())->find($id),
            default => null,
        };
    }

    protected function canManage(string $ability, ?Model $record = null, ?string $tab = null): bool
    {
        $resource = match ($tab ?? $this->tab) {
            'levels' => CourseResource::class,
            'subjects' => SubjectResource::class,
            'classrooms' => ClassroomResource::class,
            'years' => AcademicYearResource::class,
            default => null,
        };

        if ($resource === null) {
            return false;
        }

        return match ($ability) {
            'create' => $resource::canCreate(),
            'update' => $record ? $resource::canEdit($record) : false,
            'delete' => $record ? $resource::canDelete($record) : false,
            default => false,
        };
    }

    protected function refreshAcademicState(): void
    {
        cache()->forget('academic_readiness_score_'.$this->schoolId());
        cache()->forget('academic_workflow_progress_'.$this->schoolId());

        $this->loadState();
    }

    protected function getUpcomingDeadlines(?int $schoolId): array
    {
        if (! $schoolId) {
            return [];
        }

        $deadlines = [];

        $terms = Term::where('school_id', $schoolId)
            ->whereBetween('end_date', [now(), now()->addDays(60)])
            ->orderBy('end_date')
            ->limit(3)
            ->get();

        foreach ($terms as $term) {
            $daysLeft = (int) now()->diffInDays($term->end_date, false);
            $deadlines[] = [
                'type' => 'term_end',
                'title' => "{$term->name} ends",
                'detail' => $term->end_date->format('M d, Y'),
                'days_left' => $daysLeft,
                'route' => $this->getRouteForStep('terms'),
            ];
        }

        $assessments = Assessment::where('school_id', $schoolId)
            ->whereBetween('assessment_date', [now()->startOfDay(), now()->addDays(60)->endOfDay()])
            ->orderBy('assessment_date')
            ->limit(3)
            ->get();

        foreach ($assessments as $assessment) {
            $daysLeft = (int) now()->startOfDay()->diffInDays($assessment->assessment_date->startOfDay(), false);
            $deadlines[] = [
                'type' => 'assessment',
                'title' => "{$assessment->name} scheduled",
                'detail' => $assessment->assessment_date->format('M d, Y'),
                'days_left' => $daysLeft,
                'route' => $this->getRouteForStep('assessment'),
            ];
        }

        usort($deadlines, fn ($a, $b) => $a['days_left'] <=> $b['days_left']);

        return array_slice($deadlines, 0, 5);
    }

    protected function buildWorkflowCards(AcademicWorkflowEngine $engine): array
    {
        $cards = [];

        $steps = $engine->getWorkflowSteps();

        foreach ($steps as $key => $step) {
            $status = $engine->checkStepCompletion($key);
            $nextStep = $engine->getNextStep($key);

            $cards[$key] = [
                'title' => $step['title'],
                'description' => $step['description'],
                'status' => $status,
                'step_key' => $key,
                'next_step' => $nextStep,
                'depends_on' => $step['depends_on'] ?? null,
                'route' => $this->getRouteForStep($key),
                'is_blocked' => $this->isStepBlocked($key, $engine),
                'is_current' => $this->isCurrentStep($key, $engine),
            ];
        }

        return $cards;
    }

    protected function getRouteForStep(string $stepKey): string
    {
        return app(AcademicWorkflowEngine::class)->getStepRoute($stepKey) ?? '#';
    }

    protected function isStepBlocked(string $stepKey, AcademicWorkflowEngine $engine): bool
    {
        $step = $engine->checkStepCompletion($stepKey);
        if (in_array($step, ['completed', 'skipped'], true)) {
            return false;
        }

        $dep = AcademicWorkflowEngine::SETUP_WORKFLOW[$stepKey]['depends_on'] ?? null;
        if ($dep && ! $engine->isDependencySatisfied($dep)) {
            return true;
        }

        return false;
    }

    protected function isCurrentStep(string $stepKey, AcademicWorkflowEngine $engine): bool
    {
        $status = $engine->checkStepCompletion($stepKey);
        if (in_array($status, ['completed', 'skipped'], true)) {
            return false;
        }

        $dep = AcademicWorkflowEngine::SETUP_WORKFLOW[$stepKey]['depends_on'] ?? null;
        if ($dep && $engine->isDependencySatisfied($dep)) {
            return true;
        }

        if (! $dep && $status === 'pending') {
            return true;
        }

        return false;
    }

    protected function generateSuggestions(AcademicWorkflowEngine $engine, AcademicReadinessScorer $scorer): array
    {
        $suggestions = [];
        $readyNext = $engine->getReadyNextSteps();

        foreach ($readyNext as $stepKey) {
            $step = $engine->getStep($stepKey);
            $suggestions[] = [
                'title' => 'Complete: '.$step['title'],
                'description' => $step['description'],
                'priority' => $engine->checkStepCompletion($stepKey) === 'pending' ? 'high' : 'medium',
                'action' => $this->getRouteForStep($stepKey),
            ];
        }

        $readiness = $scorer->calculateReadinessScore();
        if (! empty($readiness['recommendations'])) {
            foreach ($readiness['recommendations'] as $rec) {
                $suggestions[] = [
                    'title' => 'Critical Issue',
                    'description' => $rec,
                    'priority' => 'critical',
                    'action' => '#',
                ];
            }
        }

        return array_slice($suggestions, 0, 8);
    }

    protected function getRecentActivity(): array
    {
        $schoolId = $this->schoolId();

        $entries = AcademicWorkflowHistory::where('school_id', $schoolId)
            ->with('user')
            ->latest()
            ->limit(10)
            ->get();

        return $entries->map(function ($entry) {
            $stepTitle = $entry->workflow_step
                ? (AcademicWorkflowEngine::SETUP_WORKFLOW[$entry->workflow_step]['title'] ?? ucwords(str_replace('_', ' ', $entry->workflow_step)))
                : null;

            $description = $stepTitle
                ? "{$entry->action}: {$stepTitle}"
                : "{$entry->action} on {$entry->entity_type}";

            return [
                'description' => $description,
                'user' => $entry->user?->name ?? 'System',
                'timestamp' => $entry->created_at?->diffForHumans() ?? 'just now',
                'type' => $entry->action === 'step_completed' ? 'success' : 'info',
            ];
        })->toArray();
    }

    public function startSetupWizard(): void
    {
        $this->showSetupWizard = true;
    }

    public function resetReadinessCache(): void
    {
        cache()->forget('academic_readiness_score_'.$this->schoolId());
        cache()->forget('academic_workflow_progress_'.$this->schoolId());
        $this->loadState();
    }
}
