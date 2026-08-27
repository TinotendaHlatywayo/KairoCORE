<?php

namespace Modules\DigitalAssessment\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\DigitalAssessment\Models\DigitalAssessment;
use Modules\DigitalAssessment\Models\DigitalAssessmentResponse;
use Modules\DigitalAssessment\Models\QuestionAnalytics;
use Modules\DigitalAssessment\Models\QuestionBank;

class QuestionAnalyticsService
{
    public function recalculateForQuestion(QuestionBank $question): QuestionAnalytics
    {
        $analytics = QuestionAnalytics::firstOrCreate(
            [
                'school_id' => $question->school_id,
                'question_bank_id' => $question->id,
            ],
            [
                'total_attempts' => 0,
                'correct_count' => 0,
                'incorrect_count' => 0,
                'skipped_count' => 0,
                'percentage_correct' => 0,
                'average_response_time_seconds' => 0,
                'average_confidence' => 0,
            ]
        );

        $analytics->recalculate();

        return $analytics->fresh();
    }

    public function recalculateForAssessment(DigitalAssessment $assessment): void
    {
        $questionIds = $assessment->questions()->pluck('question_bank_id');

        foreach ($questionIds as $qId) {
            $question = QuestionBank::find($qId);
            if ($question) {
                $this->recalculateForQuestion($question);
            }
        }
    }

    public function recalculateAll(?int $schoolId = null): int
    {
        $query = QuestionBank::query();

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $questions = $query->get();
        $count = 0;

        foreach ($questions as $question) {
            $this->recalculateForQuestion($question);
            $count++;
        }

        return $count;
    }

    public function getQuestionStats(QuestionBank $question): array
    {
        $analytics = $question->analytics;

        return [
            'total_attempts' => $analytics?->total_attempts ?? 0,
            'correct_count' => $analytics?->correct_count ?? 0,
            'incorrect_count' => $analytics?->incorrect_count ?? 0,
            'skipped_count' => $analytics?->skipped_count ?? 0,
            'percentage_correct' => $analytics?->percentage_correct ?? 0,
            'average_response_time' => $analytics?->average_response_time_seconds ?? 0,
            'average_confidence' => $analytics?->average_confidence ?? 0,
        ];
    }

    public function getDifficultQuestions(?int $schoolId = null, int $threshold = 30): Collection
    {
        return QuestionAnalytics::query()
            ->where('total_attempts', '>=', 5)
            ->where('percentage_correct', '<', $threshold)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with('question')
            ->orderBy('percentage_correct', 'asc')
            ->get();
    }

    public function getEasyQuestions(?int $schoolId = null, int $threshold = 90): Collection
    {
        return QuestionAnalytics::query()
            ->where('total_attempts', '>=', 5)
            ->where('percentage_correct', '>', $threshold)
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with('question')
            ->orderBy('percentage_correct', 'desc')
            ->get();
    }

    public function getSubjectPerformance(int $subjectId): array
    {
        $questions = QuestionBank::where('subject_id', $subjectId)
            ->with('analytics')
            ->get();

        $withAnalytics = $questions->filter(fn ($q) => $q->analytics);

        return [
            'total_questions' => $questions->count(),
            'questions_with_data' => $withAnalytics->count(),
            'average_success_rate' => $withAnalytics->count() > 0
                ? round($withAnalytics->avg(fn ($q) => $q->analytics->percentage_correct), 2)
                : 0,
            'hardest_topic' => $questions->groupBy('topic')
                ->map(fn ($qs) => $qs->filter(fn ($q) => $q->analytics)
                    ->avg(fn ($q) => $q->analytics->percentage_correct))
                ->sort()
                ->keys()
                ->first() ?? 'N/A',
            'by_difficulty' => $questions->groupBy('difficulty')
                ->map(fn ($qs) => [
                    'count' => $qs->count(),
                    'avg_success' => round($qs->filter(fn ($q) => $q->analytics)
                        ->avg(fn ($q) => $q->analytics->percentage_correct), 2),
                ])
                ->toArray(),
        ];
    }

    public function getDashboardStats(?int $schoolId = null): array
    {
        $analyticsQuery = QuestionAnalytics::query();

        if ($schoolId) {
            $analyticsQuery->where('school_id', $schoolId);
        }

        $all = $analyticsQuery->get();

        return [
            'total_questions_with_data' => $all->count(),
            'overall_success_rate' => $all->count() > 0
                ? round($all->avg('percentage_correct'), 2)
                : 0,
            'total_responses' => $all->sum('total_attempts'),
            'hardest_questions' => $this->getDifficultQuestions($schoolId, 30)->take(5),
            'easiest_questions' => $this->getEasyQuestions($schoolId, 90)->take(5),
        ];
    }
}
