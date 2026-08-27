<?php

namespace Modules\DigitalAssessment\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\DigitalAssessment\Enums\AttemptStatus;
use Modules\DigitalAssessment\Enums\QuestionType;
use Modules\DigitalAssessment\Models\DigitalAssessment;
use Modules\DigitalAssessment\Models\DigitalAssessmentAttempt;
use Modules\DigitalAssessment\Models\DigitalAssessmentAutoSave;
use Modules\DigitalAssessment\Models\DigitalAssessmentResponse;
use Modules\DigitalAssessment\Services\QuestionAnalyticsService;
use Modules\DigitalAssessment\Services\MasteryService;
use Modules\DigitalAssessment\Services\GamificationService;
use Modules\DigitalAssessment\Models\QuestionBank;

class AttemptService
{
    public function startAttempt(DigitalAssessment $assessment, int $studentId, ?int $enrollmentId = null): DigitalAssessmentAttempt
    {
        $existing = $assessment->attempts()
            ->where('student_id', $studentId)
            ->inProgress()
            ->first();

        if ($existing) {
            return $existing;
        }

        $lastAttempt = $assessment->attempts()
            ->where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->first();

        if ($lastAttempt && $lastAttempt->created_at->diffInMinutes(now()) < 1) {
            throw new \DomainException('Please wait a moment before starting a new attempt.');
        }

        $attemptNumber = $assessment->attempts()
            ->where('student_id', $studentId)
            ->count() + 1;

        if ($attemptNumber > $assessment->max_attempts) {
            throw new \DomainException('No attempts remaining for this assessment.');
        }

        if ($assessment->availability_start_at && now()->lt($assessment->availability_start_at)) {
            throw new \DomainException('This assessment is not available yet.');
        }

        if ($assessment->availability_end_at && now()->gt($assessment->availability_end_at)) {
            throw new \DomainException('This assessment is no longer available.');
        }

        if (! in_array($assessment->status->value, ['published', 'active'])) {
            throw new \DomainException('This assessment is not accepting attempts.');
        }

        $questions = $this->resolveQuestionOrder($assessment);

        return DB::transaction(function () use ($assessment, $studentId, $enrollmentId, $attemptNumber) {
            $attempt = DigitalAssessmentAttempt::create([
                'school_id' => $assessment->school_id,
                'digital_assessment_id' => $assessment->id,
                'student_id' => $studentId,
                'enrollment_id' => $enrollmentId,
                'attempt_number' => $attemptNumber,
                'started_at' => now(),
                'status' => AttemptStatus::InProgress,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'max_possible_marks' => $assessment->getCalculatedTotalMarks(),
            ]);

            $questions = $this->resolveQuestionOrder($assessment);

            foreach ($questions as $order => $questionBankId) {
                $dq = $assessment->questions()
                    ->where('question_bank_id', $questionBankId)
                    ->first();

                $marks = $dq?->getEffectiveMarks() ?? 0;

                DigitalAssessmentResponse::create([
                    'digital_assessment_attempt_id' => $attempt->id,
                    'question_bank_id' => $questionBankId,
                    'marks_possible' => $marks,
                ]);
            }

            $attempt->update([
                'max_possible_marks' => $questions->sum(
                    fn ($qId) => $assessment->questions()
                        ->where('question_bank_id', $qId)
                        ->first()?->getEffectiveMarks() ?? 0
                ),
            ]);

            return $attempt->fresh();
        });
    }

    protected function resolveQuestionOrder(DigitalAssessment $assessment): \Illuminate\Support\Collection
    {
        $questions = $assessment->questions()
            ->orderBy('question_order')
            ->pluck('question_bank_id');

        if ($assessment->randomize_questions) {
            $questions = $questions->shuffle();
        }

        return $questions;
    }

    public function saveAnswer(DigitalAssessmentAttempt $attempt, int $questionBankId, mixed $answer): DigitalAssessmentResponse
    {
        if ($attempt->status !== AttemptStatus::InProgress) {
            throw new \DomainException('This attempt is no longer in progress.');
        }

        $response = $attempt->responses()
            ->where('question_bank_id', $questionBankId)
            ->first();

        if (! $response) {
            throw new \DomainException('Question not found in this attempt.');
        }

        if ($attempt->assessment->deadline_at && now()->gt($attempt->assessment->deadline_at)) {
            throw new \DomainException('The deadline for this assessment has passed.');
        }

        $response->update([
            'learner_answer' => $answer,
            'answered_at' => now(),
        ]);

        return $response;
    }

    public function saveFileAnswer(DigitalAssessmentAttempt $attempt, int $questionBankId, UploadedFile $file): DigitalAssessmentResponse
    {
        if ($attempt->status !== AttemptStatus::InProgress) {
            throw new \DomainException('This attempt is no longer in progress.');
        }

        $response = $attempt->responses()
            ->where('question_bank_id', $questionBankId)
            ->first();

        if (! $response) {
            throw new \DomainException('Question not found in this attempt.');
        }

        if ($attempt->assessment->deadline_at && now()->gt($attempt->assessment->deadline_at)) {
            throw new \DomainException('The deadline for this assessment has passed.');
        }

        if ($response->file_path && Storage::disk('public')->exists($response->file_path)) {
            Storage::disk('public')->delete($response->file_path);
        }

        $path = $file->store('assessment-uploads/' . $attempt->id, 'public');

        $response->update([
            'learner_answer' => $path,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'file_mime' => $file->getMimeType(),
            'answered_at' => now(),
        ]);

        return $response;
    }

    public function removeFileAnswer(DigitalAssessmentAttempt $attempt, int $questionBankId): DigitalAssessmentResponse
    {
        $response = $attempt->responses()
            ->where('question_bank_id', $questionBankId)
            ->first();

        if (! $response) {
            throw new \DomainException('Question not found in this attempt.');
        }

        if ($response->file_path && Storage::disk('public')->exists($response->file_path)) {
            Storage::disk('public')->delete($response->file_path);
        }

        $response->update([
            'learner_answer' => null,
            'file_path' => null,
            'original_filename' => null,
            'file_size' => null,
            'file_mime' => null,
        ]);

        return $response;
    }

    public function autoSaveAnswer(DigitalAssessmentAttempt $attempt, int $questionBankId, mixed $answer): void
    {
        DigitalAssessmentAutoSave::updateOrCreate(
            [
                'digital_assessment_attempt_id' => $attempt->id,
                'question_bank_id' => $questionBankId,
            ],
            [
                'response_data' => ['answer' => $answer],
                'saved_at' => now(),
            ]
        );
    }

    public function getAutoSavedAnswers(DigitalAssessmentAttempt $attempt): array
    {
        return DigitalAssessmentAutoSave::where('digital_assessment_attempt_id', $attempt->id)
            ->get()
            ->mapWithKeys(fn ($save) => [
                $save->question_bank_id => $save->response_data['answer'] ?? null,
            ])
            ->toArray();
    }

    public function submitAttempt(DigitalAssessmentAttempt $attempt): DigitalAssessmentAttempt
    {
        if ($attempt->status !== AttemptStatus::InProgress) {
            throw new \DomainException('This attempt is already submitted.');
        }

        return DB::transaction(function () use ($attempt) {
            $attempt->update([
                'status' => AttemptStatus::Submitted,
                'submitted_at' => now(),
                'duration_seconds' => $attempt->calculateDuration(),
            ]);

            $this->autoMarkResponses($attempt);
            $this->calculateScore($attempt);

            DigitalAssessmentAutoSave::where('digital_assessment_attempt_id', $attempt->id)->delete();

            app(QuestionAnalyticsService::class)->recalculateForAssessment($attempt->assessment);
            app(MasteryService::class)->updateMasteryForAttempt($attempt->id);
            app(GamificationService::class)->processAttemptCompletion($attempt);

            return $attempt->fresh();
        });
    }

    public function autoSubmitExpired(DigitalAssessmentAttempt $attempt): DigitalAssessmentAttempt
    {
        return DB::transaction(function () use ($attempt) {
            $attempt->update([
                'status' => AttemptStatus::AutoSubmitted,
                'submitted_at' => now(),
                'duration_seconds' => $attempt->calculateDuration(),
            ]);

            $this->autoMarkResponses($attempt);
            $this->calculateScore($attempt);

            app(QuestionAnalyticsService::class)->recalculateForAssessment($attempt->assessment);
            app(MasteryService::class)->updateMasteryForAttempt($attempt->id);
            app(GamificationService::class)->processAttemptCompletion($attempt);

            return $attempt->fresh();
        });
    }

    protected function autoMarkResponses(DigitalAssessmentAttempt $attempt): void
    {
        $responses = $attempt->responses()->with('question')->get();

        foreach ($responses as $response) {
            $question = $response->question;

            if (! $question || ! $question->isAutoMarkable()) {
                continue;
            }

            $isCorrect = $this->checkAnswer($question, $response->learner_answer);
            $marksAwarded = $isCorrect ? $response->marks_possible : 0;

            $response->update([
                'correct_answer' => $question->correct_answer,
                'is_correct' => $isCorrect,
                'marks_awarded' => $marksAwarded,
            ]);
        }
    }

    protected function checkAnswer(QuestionBank $question, mixed $answer): bool
    {
        if ($answer === null || $answer === '' || $answer === []) {
            return false;
        }

        return match ($question->question_type) {
            QuestionType::MultipleChoice => (string) $answer === (string) ($question->correct_answer ?? ''),
            QuestionType::TrueFalse => filter_var($answer, FILTER_VALIDATE_BOOLEAN) === filter_var($question->correct_answer, FILTER_VALIDATE_BOOLEAN),
            QuestionType::MultipleSelect => is_array($answer) && is_array($question->correct_answer)
                && empty(array_diff(array_map('strval', $answer), array_map('strval', $question->correct_answer)))
                && empty(array_diff(array_map('strval', $question->correct_answer), array_map('strval', $answer))),
            QuestionType::Numeric => abs((float) $answer - (float) ($question->numeric_answer ?? 0)) < 0.001,
            QuestionType::ShortAnswer => strtolower(trim((string) $answer)) === strtolower(trim((string) ($question->short_answer ?? ''))),
            QuestionType::FillInTheBlank => strtolower(trim((string) $answer)) === strtolower(trim((string) ($question->fill_blank_answer ?? ''))),
            QuestionType::Matching => is_array($answer) && is_array($question->correct_answer)
                && collect($answer)->pluck('right')->values()->all() === collect($question->correct_answer)->pluck('right')->values()->all(),
            QuestionType::Ordering => is_array($answer) && is_array($question->correct_answer)
                && collect($answer)->pluck('text')->values()->all() === collect($question->correct_answer)->pluck('text')->values()->all(),
            default => false,
        };
    }

    protected function calculateScore(DigitalAssessmentAttempt $attempt): void
    {
        $responses = $attempt->responses;

        $autoScore = $responses->whereNotNull('marks_awarded')->sum('marks_awarded');
        $totalPossible = $responses->sum('marks_possible');

        $percentage = $totalPossible > 0 ? round(($autoScore / $totalPossible) * 100, 2) : 0;

        $attempt->update([
            'auto_score' => $autoScore,
            'score' => $autoScore,
            'marks_obtained' => $autoScore,
            'percentage' => $percentage,
        ]);
    }

    public function getAttemptWithResponses(DigitalAssessmentAttempt $attempt): DigitalAssessmentAttempt
    {
        return $attempt->load([
            'assessment.subject',
            'assessment.questions.question',
            'responses.question',
        ]);
    }

    public function getAttemptSummary(DigitalAssessmentAttempt $attempt): array
    {
        $responses = $attempt->responses()->with('question')->get();

        $correct = $responses->where('is_correct', true)->count();
        $incorrect = $responses->where('is_correct', false)->whereNotNull('is_correct')->count();
        $unanswered = $responses->whereNull('learner_answer')->count();
        $manualPending = $responses->whereNull('marks_awarded')
            ->where(fn ($q) => $q->whereNotNull('learner_answer'))
            ->count();

        return [
            'total_questions' => $responses->count(),
            'answered' => $responses->whereNotNull('learner_answer')->count(),
            'correct' => $correct,
            'incorrect' => $incorrect,
            'unanswered' => $unanswered,
            'manual_pending' => $manualPending,
            'auto_score' => $attempt->auto_score,
            'marks_obtained' => $attempt->marks_obtained,
            'max_possible' => $attempt->max_possible_marks,
            'percentage' => $attempt->percentage,
            'passed' => $attempt->hasPassed(),
            'duration_seconds' => $attempt->duration_seconds,
        ];
    }
}
