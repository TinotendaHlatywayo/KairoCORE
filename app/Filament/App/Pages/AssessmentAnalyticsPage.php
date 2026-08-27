<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Modules\DigitalAssessment\Models\DigitalAssessment;
use Modules\DigitalAssessment\Services\ManualMarkingService;
use Modules\DigitalAssessment\Services\QuestionAnalyticsService;
use Modules\DigitalAssessment\Services\MasteryService;

class AssessmentAnalyticsPage extends Page
{
    protected static string $view = 'filament.app.pages.assessment-analytics';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Exams & Grading';

    protected static ?int $navigationSort = 64;

    protected static ?string $title = 'Assessment Analytics';

    protected static bool $shouldRegisterNavigation = false;

    public ?int $assessmentId = null;
    public array $assessmentData = [];
    public array $questionStats = [];
    public array $classMastery = [];
    public array $markingStats = [];

    public static function getRoutePath(): string
    {
        return 'assessment-analytics/{assessment}';
    }

    public function mount(int $assessment): void
    {
        $this->assessmentId = $assessment;

        $da = DigitalAssessment::with(['subject', 'questions.question', 'attempts'])->find($assessment);

        if (! $da) {
            return;
        }

        $analyticsService = app(QuestionAnalyticsService::class);
        $analyticsService->recalculateForAssessment($da);

        $attempts = $da->attempts()->complete()->get();

        $this->assessmentData = [
            'title' => $da->title,
            'subject' => $da->subject?->name ?? 'N/A',
            'total_questions' => $da->questions()->count(),
            'total_attempts' => $attempts->count(),
            'avg_score' => $attempts->count() > 0 ? round($attempts->avg('percentage'), 1) : 0,
            'avg_marks' => $attempts->count() > 0 ? round($attempts->avg('marks_obtained'), 2) : 0,
            'pass_rate' => $attempts->count() > 0
                ? round(($attempts->where('percentage', '>=', $da->pass_mark)->count() / $attempts->count()) * 100, 1)
                : 0,
            'highest_score' => $attempts->max('percentage') ?? 0,
            'lowest_score' => $attempts->min('percentage') ?? 0,
            'pass_mark' => $da->pass_mark,
            'total_marks' => $da->getCalculatedTotalMarks(),
            'status' => $da->status->label(),
        ];

        $this->questionStats = $da->questions()->with(['question.analytics'])->get()
            ->map(fn ($dq) => [
                'title' => $dq->question?->title ?? 'N/A',
                'type' => $dq->question?->question_type?->label() ?? 'N/A',
                'difficulty' => $dq->question?->difficulty?->label() ?? 'N/A',
                'difficulty_color' => $dq->question?->difficulty?->color() ?? 'gray',
                'marks' => $dq->getEffectiveMarks(),
                'total_attempts' => $dq->question?->analytics?->total_attempts ?? 0,
                'correct_count' => $dq->question?->analytics?->correct_count ?? 0,
                'success_rate' => $dq->question?->analytics?->percentage_correct ?? 0,
                'avg_response_time' => $dq->question?->analytics?->average_response_time_seconds ?? 0,
            ])
            ->toArray();

        $markingService = app(ManualMarkingService::class);
        $this->markingStats = $markingService->getQueueStats($da);

        $scoreDistribution = $attempts->groupBy(fn ($a) => match (true) {
            $a->percentage >= 80 => '80-100%',
            $a->percentage >= 60 => '60-79%',
            $a->percentage >= 40 => '40-59%',
            $a->percentage >= 20 => '20-39%',
            default => '0-19%',
        })->map(fn ($group) => $group->count())->toArray();

        $this->assessmentData['score_distribution'] = $scoreDistribution;

        $this->classMastery = app(MasteryService::class)
            ->getClassMasterySummary(
                $da->section_id ?? 0,
                $da->subject_id
            );
    }
}
