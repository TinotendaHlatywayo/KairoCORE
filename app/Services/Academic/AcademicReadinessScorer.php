<?php

namespace App\Services\Academic;

use Illuminate\Support\Collection;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Classroom;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\GradingScale;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Timetables\Models\TimetableLesson;

class AcademicReadinessScorer
{
    protected ?int $schoolId;

    public function __construct(?int $schoolId = null)
    {
        $this->schoolId = $schoolId ?? config('current_tenant_id') ?? auth()->user()?->school_id;
    }

    public function calculateReadinessScore(): array
    {
        if (! $this->schoolId) {
            return [
                'score' => 0,
                'breakdown' => [],
                'recommendations' => [],
            ];
        }

        $checks = $this->performAllChecks();

        $totalWeight = 0;
        $earnedWeight = 0;
        $recommendations = [];

        foreach ($checks as $check) {
            $totalWeight += $check['weight'];
            if ($check['passed']) {
                $earnedWeight += $check['weight'];
            } else {
                $recommendations[] = $check['recommendation'];
            }
        }

        $score = $totalWeight > 0 ? min(100, round(($earnedWeight / $totalWeight) * 100, 1)) : 0;

        // Adjust score - require at least 60% for "functional"
        if ($score < 60 && count($checks) > 0) {
            // Check if critical items failed
            $criticalFailed = collect($checks)->filter(fn ($c) => $c['critical'] && ! $c['passed'])->count();
            if ($criticalFailed > 0) {
                $score = min($score, 50);
            }
        }

        return [
            'score' => $score,
            'status' => $this->getStatusFromScore($score),
            'breakdown' => $checks,
            'recommendations' => array_slice($recommendations, 0, 5),
            'details' => $this->buildScoreDetails($checks, $earnedWeight, $totalWeight),
        ];
    }

    public function getAcademicKPIs(): Collection
    {
        if (! $this->schoolId) {
            return collect();
        }

        $kpis = [];

        $activeYear = AcademicYear::where('school_id', $this->schoolId)
            ->where('is_active', true)
            ->first();

        $kpis['academic_year_active'] = [
            'label' => __('Active Academic Year'),
            'value' => $activeYear ? $activeYear->name : 'Not Set',
            'status' => $activeYear ? 'good' : 'critical',
        ];

        $kpis['terms_configured'] = [
            'label' => __('Terms Configured'),
            'value' => $activeYear ? $activeYear->terms()->count() : 0,
            'status' => ($activeYear && $activeYear->terms()->count() > 0) ? 'good' : 'critical',
        ];

        $kpis['forms_created'] = [
            'label' => __('Forms Created'),
            'value' => Course::where('school_id', $this->schoolId)->count(),
            'status' => Course::where('school_id', $this->schoolId)->exists() ? 'good' : 'critical',
        ];

        $kpis['sections_created'] = [
            'label' => __('Sections/Streams Created'),
            'value' => Section::where('school_id', $this->schoolId)->count(),
            'status' => Section::where('school_id', $this->schoolId)->exists() ? 'good' : 'warning',
        ];

        $kpis['subjects_configured'] = [
            'label' => __('Subjects Configured'),
            'value' => Subject::where('school_id', $this->schoolId)->count(),
            'status' => Subject::where('school_id', $this->schoolId)->exists() ? 'good' : 'warning',
        ];

        $kpis['classrooms_defined'] = [
            'label' => __('Classrooms Defined'),
            'value' => Classroom::where('school_id', $this->schoolId)->count(),
            'status' => Classroom::where('school_id', $this->schoolId)->exists() ? 'good' : 'info',
        ];

        $kpis['grading_scales'] = [
            'label' => __('Grading Scales'),
            'value' => GradingScale::where('school_id', $this->schoolId)->count(),
            'status' => GradingScale::where('school_id', $this->schoolId)->exists() ? 'good' : 'warning',
        ];

        return collect($kpis);
    }

    public function getWorkflowTimeline(): array
    {
        $engine = new AcademicWorkflowEngine($this->schoolId);
        $progress = $engine->getWorkflowProgress();
        $steps = $engine->getWorkflowSteps();

        $timeline = [];
        $index = 0;
        foreach ($steps as $key => $step) {
            $timeline[] = [
                'step' => $key,
                'title' => $step['title'],
                'description' => $step['description'],
                'status' => $progress['status_by_step'][$key] ?? 'pending',
                'blocked' => in_array($key, $engine->getBlockedSteps()),
                'index' => $index++,
            ];
        }

        return $timeline;
    }

    protected function performAllChecks(): Collection
    {
        return collect([
            [
                'category' => 'academic_year',
                'label' => __('Academic Year'),
                'passed' => AcademicYear::where('school_id', $this->schoolId)->where('is_active', true)->exists(),
                'weight' => 15,
                'critical' => true,
                'recommendation' => 'No active academic year. Create an academic year and mark it as active.',
            ],
            [
                'category' => 'terms',
                'label' => __('Academic Terms'),
                'passed' => AcademicYear::where('school_id', $this->schoolId)
                    ->where('is_active', true)
                    ->whereHas('terms')
                    ->exists(),
                'weight' => 10,
                'critical' => true,
                'recommendation' => 'No terms configured. Add terms to your active academic year.',
            ],
            [
                'category' => 'forms',
                'label' => __('Education Levels / Forms'),
                'passed' => Course::where('school_id', $this->schoolId)->exists(),
                'weight' => 15,
                'critical' => true,
                'recommendation' => 'No forms/grades created. Create at least one grade level.',
            ],
            [
                'category' => 'streams',
                'label' => __('Sections / Streams'),
                'passed' => Section::where('school_id', $this->schoolId)->exists(),
                'weight' => 10,
                'critical' => false,
                'recommendation' => 'No sections/streams created. Create sections within forms.',
            ],
            [
                'category' => 'subjects',
                'label' => __('Subjects'),
                'passed' => Subject::where('school_id', $this->schoolId)->exists(),
                'weight' => 15,
                'critical' => true,
                'recommendation' => 'No subjects configured. Define subjects for your curriculum.',
            ],
            [
                'category' => 'classrooms',
                'label' => __('Classrooms'),
                'passed' => Classroom::where('school_id', $this->schoolId)->exists(),
                'weight' => 10,
                'critical' => false,
                'recommendation' => 'No classrooms defined. Add physical classrooms for scheduling.',
            ],
            [
                'category' => 'teacher_allocation',
                'label' => __('Teacher Allocation'),
                'passed' => Course::where('school_id', $this->schoolId)
                    ->whereNotNull('teacher_id')
                    ->where('teacher_id', '>', 0)
                    ->exists(),
                'weight' => 10,
                'critical' => false,
                'recommendation' => 'Some forms do not have assigned teachers. Assign teachers to remaining courses.',
            ],
            [
                'category' => 'grading_scales',
                'label' => __('Grading Scales'),
                'passed' => GradingScale::where('school_id', $this->schoolId)->exists(),
                'weight' => 10,
                'critical' => true,
                'recommendation' => 'No grading scales defined. Configure grading scales for assessment.',
            ],
            [
                'category' => 'timetable',
                'label' => __('Timetable Setup'),
                'passed' => TimetableLesson::where('school_id', $this->schoolId)->exists(),
                'weight' => 10,
                'critical' => false,
                'recommendation' => 'Timetable not yet configured. Use the Visual Timetable Builder.',
            ],
        ]);
    }

    protected function getStatusFromScore(float $score): string
    {
        if ($score >= 90) {
            return 'excellent';
        }
        if ($score >= 75) {
            return 'good';
        }
        if ($score >= 50) {
            return 'partial';
        }
        if ($score > 0) {
            return 'minimal';
        }

        return 'not_started';
    }

    protected function buildScoreDetails(Collection $checks, int $earned, int $total): array
    {
        return [
            'earned_points' => $earned,
            'total_points' => $total,
            'passed_checks' => $checks->filter(fn ($c) => $c['passed'])->count(),
            'total_checks' => $checks->count(),
            'critical_failed' => $checks->filter(fn ($c) => $c['critical'] && ! $c['passed'])->count(),
        ];
    }

    public function getQuickActions(): array
    {
        $actions = [];

        if (! AcademicYear::where('school_id', $this->schoolId)->exists()) {
            $actions[] = [
                'label' => __('Create Academic Year'),
                'url' => route('filament.app.resources.academic-years.create'),
                'icon' => 'calendar',
            ];
        }

        if (! Course::where('school_id', $this->schoolId)->exists()) {
            $actions[] = [
                'label' => __('Create Forms'),
                'url' => route('filament.app.resources.courses.create'),
                'icon' => 'academic-cap',
            ];
        }

        if (! Subject::where('school_id', $this->schoolId)->exists()) {
            $actions[] = [
                'label' => __('Configure Subjects'),
                'url' => route('filament.app.resources.subjects.create'),
                'icon' => 'book-open',
            ];
        }

        if (! TimetableLesson::where('school_id', $this->schoolId)->exists()) {
            $actions[] = [
                'label' => __('Build Timetable'),
                'url' => route('filament.app.pages.visual-timetable-builder'),
                'icon' => 'clock',
            ];
        }

        return $actions;
    }
}
