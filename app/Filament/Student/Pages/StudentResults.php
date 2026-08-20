<?php

namespace App\Filament\Student\Pages;

use App\Filament\Student\Resources\HomeworkResource;
use Filament\Pages\Page;
use Modules\Academics\Models\AcademicReport;
use Modules\Academics\Models\AssessmentMark;

class StudentResults extends Page
{
    protected static string $view = 'filament.student.pages.student-results';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Academics';

    protected static ?string $navigationLabel = 'Results & Continuous Assessment';

    protected static ?string $title = 'Exam Results & Continuous Assessment';

    protected static ?string $slug = 'my-results';

    public static function getNavigationLabel(): string
    {
        return __('Results & Continuous Assessment');
    }

    protected function getViewData(): array
    {
        $student = HomeworkResource::currentStudent();

        $reports = collect();
        $assessmentMarks = collect();

        if ($student) {
            $reports = AcademicReport::where('student_id', $student->id)
                ->with(['section.course', 'term'])
                ->orderByDesc('term_id')
                ->get();

            $enrollmentIds = $student->enrollments()->pluck('id');

            $assessmentMarks = AssessmentMark::whereIn('enrollment_id', $enrollmentIds)
                ->with(['subject', 'assessmentType'])
                ->orderBy('subject_id')
                ->get();
        }

        return [
            'student' => $student,
            'reports' => $reports,
            'assessmentMarks' => $assessmentMarks,
        ];
    }
}
