<?php

namespace App\Services\Academic;

use App\Models\AcademicWorkflowHistory;
use App\Models\AcademicWorkflowStep;
use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\AssessmentType;
use Modules\Academics\Models\Classroom;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\CourseSubject;
use Modules\Academics\Models\GradingScale;
use Modules\Academics\Models\ReportTemplate;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Admissions\Models\Application;
use Modules\Attendance\Models\StudentAttendance;
use Modules\Students\Models\Enrollment;
use Modules\Timetables\Models\TimeSlot;
use Modules\Timetables\Models\TimetableLesson;

class AcademicWorkflowEngine
{
    public const SETUP_WORKFLOW = [
        'academic_year' => [
            'title' => 'Academic Year',
            'description' => 'Define the academic year with start and end dates',
            'model' => AcademicYear::class,
        ],
        'terms' => [
            'title' => 'Terms',
            'description' => 'Configure academic terms/semesters for the year',
            'depends_on' => 'academic_year',
        ],
        'levels' => [
            'title' => 'Education Levels',
            'description' => 'Set up education levels (primary, secondary, etc.)',
            'depends_on' => 'academic_year',
        ],
        'forms' => [
            'title' => 'Forms',
            'description' => 'Create form groups/grades for student enrolment',
            'depends_on' => 'levels',
        ],
        'streams' => [
            'title' => 'Streams',
            'description' => 'Define streams within each form for student grouping',
            'depends_on' => 'forms',
        ],
        'subjects' => [
            'title' => 'Subjects',
            'description' => 'Configure subjects offered across all forms',
            'depends_on' => 'forms',
        ],
        'classrooms' => [
            'title' => 'Classrooms',
            'description' => 'Define physical classrooms for teaching',
            'depends_on' => 'subjects',
        ],
        'teachers' => [
            'title' => 'Teachers',
            'description' => 'Assign teachers to forms and subjects',
            'depends_on' => 'classrooms',
        ],
        'time_slots' => [
            'title' => 'Time Slots',
            'description' => 'Configure time slots for timetable scheduling',
            'depends_on' => 'teachers',
        ],
        'timetable' => [
            'title' => 'Timetable',
            'description' => 'Build and configure the school timetable',
            'depends_on' => 'time_slots',
        ],
        'assessment' => [
            'title' => 'Assessment Setup',
            'description' => 'Configure assessment types and grading',
            'depends_on' => 'timetable',
        ],
        'admissions_open' => [
            'title' => 'Admissions Open',
            'description' => 'Open admissions for the new academic year',
            'depends_on' => 'assessment',
        ],
        'student_enrolment' => [
            'title' => 'Student Enrolment',
            'description' => 'Enrol students into forms and streams',
            'depends_on' => 'admissions_open',
        ],
        'attendance_ready' => [
            'title' => 'Attendance Ready',
            'description' => 'Attendance tracking is configured and ready',
            'depends_on' => 'student_enrolment',
        ],
        'report_cards_ready' => [
            'title' => 'Report Cards Ready',
            'description' => 'Report card templates and generation are ready',
            'depends_on' => 'attendance_ready',
        ],
    ];

    protected ?int $schoolId;

    public function __construct(?int $schoolId = null)
    {
        $this->schoolId = $schoolId ?? config('current_tenant_id') ?? auth()->user()?->school_id;
    }

    /**
     * Persist every workflow step definition into academic_workflow_steps so
     * staff can view and manually override status from a database-backed store.
     */
    public function syncWorkflowSteps(?int $schoolId = null): void
    {
        $schoolId = $schoolId ?? $this->schoolId;
        if (! $schoolId) {
            return;
        }

        $order = 0;
        foreach (self::SETUP_WORKFLOW as $key => $step) {
            AcademicWorkflowStep::updateOrCreate(
                ['school_id' => $schoolId, 'step_key' => $key],
                [
                    'title' => $step['title'],
                    'description' => $step['description'],
                    'depends_on' => $step['depends_on'] ?? null,
                    'step_order' => $order++,
                ]
            );
        }
    }

    protected function getPersistedStep(string $stepKey): ?array
    {
        if (! $this->schoolId) {
            return null;
        }

        $row = AcademicWorkflowStep::where('school_id', $this->schoolId)
            ->where('step_key', $stepKey)
            ->first();

        if (! $row) {
            return null;
        }

        if ($row->status === 'manual_completed') {
            return ['status' => 'completed'];
        }

        if ($row->status === 'skipped') {
            return ['status' => 'skipped'];
        }

        return null;
    }

    /**
     * Manually mark a workflow step as completed or skipped. Persists to the
     * academic_workflow_steps table so state survives across sessions.
     */
    public function setStepStatus(string $stepKey, string $status): bool
    {
        if (! $this->schoolId || ! array_key_exists($stepKey, self::SETUP_WORKFLOW)) {
            return false;
        }

        $this->syncWorkflowSteps($this->schoolId);

        $step = AcademicWorkflowStep::where('school_id', $this->schoolId)
            ->where('step_key', $stepKey)
            ->first();

        if (! $step) {
            return false;
        }

        $step->status = $status === 'completed' ? 'manual_completed' : ($status === 'skipped' ? 'skipped' : 'pending');
        $step->completed_at = $status === 'completed' ? now() : null;
        $step->completed_by = $status === 'completed' ? auth()->id() : null;
        $step->save();

        try {
            AcademicWorkflowHistory::record(
                $this->schoolId,
                'academic_workflow_step',
                $step->id,
                $status === 'completed' ? 'step_completed' : 'step_status_changed',
                ['workflow_step' => $stepKey],
                ['status' => $status],
                'Workflow step manually updated from the Academic Operations Center.'
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return true;
    }

    /**
     * Reset a manual override back to auto-detected status.
     */
    public function resetStepStatus(string $stepKey): bool
    {
        if (! $this->schoolId || ! array_key_exists($stepKey, self::SETUP_WORKFLOW)) {
            return false;
        }

        return (bool) AcademicWorkflowStep::where('school_id', $this->schoolId)
            ->where('step_key', $stepKey)
            ->update([
                'status' => 'pending',
                'completed_at' => null,
                'completed_by' => null,
            ]);
    }

    public function getWorkflowSteps(): Collection
    {
        return collect(self::SETUP_WORKFLOW);
    }

    public function getStep(string $stepKey): array
    {
        return self::SETUP_WORKFLOW[$stepKey] ?? [];
    }

    public function getStepDependencies(string $stepKey): array
    {
        $step = self::SETUP_WORKFLOW[$stepKey] ?? [];

        return isset($step['depends_on']) ? [$step['depends_on']] : [];
    }

    public function getNextStep(string $stepKey): ?string
    {
        $steps = array_keys(self::SETUP_WORKFLOW);
        $index = array_search($stepKey, $steps);
        if ($index !== false && $index + 1 < count($steps)) {
            return $steps[$index + 1];
        }

        return null;
    }

    public function getPreviousStep(string $stepKey): ?string
    {
        $steps = array_keys(self::SETUP_WORKFLOW);
        $index = array_search($stepKey, $steps);
        if ($index !== false && $index > 0) {
            return $steps[$index - 1];
        }

        return null;
    }

    public function getStepCompletionStatus(string $stepKey): array
    {
        return [
            'step' => $stepKey,
            'title' => self::SETUP_WORKFLOW[$stepKey]['title'] ?? '',
            'description' => self::SETUP_WORKFLOW[$stepKey]['description'] ?? '',
            'status' => $this->checkStepCompletion($stepKey),
            'dependencies' => $this->getStepDependencies($stepKey),
            'next_step' => $this->getNextStep($stepKey),
            'previous_step' => $this->getPreviousStep($stepKey),
        ];
    }

    public function checkStepCompletion(string $stepKey): string
    {
        // A manual override persisted on academic_workflow_steps always wins,
        // so staff can mark a step done/skipped for their own workflow.
        $override = $this->getPersistedStep($stepKey);
        if ($override) {
            return $override['status'];
        }

        $checkMethods = [
            'academic_year' => fn () => $this->activeYearExists(),
            'terms' => fn () => $this->activeYearExists() && AcademicYear::where('school_id', $this->schoolId)
                ->where('is_active', true)
                ->whereHas('terms')
                ->exists(),
            'levels' => fn () => Course::where('school_id', $this->schoolId)->exists(),
            'forms' => fn () => Course::where('school_id', $this->schoolId)->exists(),
            'streams' => fn () => Section::where('school_id', $this->schoolId)->exists(),
            'subjects' => fn () => Subject::where('school_id', $this->schoolId)->exists(),
            'classrooms' => fn () => Classroom::where('school_id', $this->schoolId)->exists(),
            'teachers' => fn () => $this->checkTeacherAssignments(),
            'time_slots' => fn () => TimeSlot::where('school_id', $this->schoolId)
                ->where('is_break', false)
                ->exists(),
            'timetable' => fn () => TimetableLesson::where('school_id', $this->schoolId)->exists(),
            'assessment' => fn () => $this->assessmentConfigured(),
            'admissions_open' => fn () => Application::where('school_id', $this->schoolId)->exists(),
            'student_enrolment' => fn () => Enrollment::where('school_id', $this->schoolId)->exists(),
            'attendance_ready' => fn () => $this->attendanceReady(),
            'report_cards_ready' => fn () => ReportTemplate::where('school_id', $this->schoolId)
                ->where('is_active', true)
                ->exists(),
        ];

        if (isset($checkMethods[$stepKey])) {
            return $checkMethods[$stepKey]() ? 'completed' : 'pending';
        }

        return 'pending';
    }

    protected function activeYearExists(): bool
    {
        return $this->schoolId && AcademicYear::where('school_id', $this->schoolId)
            ->where('is_active', true)
            ->exists();
    }

    protected function checkTeacherAssignments(): bool
    {
        if (! $this->schoolId) {
            return false;
        }

        $hasTeachers = User::where('school_id', $this->schoolId)
            ->whereHas('roles', fn ($q) => $q->where('name', 'teacher'))
            ->exists();

        if ($hasTeachers) {
            return true;
        }

        return CourseSubject::where('school_id', $this->schoolId)
            ->whereNotNull('teacher_id')
            ->exists()
            || Course::where('school_id', $this->schoolId)
                ->whereNotNull('teacher_id')
                ->exists();
    }

    protected function assessmentConfigured(): bool
    {
        if (! $this->schoolId) {
            return false;
        }

        $gradingReady = GradingScale::where('school_id', $this->schoolId)
            ->whereHas('points')
            ->exists();

        if ($gradingReady) {
            return true;
        }

        return AssessmentType::where('school_id', $this->schoolId)->exists();
    }

    protected function attendanceReady(): bool
    {
        if (! $this->schoolId) {
            return false;
        }

        $attendanceLogged = StudentAttendance::where('school_id', $this->schoolId)->exists();
        if ($attendanceLogged) {
            return true;
        }

        $hasTimetable = TimetableLesson::where('school_id', $this->schoolId)->exists();
        $hasEnrolments = Enrollment::where('school_id', $this->schoolId)->exists();

        return $hasTimetable && $hasEnrolments;
    }

    public function getWorkflowProgress(): array
    {
        $steps = array_keys(self::SETUP_WORKFLOW);
        $completed = 0;
        $skipped = 0;
        $blocked = 0;
        $total = count($steps);
        $status = [];

        foreach ($steps as $stepKey) {
            $stepStatus = $this->checkStepCompletion($stepKey);
            $status[$stepKey] = $stepStatus;

            if ($stepStatus === 'completed') {
                $completed++;
            } elseif ($stepStatus === 'skipped') {
                $skipped++;
            } elseif (in_array($stepKey, $this->getBlockedSteps())) {
                $blocked++;
            }
        }

        $done = $completed + $skipped;

        return [
            'total_steps' => $total,
            'completed_steps' => $completed,
            'skipped_steps' => $skipped,
            'blocked_steps' => $blocked,
            'percentage' => $total > 0 ? min(100, round(($done / $total) * 100, 1)) : 0,
            'status_by_step' => $status,
        ];
    }

    public function isDependencySatisfied(string $depKey): bool
    {
        $depStatus = $this->checkStepCompletion($depKey);

        return in_array($depStatus, ['completed', 'skipped'], true);
    }

    public function getBlockedSteps(): array
    {
        $blocked = [];
        $steps = array_values(array_keys(self::SETUP_WORKFLOW));

        foreach ($steps as $stepKey) {
            $status = $this->checkStepCompletion($stepKey);
            if ($status === 'pending') {
                $dep = self::SETUP_WORKFLOW[$stepKey]['depends_on'] ?? null;
                if ($dep && ! $this->isDependencySatisfied($dep)) {
                    $blocked[] = $stepKey;
                }
            }
        }

        return $blocked;
    }

    public function getReadyNextSteps(): array
    {
        $ready = [];
        $steps = array_keys(self::SETUP_WORKFLOW);

        foreach ($steps as $stepKey) {
            if ($this->checkStepCompletion($stepKey) === 'pending') {
                $dep = self::SETUP_WORKFLOW[$stepKey]['depends_on'] ?? null;
                if (! $dep || $this->isDependencySatisfied($dep)) {
                    $ready[] = $stepKey;
                }
            }
        }

        return $ready;
    }

    public function getStepRoute(string $stepKey): ?string
    {
        $routes = [
            'academic_year' => 'filament.app.resources.academic-years.index',
            'terms' => 'filament.app.resources.academic-years.index',
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

        return $routes[$stepKey] ?? null;
    }
}
