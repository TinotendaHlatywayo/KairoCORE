<?php

namespace App\Filament\App\Pages\Exams;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Services\ModuleVisibilityManager;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\AssessmentMark;
use Modules\Academics\Models\AssessmentType;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Academics\Models\Term;
use Modules\Students\Models\Enrollment;

/**
 * Student performance analytics for the whole school, a grade/form level or an
 * individual class stream. Replaces the old Assessment Workspace kanban page.
 */
class PerformanceAnalyticsPage extends Page
{
    use ModuleAwareActiveNavigation;

    protected static string $view = 'filament.app.pages.exams.performance-analytics';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Exams & Grading';

    protected static ?string $navigationLabel = 'Performance Analytics';

    protected static ?string $title = 'Student Performance Analytics';

    protected static ?string $slug = 'exams-performance-analytics';

    public ?int $courseId = null;

    public ?int $sectionId = null;

    public ?int $subjectId = null;

    public ?int $termId = null;

    public static function canAccess(): bool
    {
        return ModuleVisibilityManager::isModuleVisible('exams');
    }

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    public function mount(): void
    {
        $this->termId ??= $this->activeTermId();
    }

    public function updatedCourseId(): void
    {
        $this->sectionId = null;
    }

    protected function activeTermId(): ?int
    {
        $schoolId = $this->schoolId();
        $activeYear = AcademicYear::where('school_id', $schoolId)->where('is_active', true)->first();

        return Term::where('school_id', $schoolId)
            ->where('academic_year_id', $activeYear?->id)
            ->orderBy('is_active', 'desc')
            ->orderByDesc('id')
            ->value('id');
    }

    protected function schoolId(): ?int
    {
        return current_tenant()?->id ?? auth()->user()?->school_id;
    }

    protected function sectionOptions(?int $schoolId): array
    {
        $sections = Section::with('course')
            ->when($this->courseId, fn ($q) => $q->where('course_id', $this->courseId))
            ->get();

        $options = [];
        foreach ($sections as $section) {
            $options[$section->id] = trim(($section->course?->name ?? '').' '.$section->name);
        }

        asort($options);

        return $options;
    }

    protected function getViewData(): array
    {
        $schoolId = $this->schoolId();
        $term = Term::find($this->termId);

        $metrics = $this->computeScopeMetrics($this->termId, $this->courseId, $this->sectionId, $this->subjectId);

        // Term-over-term trend for the same scope (subject filter omitted).
        $trend = [];
        if ($term) {
            $terms = Term::where('school_id', $schoolId)
                ->where('academic_year_id', $term->academic_year_id)
                ->orderBy('start_date')
                ->get();

            foreach ($terms as $row) {
                $rowMetrics = $this->computeScopeMetrics($row->id, $this->courseId, $this->sectionId, null);
                $avg = $this->averageOf($rowMetrics['overalls']);
                $trend[] = [
                    'term' => ucwords(strtolower($row->name)),
                    'avg' => $avg === null ? null : round($avg, 1),
                    'students' => $rowMetrics['total'],
                ];
            }
        }

        $overalls = $metrics['overalls'];

        $avgOverall = $this->averageOf($overalls);
        $passRate = $overalls->isEmpty() ? 0 : round($overalls->filter(fn ($v) => $v >= 50)->count() / $overalls->count() * 100, 1);
        $distinctionRate = $overalls->isEmpty() ? 0 : round($overalls->filter(fn ($v) => $v >= 80)->count() / $overalls->count() * 100, 1);
        $supportRate = $overalls->isEmpty() ? 0 : round($overalls->filter(fn ($v) => $v < 40)->count() / $overalls->count() * 100, 1);

        return [
            'schoolId' => $schoolId,
            'terms' => Term::with('academicYear')->where('school_id', $schoolId)->orderByDesc('id')->get(),
            'courses' => Course::where('school_id', $schoolId)->orderBy('name')->pluck('name', 'id'),
            'sections' => $this->sectionOptions($schoolId),
            'subjects' => Subject::where('school_id', $schoolId)->orderBy('name')->pluck('name', 'id'),
            'kpis' => [
                'students' => $metrics['total'],
                'avg_overall' => $avgOverall === null ? null : round($avgOverall, 1),
                'pass_rate' => $passRate,
                'distinction_rate' => $distinctionRate,
                'support_rate' => $supportRate,
                'assessments' => $metrics['assessmentCount'],
                'subjects' => $metrics['subjectCount'],
            ],
            'gradeDistribution' => $metrics['gradeDistribution'],
            'bandDistribution' => $metrics['bandDistribution'],
            'subjectStats' => $metrics['subjectStats'],
            'sectionStats' => $metrics['sectionStats'],
            'topLearners' => $metrics['topLearners'],
            'supportLearners' => $metrics['supportLearners'],
            'trend' => $trend,
            'selected' => [
                'term' => $term?->name,
                'year' => $term?->academicYear?->name,
                'scope' => $this->scopeLabel(),
            ],
        ];
    }

    /**
     * Weighted academic metrics for one term/scope snapshot.
     *
     * @return array<string, mixed>
     */
    protected function computeScopeMetrics(?int $termId, ?int $courseId, ?int $sectionId, ?int $subjectId = null): array
    {
        $schoolId = $this->schoolId();
        $term = Term::find($termId);
        $yearId = $term?->academic_year_id;

        $enrollments = Enrollment::query()
            ->with(['student', 'section.course'])
            ->where('school_id', $schoolId);

        if ($yearId) {
            $enrollments->where('academic_year_id', $yearId);
        }
        if ($courseId) {
            $enrollments->whereHas('section', fn ($q) => $q->where('course_id', $courseId));
        }
        if ($sectionId) {
            $enrollments->where('section_id', $sectionId);
        }
        $enrollments = $enrollments->get();

        $assessmentTypes = AssessmentType::where('school_id', $schoolId)
            ->where(fn ($q) => $q->where('term_id', $termId)->orWhereNull('term_id'))
            ->get()
            ->keyBy('id');

        $markQuery = AssessmentMark::whereIn('enrollment_id', $enrollments->pluck('id'))
            ->whereIn('assessment_type_id', $assessmentTypes->keys())
            ->whereNotNull('marks_obtained');

        if ($subjectId) {
            $markQuery->where('subject_id', $subjectId);
        }

        $marks = $markQuery->get(['enrollment_id', 'subject_id', 'assessment_type_id', 'marks_obtained']);

        // Weighted subject percentage per enrollment: [enrollment][subject] => [Σ pct×wt, Σ wt].
        $subjectByEnrollment = [];
        foreach ($marks as $mark) {
            $type = $assessmentTypes[$mark->assessment_type_id] ?? null;
            if (! $type) {
                continue;
            }
            $max = (float) $type->max_mark;
            $weight = ($type->weight_percentage ?? 0) > 0 ? (float) $type->weight_percentage : 1;
            $pct = $max > 0 ? ((float) $mark->marks_obtained / $max) * 100 : (float) $mark->marks_obtained;

            if (! isset($subjectByEnrollment[$mark->enrollment_id][$mark->subject_id])) {
                $subjectByEnrollment[$mark->enrollment_id][$mark->subject_id] = [0.0, 0.0];
            }
            $subjectByEnrollment[$mark->enrollment_id][$mark->subject_id][0] += $pct * $weight;
            $subjectByEnrollment[$mark->enrollment_id][$mark->subject_id][1] += $weight;
        }

        $subjectLabels = Subject::whereIn('id', collect($subjectByEnrollment)->flatMap(fn ($s) => array_keys($s))->unique()->values())
            ->pluck('name', 'id')
            ->all();

        $results = [];
        foreach ($enrollments as $enrollment) {
            $subjectResults = [];
            foreach ($subjectByEnrollment[$enrollment->id] ?? [] as $sid => [$sum, $used]) {
                if ($used > 0) {
                    $subjectResults[$sid] = $sum / $used;
                }
            }

            if (empty($subjectResults)) {
                continue;
            }

            $overall = round(array_sum($subjectResults) / count($subjectResults), 1);

            $results[] = [
                'enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'student_name' => $enrollment->student?->full_name ?? 'Student #'.$enrollment->student_id,
                'section' => trim(($enrollment->section?->course?->name ?? '').' '.($enrollment->section?->name ?? '')),
                'course_id' => $enrollment->section?->course_id,
                'section_id' => $enrollment->section_id,
                'subject_results' => $subjectResults,
                'overall' => $overall,
                'grade' => $this->letterGrade($overall),
            ];
        }

        $overalls = collect($results)->pluck('overall')->values();

        // Subject summary (class averages, pass rates etc.).
        $subjectStats = [];
        foreach ($subjectLabels as $sid => $name) {
            $taken = [];
            foreach ($results as $row) {
                if (isset($row['subject_results'][$sid])) {
                    $taken[] = $row['subject_results'][$sid];
                }
            }
            if (empty($taken)) {
                continue;
            }
            $subjectStats[] = [
                'name' => $name,
                'students' => count($taken),
                'avg' => round(array_sum($taken) / count($taken), 1),
                'pass_rate' => round(collect($taken)->filter(fn ($v) => $v >= 50)->count() / count($taken) * 100, 1),
                'highest' => round(max($taken), 1),
                'lowest' => round(min($taken), 1),
            ];
        }
        usort($subjectStats, fn ($a, $b) => $b['avg'] <=> $a['avg']);

        // Class stream summary (only meaningful with more than one stream in scope).
        $sectionStats = [];
        if ($courseId === null && $sectionId === null) {
            $grouped = collect($results)->groupBy('section');
            foreach ($grouped as $sectionName => $rows) {
                if ($sectionName === '' || $rows->isEmpty()) {
                    continue;
                }
                $avgs = $rows->pluck('overall');
                $sectionStats[] = [
                    'name' => $sectionName,
                    'students' => $rows->count(),
                    'avg' => round($avgs->avg(), 1),
                    'pass_rate' => round($avgs->filter(fn ($v) => $v >= 50)->count() / $avgs->count() * 100, 1),
                ];
            }
            usort($sectionStats, fn ($a, $b) => $b['avg'] <=> $a['avg']);
        }

        $gradeDistribution = $this->gradeDistribution($results);
        $bandDistribution = $this->bandDistribution($results);

        $topLearners = collect($results)->sortByDesc('overall')->take(10)->values()->map(function ($row, $index) {
            return ['rank' => $index + 1] + $row;
        })->all();

        $supportLearners = collect($results)
            ->filter(fn ($row) => $row['overall'] < 40)
            ->sortBy('overall')
            ->values()
            ->map(function ($row, $index) {
                return ['rank' => $index + 1] + $row;
            })
            ->all();

        return [
            'total' => count($results),
            'overalls' => $overalls,
            'assessmentCount' => $marks->pluck('assessment_type_id')->unique()->count(),
            'subjectCount' => count($subjectLabels),
            'subjectStats' => $subjectStats,
            'sectionStats' => $sectionStats,
            'gradeDistribution' => $gradeDistribution,
            'bandDistribution' => $bandDistribution,
            'topLearners' => $topLearners,
            'supportLearners' => $supportLearners,
        ];
    }

    protected function averageOf(Collection $values): ?float
    {
        if ($values->isEmpty()) {
            return null;
        }

        return round($values->avg(), 1);
    }

    protected function letterGrade(float $overall): string
    {
        return match (true) {
            $overall >= 80 => 'A',
            $overall >= 70 => 'B',
            $overall >= 60 => 'C',
            $overall >= 50 => 'D',
            $overall >= 40 => 'E',
            default => 'U',
        };
    }

    protected function gradeDistribution(array $results): array
    {
        $grades = [
            'A' => ['label' => 'A (80-100)', 'count' => 0, 'color' => '#16a34a'],
            'B' => ['label' => 'B (70-79)', 'count' => 0, 'color' => '#22c55e'],
            'C' => ['label' => 'C (60-69)', 'count' => 0, 'color' => '#0ea5e9'],
            'D' => ['label' => 'D (50-59)', 'count' => 0, 'color' => '#eab308'],
            'E' => ['label' => 'E (40-49)', 'count' => 0, 'color' => '#f97316'],
            'U' => ['label' => 'U (<40)', 'count' => 0, 'color' => '#ef4444'],
        ];

        foreach ($results as $row) {
            if (isset($grades[$row['grade']])) {
                $grades[$row['grade']]['count']++;
            }
        }

        return array_values($grades);
    }

    protected function bandDistribution(array $results): array
    {
        $bands = [
            ['label' => '0-19', 'count' => 0, 'color' => '#ef4444'],
            ['label' => '20-39', 'count' => 0, 'color' => '#f97316'],
            ['label' => '40-59', 'count' => 0, 'color' => '#eab308'],
            ['label' => '60-79', 'count' => 0, 'color' => '#0ea5e9'],
            ['label' => '80-100', 'count' => 0, 'color' => '#16a34a'],
        ];

        foreach ($results as $row) {
            $overall = $row['overall'];
            if ($overall >= 80) {
                $bands[4]['count']++;
            } elseif ($overall >= 60) {
                $bands[3]['count']++;
            } elseif ($overall >= 40) {
                $bands[2]['count']++;
            } elseif ($overall >= 20) {
                $bands[1]['count']++;
            } else {
                $bands[0]['count']++;
            }
        }

        return $bands;
    }

    protected function scopeLabel(): string
    {
        if ($this->sectionId) {
            $section = Section::with('course')->find($this->sectionId);

            return trim(($section?->course?->name ?? '').' '.($section?->name ?? ''));
        }

        if ($this->courseId) {
            return Course::find($this->courseId)?->name ?? __('Level');
        }

        return __('Whole School');
    }
}