<?php

namespace Modules\DigitalAssessment\Services;

use Illuminate\Support\Collection;
use Modules\DigitalAssessment\Models\DigitalAssessment;
use Modules\DigitalAssessment\Models\DigitalAssessmentAttempt;
use Modules\DigitalAssessment\Models\DigitalAssessmentResponse;

class ManualMarkingService
{
    public function getMarkingQueue(?int $assessmentId = null, ?int $schoolId = null): Collection
    {
        $query = DigitalAssessmentResponse::query()
            ->whereNull('marked_at')
            ->whereNotNull('learner_answer')
            ->whereHas('question', fn ($q) => $q
                // Auto-markable types are excluded UNLESS the teacher switched
                // them to manual marking on the question itself.
                ->where(fn ($q2) => $q2
                    ->whereIn('question_type', ['short_answer', 'fill_in_the_blank', 'essay', 'file_upload'])
                    ->orWhere('manual_marking', true)))
            ->with([
                'question',
                'attempt.student',
                'attempt.assessment.subject',
                'attempt.assessment',
            ]);

        if ($assessmentId) {
            $query->whereHas('attempt', fn ($q) => $q->where('digital_assessment_id', $assessmentId));
        }

        if ($schoolId) {
            $query->whereHas('attempt', fn ($q) => $q->where('school_id', $schoolId));
        }

        return $query->orderBy('attempt_id')->get();
    }

    public function getMarkingQueueForAssessment(DigitalAssessment $assessment): Collection
    {
        return DigitalAssessmentResponse::query()
            ->whereHas('attempt', fn ($q) => $q->where('digital_assessment_id', $assessment->id))
            ->whereNull('marked_at')
            ->whereNotNull('learner_answer')
            ->with(['question', 'attempt.student'])
            ->orderBy('attempt_id')
            ->get();
    }

    public function getQueueStats(DigitalAssessment $assessment): array
    {
        $responses = DigitalAssessmentResponse::query()
            ->whereHas('attempt', fn ($q) => $q->where('digital_assessment_id', $assessment->id))
            ->with('question')
            ->get();

        $subjective = $responses->filter(fn ($r) => ! $r->question?->isAutoMarkable());

        return [
            'total_responses' => $responses->count(),
            'subjective_count' => $subjective->count(),
            'needs_marking' => $subjective->whereNull('marked_at')->whereNotNull('learner_answer')->count(),
            'marked' => $subjective->whereNotNull('marked_at')->count(),
            'unanswered' => $subjective->whereNull('learner_answer')->count(),
        ];
    }

    public function markResponse(DigitalAssessmentResponse $response, float $marksAwarded, ?string $feedback = null, ?int $markedById = null): DigitalAssessmentResponse
    {
        $maxMarks = (float) $response->marks_possible;

        if ($marksAwarded < 0 || $marksAwarded > $maxMarks) {
            throw new \DomainException("Marks must be between 0 and {$maxMarks}.");
        }

        $response->update([
            'marks_awarded' => $marksAwarded,
            'teacher_feedback' => $feedback,
            'marked_by_id' => $markedById ?? auth()->id(),
            'marked_at' => now(),
        ]);

        $this->recalculateAttemptScore($response->attempt);

        return $response->fresh();
    }

    public function bulkMark(Collection $responses, array $marks, ?string $feedback = null, ?int $markedById = null): int
    {
        $marked = 0;

        foreach ($responses as $response) {
            $markValue = $marks[$response->id] ?? null;

            if ($markValue !== null) {
                $this->markResponse($response, (float) $markValue, $feedback, $markedById);
                $marked++;
            }
        }

        return $marked;
    }

    public function recalculateAttemptScore(DigitalAssessmentAttempt $attempt): void
    {
        $responses = $attempt->responses;

        $autoScore = $responses->whereNotNull('marks_awarded')->sum('marks_awarded');
        $totalPossible = $responses->sum('marks_possible');

        $percentage = $totalPossible > 0 ? round(($autoScore / $totalPossible) * 100, 2) : 0;

        $attempt->update([
            'score' => $autoScore,
            'auto_score' => $responses->filter(fn ($r) => $r->question?->isAutoMarkable())->sum('marks_awarded'),
            'manual_score' => $responses->filter(fn ($r) => ! $r->question?->isAutoMarkable() && $r->marked_at)->sum('marks_awarded'),
            'marks_obtained' => $autoScore,
            'final_score' => $autoScore,
            'percentage' => $percentage,
        ]);

        if ($percentage >= ($attempt->assessment?->pass_mark ?? 0)) {
            $attempt->update(['status' => 'graded']);
        }
    }

    public function isFullyMarked(DigitalAssessmentAttempt $attempt): bool
    {
        return $attempt->responses()
            ->whereNotNull('learner_answer')
            ->whereNull('marked_at')
            ->whereHas('question', fn ($q) => $q
                ->where(fn ($q2) => $q2
                    ->whereIn('question_type', ['short_answer', 'fill_in_the_blank', 'essay', 'file_upload'])
                    ->orWhere('manual_marking', true)))
            ->count() === 0;
    }

    public function publishGrades(DigitalAssessment $assessment): int
    {
        $attempts = $assessment->attempts()
            ->whereIn('status', ['submitted', 'graded'])
            ->get();

        $count = 0;

        foreach ($attempts as $attempt) {
            if ($this->isFullyMarked($attempt)) {
                $attempt->update(['status' => 'graded']);
                $count++;
            }
        }

        return $count;
    }
}
