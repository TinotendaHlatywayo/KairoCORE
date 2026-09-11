<?php

namespace App\Filament\Student\Pages;

use App\Filament\Student\Resources\HomeworkResource;
use Filament\Pages\Page;
use Modules\Admin\Models\SystemSetting;
use Modules\Academics\Models\AssessmentMark;

class PerformanceAnalyticsPage extends Page
{
    protected static string $view = 'filament.student.pages.performance-analytics';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Academics';

    protected static ?string $navigationLabel = 'Performance Analytics & Intelligence';

    protected static ?string $title = 'Performance Analytics & Intelligence';

    protected static ?string $slug = 'performance-analytics';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('Performance Analytics & Intelligence');
    }

    public static function canAccess(): bool
    {
        $enabled = SystemSetting::get('student_portal', 'analytics_enabled', true);
        return (bool) $enabled && auth()->check();
    }

    protected function getViewData(): array
    {
        $student = HomeworkResource::currentStudent();
        $enrollmentIds = $student ? $student->enrollments()->pluck('id') : collect();

        $marks = AssessmentMark::whereIn('enrollment_id', $enrollmentIds)
            ->with(['subject', 'assessmentType'])
            ->get();

        $bySubject = $marks->groupBy(fn ($m) => $m->subject?->name ?? __('Unknown Subject'))
            ->map(function ($subjectMarks) {
                return [
                    'avg' => round($subjectMarks->avg('marks_obtained'), 1),
                    'max' => $subjectMarks->max('marks_obtained'),
                    'min' => $subjectMarks->min('marks_obtained'),
                    'count' => $subjectMarks->count(),
                    'items' => $subjectMarks->groupBy(fn ($m) => $m->assessmentType?->name ?? 'Assessment'),
                ];
            });

        $overallAvg = $marks->isNotEmpty() ? round($marks->avg('marks_obtained'), 1) : 0;
        $bestSubject = $bySubject->isNotEmpty() ? $bySubject->sortDesc()->keys()->first() : __('N/A');

        return [
            'student' => $student,
            'marks' => $marks,
            'bySubject' => $bySubject,
            'overallAvg' => $overallAvg,
            'bestSubject' => $bestSubject,
        ];
    }
}
