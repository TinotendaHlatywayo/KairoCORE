<?php

namespace Modules\DigitalAssessment\Services;

use Illuminate\Support\Collection;
use Modules\DigitalAssessment\Enums\QuestionDifficulty;
use Modules\DigitalAssessment\Enums\QuestionStatus;
use Modules\DigitalAssessment\Enums\QuestionType;
use Modules\DigitalAssessment\Models\QuestionBank;
use Modules\DigitalAssessment\Models\QuestionAnalytics;

class QuestionBankService
{
    public function createQuestion(array $data): QuestionBank
    {
        $data['school_id'] = current_tenant()?->id ?? auth()->user()->school_id;
        $data['created_by_id'] = auth()->id();

        if (! isset($data['status'])) {
            $data['status'] = QuestionStatus::Draft;
        }

        return QuestionBank::create($data);
    }

    public function updateQuestion(QuestionBank $question, array $data): QuestionBank
    {
        $question->update($data);

        return $question->fresh();
    }

    public function publishQuestion(QuestionBank $question): QuestionBank
    {
        $question->update(['status' => QuestionStatus::Published]);

        return $question->fresh();
    }

    public function archiveQuestion(QuestionBank $question): QuestionBank
    {
        $question->update(['status' => QuestionStatus::Archived]);

        return $question->fresh();
    }

    public function bulkPublish(array $ids): int
    {
        return QuestionBank::whereIn('id', $ids)
            ->where('status', '!=', QuestionStatus::Published)
            ->update(['status' => QuestionStatus::Published]);
    }

    public function bulkArchive(array $ids): int
    {
        return QuestionBank::whereIn('id', $ids)
            ->update(['status' => QuestionStatus::Archived]);
    }

    public function duplicateQuestion(QuestionBank $question): QuestionBank
    {
        $data = $question->toArray();

        unset($data['id'], $data['usage_count'], $data['last_used_at'], $data['created_at'], $data['updated_at']);

        $data['title'] = $data['title'] . ' (Copy)';
        $data['status'] = QuestionStatus::Draft;
        $data['created_by_id'] = auth()->id();

        return QuestionBank::create($data);
    }

    public function getQuestionsBySubject(int $subjectId, ?QuestionType $type = null): Collection
    {
        $query = QuestionBank::forSubject($subjectId)->published();

        if ($type) {
            $query->ofType($type);
        }

        return $query->get();
    }

    public function getRandomQuestions(int $subjectId, int $count, array $filters = []): Collection
    {
        $query = QuestionBank::forSubject($subjectId)
            ->published()
            ->inRandomOrder();

        if (isset($filters['difficulty'])) {
            $query->where('difficulty', $filters['difficulty']);
        }

        if (isset($filters['question_type'])) {
            $query->where('question_type', $filters['question_type']);
        }

        if (isset($filters['topic'])) {
            $query->where('topic', $filters['topic']);
        }

        return $query->limit($count)->get();
    }

    public function getTopicsBySubject(int $subjectId): array
    {
        return QuestionBank::where('school_id', current_tenant()?->id)
            ->where('subject_id', $subjectId)
            ->whereNotNull('topic')
            ->where('topic', '!=', '')
            ->distinct()
            ->pluck('topic')
            ->sort()
            ->values()
            ->toArray();
    }

    public function getQuestionStats(QuestionBank $question): array
    {
        $analytics = $question->analytics;

        return [
            'total_attempts' => $analytics?->total_attempts ?? 0,
            'correct_count' => $analytics?->correct_count ?? 0,
            'percentage_correct' => $analytics?->percentage_correct ?? 0,
            'average_response_time' => $analytics?->average_response_time_seconds ?? 0,
            'usage_count' => $question->usage_count,
        ];
    }

    public function getSubjectStats(int $subjectId): array
    {
        $base = QuestionBank::where('school_id', current_tenant()?->id)
            ->where('subject_id', $subjectId);

        return [
            'total' => (clone $base)->count(),
            'draft' => (clone $base)->where('status', QuestionStatus::Draft)->count(),
            'published' => (clone $base)->where('status', QuestionStatus::Published)->count(),
            'archived' => (clone $base)->where('status', QuestionStatus::Archived)->count(),
            'by_type' => (clone $base)
                ->selectRaw('question_type, count(*) as count')
                ->groupBy('question_type')
                ->pluck('count', 'question_type')
                ->toArray(),
            'by_difficulty' => (clone $base)
                ->selectRaw('difficulty, count(*) as count')
                ->groupBy('difficulty')
                ->pluck('count', 'difficulty')
                ->toArray(),
        ];
    }

    public function validateAnswer(QuestionBank $question, mixed $answer): bool
    {
        return match ($question->question_type) {
            QuestionType::MultipleChoice => $answer == $question->correct_answer,
            QuestionType::TrueFalse => (bool) $answer === (bool) $question->correct_answer,
            QuestionType::Numeric => abs((float) $answer - (float) $question->numeric_answer) < 0.001,
            QuestionType::ShortAnswer => strtolower(trim($answer)) === strtolower(trim($question->short_answer)),
            QuestionType::FillInTheBlank => strtolower(trim($answer)) === strtolower(trim($question->fill_blank_answer)),
            default => false,
        };
    }

    public function exportQuestions(int $subjectId, ?string $format = 'json'): string
    {
        $questions = QuestionBank::where('school_id', current_tenant()?->id)
            ->where('subject_id', $subjectId)
            ->get();

        if ($format === 'csv') {
            return $this->exportAsCsv($questions);
        }

        return $questions->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    protected function exportAsCsv(Collection $questions): string
    {
        $headers = ['title', 'question_type', 'difficulty', 'question_text', 'marks', 'topic', 'status'];
        $csv = implode(',', $headers) . "\n";

        foreach ($questions as $q) {
            $row = array_map(fn ($h) => '"' . str_replace('"', '""', $q->{$h} ?? '') . '"', $headers);
            $csv .= implode(',', $row) . "\n";
        }

        return $csv;
    }
}
