<?php

namespace Modules\DigitalAssessment\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\DigitalAssessment\Enums\MasteryLabel;
use Modules\DigitalAssessment\Models\DigitalAssessmentResponse;
use Modules\DigitalAssessment\Models\LearnerMastery;
use Modules\Students\Models\Student;

class MasteryService
{
    public function updateMasteryForStudent(int $studentId, int $enrollmentId, int $subjectId, string $topic, ?string $subtopic = null): LearnerMastery
    {
        $responses = DigitalAssessmentResponse::query()
            ->whereHas('attempt', fn ($q) => $q->where('student_id', $studentId))
            ->whereHas('question', fn ($q) => $q->where('subject_id', $subjectId)->where('topic', $topic))
            ->when($subtopic, fn ($q) => $q->whereHas('question', fn ($qq) => $qq->where('subtopic', $subtopic)))
            ->whereNotNull('marks_awarded')
            ->get();

        $total = $responses->count();
        $correct = $responses->where('is_correct', true)->count();

        $score = $total > 0 ? round(($correct / $total) * 100, 2) : 0;
        $label = MasteryLabel::fromScore($score);

        return LearnerMastery::updateOrCreate(
            [
                'school_id' => current_tenant()?->id,
                'enrollment_id' => $enrollmentId,
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'topic' => $topic,
                'subtopic' => $subtopic,
            ],
            [
                'mastery_score' => $score,
                'mastery_label' => $label,
                'total_assessments' => $responses->pluck('attempt_id')->unique()->count(),
                'correct_responses' => $correct,
                'total_responses' => $total,
                'last_assessed_at' => $responses->max('answered_at'),
            ]
        );
    }

    public function updateMasteryForAttempt(int $attemptId): void
    {
        $responses = DigitalAssessmentResponse::query()
            ->where('digital_assessment_attempt_id', $attemptId)
            ->with(['question', 'attempt'])
            ->get();

        if ($responses->isEmpty()) {
            return;
        }

        $attempt = $responses->first()->attempt;

        $grouped = $responses->groupBy(fn ($r) => $r->question?->topic ?? 'General');

        foreach ($grouped as $topic => $topicResponses) {
            $this->updateMasteryForStudent(
                $attempt->student_id,
                $attempt->enrollment_id,
                $attempt->assessment->subject_id,
                $topic
            );
        }
    }

    public function getStudentMastery(int $studentId, ?int $subjectId = null): Collection
    {
        return LearnerMastery::where('student_id', $studentId)
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->with(['subject'])
            ->orderBy('topic')
            ->get();
    }

    public function getClassMasterySummary(int $sectionId, int $subjectId): array
    {
        $students = Student::whereHas('enrollments', fn ($q) => $q->where('section_id', $sectionId))->pluck('id');

        $masteries = LearnerMastery::whereIn('student_id', $students)
            ->where('subject_id', $subjectId)
            ->get();

        $byTopic = $masteries->groupBy('topic')->map(function ($topicMasteries, $topic) {
            return [
                'topic' => $topic,
                'student_count' => $topicMasteries->count(),
                'avg_score' => round($topicMasteries->avg('mastery_score'), 2),
                'mastery_breakdown' => [
                    MasteryLabel::Mastered->value => $topicMasteries->where('mastery_label', MasteryLabel::Mastered)->count(),
                    MasteryLabel::Proficient->value => $topicMasteries->where('mastery_label', MasteryLabel::Proficient)->count(),
                    MasteryLabel::Developing->value => $topicMasteries->where('mastery_label', MasteryLabel::Developing)->count(),
                    MasteryLabel::Beginning->value => $topicMasteries->where('mastery_label', MasteryLabel::Beginning)->count(),
                ],
            ];
        })->values()->toArray();

        return [
            'total_students' => $students->count(),
            'total_topics' => $masteries->pluck('topic')->unique()->count(),
            'overall_avg' => $masteries->count() > 0 ? round($masteries->avg('mastery_score'), 2) : 0,
            'by_topic' => $byTopic,
        ];
    }

    public function getWeakTopics(int $studentId, int $subjectId, int $limit = 5): Collection
    {
        return LearnerMastery::where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->orderBy('mastery_score', 'asc')
            ->limit($limit)
            ->get();
    }

    public function getStrongTopics(int $studentId, int $subjectId, int $limit = 5): Collection
    {
        return LearnerMastery::where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->orderBy('mastery_score', 'desc')
            ->limit($limit)
            ->get();
    }
}
