<?php

namespace App\Filament\App\Pages\Academic;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Models\AcademicWorkflowHistory;
use App\Services\Academic\AcademicReadinessScorer;
use App\Services\Academic\AcademicValidationEngine;
use App\Services\Academic\AcademicWorkflowEngine;
use App\Services\ModuleVisibilityManager;
use Filament\Pages\Page;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Assessment;
use Modules\Academics\Models\Term;
use Modules\Admin\Services\PermissionRegistry;
use Modules\Students\Models\Enrollment;

class AcademicOperationsCenter extends Page
{
    use ModuleAwareActiveNavigation;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Academics';

    public static function getNavigationGroup(): ?string
    {

        return __(static::$navigationGroup);

    }

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.app.pages.academic.operations-center';

    protected static ?string $title = 'Academic Operations Center';

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    protected static ?string $breadcrumb = 'Operations Center';

    protected static bool $shouldCacheUnallocatedResources = false;

    public static function canAccess(): bool
    {
        if (! ModuleVisibilityManager::isPageVisible('academics', 'operations')) {
            return false;
        }

        if (class_exists('\Modules\Admin\Services\PermissionRegistry')) {
            return PermissionRegistry::checkPermission('academic_ops.view');
        }

        return true;
    }

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

    public ?string $activeYearName = null;

    public ?int $activeYearId = null;

    public int $activeTerms = 0;

    public int $enrolledStudents = 0;

    public bool $showSetupWizard = false;

    public function mount(): void
    {
        $schoolId = auth()->user()?->school_id ?? config('current_tenant_id');

        $engine = new AcademicWorkflowEngine($schoolId);
        $scorer = new AcademicReadinessScorer($schoolId);
        $validator = new AcademicValidationEngine($schoolId);

        $engine->syncWorkflowSteps($schoolId);

        $this->progressData = $engine->getWorkflowProgress();
        $this->readinessData = $scorer->calculateReadinessScore();
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
        $this->loadYearContext($schoolId);
        $this->deadlines = $this->getUpcomingDeadlines($schoolId);
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

    protected function getUpcomingDeadlines(?int $schoolId): array
    {
        if (! $schoolId) {
            return [];
        }

        $deadlines = [];

        // Terms that are ending within 60 days
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

        // Assessments scheduled within the next 60 days
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
        $routes = [
            'academic_year' => 'filament.app.resources.academic-years.index',
            'terms' => 'filament.app.resources.academic-years.edit',
            'levels' => 'filament.app.resources.courses.index',
            'forms' => 'filament.app.resources.courses.index',
            'streams' => 'filament.app.resources.courses.index',
            'subjects' => 'filament.app.resources.subjects.index',
            'classrooms' => 'filament.app.resources.classrooms.index',
            'teachers' => 'filament.app.resources.teacher-assignments.index',
            'time_slots' => 'filament.app.resources.time-slots.index',
            'timetable' => 'filament.app.pages.visual-timetable-builder',
            'assessment' => 'filament.app.resources.grading-scales.index',
            'admissions_open' => 'filament.app.resources.applications.index',
            'student_enrolment' => 'filament.app.resources.students.index',
            'attendance_ready' => 'filament.app.resources.timetable-lessons.index',
            'report_cards_ready' => 'filament.app.resources.report-templates.index',
        ];

        return $routes[$stepKey] ?? '#';
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
        $schoolId = auth()->user()?->school_id ?? config('current_tenant_id');
        if (! $schoolId) {
            return [];
        }

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
        cache()->forget('academic_readiness_score_'.auth()->user()?->school_id);
        cache()->forget('academic_workflow_progress_'.auth()->user()?->school_id);
        $this->mount();
    }
}
