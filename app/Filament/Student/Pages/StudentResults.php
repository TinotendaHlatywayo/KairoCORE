<?php

namespace App\Filament\Student\Pages;

use App\Filament\Student\Resources\HomeworkResource;
use Filament\Pages\Page;
use Modules\Academics\Models\AcademicReport;
use Modules\Academics\Models\AssessmentMark;
use Modules\Academics\Models\Term;
use Modules\Academics\Models\Subject;

class StudentResults extends Page
{
    protected static string $view = 'filament.student.pages.student-results';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Academics';

    protected static ?string $navigationLabel = 'Results & Continuous Assessment';

    protected static ?string $title = 'Exam Results & Continuous Assessment';

    protected static ?string $slug = 'my-results';

    public ?int $selectedTermId = null;
    public ?int $selectedSubjectId = null;

    public static function getNavigationLabel(): string
    {
        return __('Results & Continuous Assessment');
    }

    public function mount(): void
    {
        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id;
        $activeTerm = Term::where('school_id', $schoolId)
            ->whereHas('academicYear', fn ($q) => $q->where('is_active', true))
            ->orderBy('is_active', 'desc')
            ->orderByDesc('id')
            ->first() ?? Term::where('school_id', $schoolId)->orderByDesc('id')->first();

        $this->selectedTermId = $activeTerm?->id;
    }

    protected function getViewData(): array
    {
        $student = HomeworkResource::currentStudent();
        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id;

        $terms = Term::where('school_id', $schoolId)->with('academicYear')->orderByDesc('id')->get();
        $subjects = Subject::where('school_id', $schoolId)->orderBy('name')->get();

        $reports = collect();
        $assessmentMarks = collect();

        if ($student) {
            $reportQuery = AcademicReport::where('student_id', $student->id)
                ->whereIn('status', ['published', 'approved'])
                ->with(['section.course', 'term.academicYear']);

            if ($this->selectedTermId) {
                $reportQuery->where('term_id', $this->selectedTermId);
            }
            $reports = $reportQuery->orderByDesc('term_id')->get();

            $enrollmentIds = $student->enrollments()->pluck('id');

            $markQuery = AssessmentMark::whereIn('enrollment_id', $enrollmentIds)
                ->whereHas('assessmentType', fn ($q) => $q->where('status', 'published'))
                ->with(['subject', 'assessmentType.term']);

            if ($this->selectedTermId) {
                $markQuery->whereHas('assessmentType', fn ($q) => $q->where('term_id', $this->selectedTermId));
            }
            if ($this->selectedSubjectId) {
                $markQuery->where('subject_id', $this->selectedSubjectId);
            }

            $assessmentMarks = $markQuery->orderBy('subject_id')->get();
        }

        return [
            'student' => $student,
            'reports' => $reports,
            'assessmentMarks' => $assessmentMarks,
            'terms' => $terms,
            'subjects' => $subjects,
            'selectedTermId' => $this->selectedTermId,
            'selectedSubjectId' => $this->selectedSubjectId,
        ];
    }
}
