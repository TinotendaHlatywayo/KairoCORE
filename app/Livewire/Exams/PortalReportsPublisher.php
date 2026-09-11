<?php

namespace App\Livewire\Exams;

use Filament\Notifications\Notification;
use Livewire\Component;
use Modules\Academics\Models\AcademicReport;
use Modules\Academics\Models\AssessmentType;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\ReportTemplate;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Term;

class PortalReportsPublisher extends Component
{
    public string $scope = 'school';

    public ?int $courseId = null;

    public ?int $sectionId = null;

    public ?int $termId = null;

    protected function schoolId(): ?int
    {
        $tenant = app('current_tenant');

        return $tenant?->id ?? filament()->getTenant()?->id;
    }

    public function updatedScope(): void
    {
        $this->courseId = null;
        $this->sectionId = null;
    }

    public function updatedCourseId(): void
    {
        $this->sectionId = null;
    }

    public function render()
    {
        $schoolId = $this->schoolId();

        $candidateReports = $this->matchedReportsQuery()->count();
        $publishedReports = $this->matchedReportsQuery()->where('status', 'published')->count();

        return view('livewire.exams.portal-reports-publisher', [
            'schoolId' => $schoolId,
            'candidateReports' => $candidateReports,
            'publishedReports' => $publishedReports,
            'unpublishedReports' => max(0, $candidateReports - $publishedReports),
            'terms' => Term::where('school_id', $schoolId)->orderByDesc('id')->get(),
            'courses' => Course::where('school_id', $schoolId)->orderBy('name')->get(),
            'sections' => Section::where('school_id', $schoolId)->orderBy('name')->get(),
            'assessmentTypes' => AssessmentType::where('school_id', $schoolId)->orderBy('name')->get(),
            'includedIds' => $this->reportCardIncludedAssessmentIds($schoolId),
            'publishedTypeIds' => AssessmentType::where('school_id', $schoolId)
                ->where('status', 'published')
                ->pluck('id')
                ->all(),
        ]);
    }

    protected function matchedReportsQuery()
    {
        $query = AcademicReport::query()->where('school_id', $this->schoolId());

        if ($this->termId) {
            $query->where('term_id', $this->termId);
        }

        if ($this->scope === 'course' && $this->courseId) {
            $query->whereHas('section', fn ($q) => $q->where('course_id', $this->courseId));
        } elseif ($this->scope === 'section' && $this->sectionId) {
            $query->where('section_id', $this->sectionId);
        }

        return $query;
    }

    protected function reportCardIncludedAssessmentIds(int $schoolId): array
    {
        $template = ReportTemplate::where('school_id', $schoolId)->where('is_active', true)->first();
        $ids = $template?->layout_config['included_assessments'] ?? [];

        if (empty($ids)) {
            $ids = AssessmentType::where('school_id', $schoolId)->pluck('id')->toArray();
        }

        return $ids;
    }

    public function publishReports(): void
    {
        $schoolId = $this->schoolId();
        $termId = $this->termId ?: Term::where('school_id', $schoolId)->orderByDesc('id')->first()?->id;

        $studentQuery = \Modules\Students\Models\Student::query()->where('school_id', $schoolId)->where('status', 'active');
        if ($this->scope === 'course' && $this->courseId) {
            $studentQuery->whereHas('enrollments.section', fn ($q) => $q->where('course_id', $this->courseId));
        } elseif ($this->scope === 'section' && $this->sectionId) {
            $studentQuery->whereHas('enrollments', fn ($q) => $q->where('section_id', $this->sectionId));
        }

        $students = $studentQuery->get();
        $count = 0;
        $skipped = 0;

        foreach ($students as $student) {
            if (! $student->user_id) {
                $skipped++;
                continue;
            }
            $sectionId = $student->currentEnrollment?->section_id ?? $student->enrollments()->latest()->first()?->section_id;
            if (! $sectionId || ! $termId) {
                $skipped++;
                continue;
            }

            $report = AcademicReport::firstOrCreate(
                [
                    'school_id' => $schoolId,
                    'student_id' => $student->id,
                    'term_id' => $termId,
                    'section_id' => $sectionId,
                ],
                [
                    'status' => 'draft',
                    'unhu_competencies' => [],
                ]
            );

            $report->update(['status' => 'published']);
            $count++;
        }

        $body = trans_choice(':count report card(s) published to the student portal.', $count, ['count' => $count]);
        if ($skipped > 0) {
            $body .= ' '.__(':skipped skipped (portal not created yet).', ['skipped' => $skipped]);
        }

        Notification::make()
            ->success()
            ->title(__('Reports Published'))
            ->body($body)
            ->send();
    }

    public function unpublishReports(): void
    {
        $count = $this->matchedReportsQuery()->where('status', 'published')->update(['status' => 'approved']);

        Notification::make()
            ->warning()
            ->title(__('Reports Unpublished'))
            ->body(trans_choice(':count report card(s) removed from the student portal.', $count, ['count' => $count]))
            ->send();
    }

    public function publishAssessmentType(int $id): void
    {
        AssessmentType::where('id', $id)->where('school_id', $this->schoolId())->update(['status' => 'published']);
    }

    public function unpublishAssessmentType(int $id): void
    {
        AssessmentType::where('id', $id)->where('school_id', $this->schoolId())->update(['status' => 'draft']);
    }
}