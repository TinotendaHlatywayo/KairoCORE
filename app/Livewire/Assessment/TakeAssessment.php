<?php

namespace App\Livewire\Assessment;

use App\Filament\Student\Resources\StudentAssessmentResource;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\DigitalAssessment\Models\DigitalAssessment;
use Modules\DigitalAssessment\Models\DigitalAssessmentAttempt;
use Modules\DigitalAssessment\Services\AdaptiveEngine;
use Modules\DigitalAssessment\Services\AttemptService;

class TakeAssessment extends Component
{
    use WithFileUploads;

    public ?int $assessmentId = null;

    public ?int $attemptId = null;

    public ?DigitalAssessment $assessment = null;

    public ?DigitalAssessmentAttempt $attempt = null;

    public int $currentQuestionIndex = 0;

    public array $answers = [];

    public int $secondsRemaining = 0;

    public bool $submitted = false;

    public bool $confirmSubmit = false;

    public bool $isAdaptive = false;

    public int $currentDifficulty = 50;

    public int $totalQuestions = 0;

    public $file;

    public bool $fileUploading = false;

    public bool $uploadSuccess = false;

    protected AttemptService $attemptService;

    protected ?AdaptiveEngine $adaptiveEngine = null;

    protected $listeners = ['file-uploaded' => 'handleFileUpload'];

    public function boot(): void
    {
        $this->attemptService = app(AttemptService::class);
        $this->adaptiveEngine = app(AdaptiveEngine::class);
    }

    public function mount(int $assessment): void
    {
        $this->assessmentId = $assessment;

        $this->assessment = DigitalAssessment::with(['questions.question', 'subject'])
            ->findOrFail($assessment);

        $this->isAdaptive = $this->adaptiveEngine->getAdaptiveConfig($this->assessment)?->is_active ?? false;
        $this->totalQuestions = $this->assessment->questions()->count();

        $student = StudentAssessmentResource::currentStudent();

        if (! $student) {
            session()->flash('error', 'Student record not found.');

            return;
        }

        // Deny attempts on assessments targeted at a class the student is not in.
        if ($this->assessment->section_id !== null) {
            $sectionIds = $student->enrollments()->pluck('section_id')->map(fn ($id) => (int) $id)->all();
            if (! in_array((int) $this->assessment->section_id, $sectionIds, true)) {
                abort(403, 'This assessment is not assigned to your class.');
            }
        }

        $this->attempt = $this->attemptService->startAttempt(
            $this->assessment,
            $student->id,
            $student->enrollments()->first()?->id
        );

        $this->attemptId = $this->attempt->id;

        $savedAnswers = $this->attemptService->getAutoSavedAnswers($this->attempt);

        $this->answers = [];

        foreach ($this->attempt->responses()->with('question')->get() as $response) {
            $qId = $response->question_bank_id;
            $this->answers[$qId] = $savedAnswers[$qId] ?? $response->learner_answer;
        }

        if ($this->isAdaptive) {
            $config = $this->adaptiveEngine->getAdaptiveConfig($this->assessment);
            $this->currentDifficulty = $this->adaptiveEngine->calculateCurrentDifficulty($config, $this->attempt);

            $this->ensureNextAdaptiveQuestion();
        }

        $this->calculateTimeRemaining();

        if ($this->secondsRemaining <= 0 && $this->assessment->auto_submit) {
            $this->attemptService->autoSubmitExpired($this->attempt);
            $this->submitted = true;
        }
    }

    public function getQuestionsProperty()
    {
        return $this->attempt
            ? $this->attempt->responses()->with('question')->orderBy('id')->get()
            : collect();
    }

    public function getCurrentQuestionProperty()
    {
        return $this->questions->get($this->currentQuestionIndex);
    }

    public function goToQuestion(int $index): void
    {
        if ($this->isAdaptive) {
            return;
        }

        if ($index >= 0 && $index < $this->questions->count()) {
            $this->currentQuestionIndex = $index;
        }
    }

    public function nextQuestion(): void
    {
        if ($this->isAdaptive) {
            $this->ensureNextAdaptiveQuestion();
        } elseif ($this->currentQuestionIndex < $this->questions->count() - 1) {
            $this->currentQuestionIndex++;
        }
    }

    public function previousQuestion(): void
    {
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
        }
    }

    /**
     * Reorder an Ordering-answer item up or down by index. The answer is stored
     * as a list of {text: ...} entries in the learner's chosen order, which is
     * exactly what AttemptService::checkAnswer() compares for QuestionType::Ordering.
     */
    public function moveOrderingItem(string $qId, int $from, string $direction): void
    {
        if ($this->submitted || ! $this->attempt || $this->isAdaptive) {
            return;
        }

        $items = $this->answers[$qId] ?? [];
        if (! is_array($items) || empty($items)) {
            return;
        }

        $to = $direction === 'up' ? $from - 1 : $from + 1;
        if ($from < 0 || $to < 0 || $from >= count($items) || $to >= count($items)) {
            return;
        }

        [$removed] = array_splice($items, $from, 1);
        array_splice($items, $to, 0, [$removed]);

        $items = array_values($items);
        $this->answers[(int) $qId] = $items;

        $this->attemptService->saveAnswer($this->attempt, (int) $qId, $items);
        $this->attemptService->autoSaveAnswer($this->attempt, (int) $qId, $items);
    }

    public function uploadFile(): void
    {
        $this->validate([
            'file' => 'required|file|max:51200',
        ], [
            'file.required' => 'Please select a file to upload.',
            'file.max' => 'File size must not exceed 50MB.',
        ]);

        $this->fileUploading = true;
        $this->uploadSuccess = false;

        try {
            $response = $this->attemptService->saveFileAnswer(
                $this->attempt,
                (int) $this->currentQuestion->question_bank_id,
                $this->file
            );

            $this->answers[$this->currentQuestion->question_bank_id] = $response->file_path;
            $this->uploadSuccess = true;
            $this->file = null;

            $this->dispatch('file-uploaded');
        } catch (\Exception $e) {
            session()->flash('error', 'Upload failed: '.$e->getMessage());
        } finally {
            $this->fileUploading = false;
        }
    }

    public function removeFile(): void
    {
        try {
            $this->attemptService->removeFileAnswer(
                $this->attempt,
                (int) $this->currentQuestion->question_bank_id
            );

            $this->answers[$this->currentQuestion->question_bank_id] = null;
            $this->uploadSuccess = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to remove file: '.$e->getMessage());
        }
    }

    public function handleFileUpload(): void
    {
        $this->uploadSuccess = true;
    }

    public function updatedAnswers($value, $key): void
    {
        if ($this->submitted || ! $this->attempt) {
            return;
        }

        $this->attemptService->saveAnswer($this->attempt, (int) $key, $value);
        $this->attemptService->autoSaveAnswer($this->attempt, (int) $key, $value);

        if ($this->isAdaptive) {
            $response = $this->attempt->responses()
                ->where('question_bank_id', $key)
                ->first();

            if ($response) {
                $this->adaptiveEngine->applyRulesAfterResponse($this->attempt, $response);

                $config = $this->adaptiveEngine->getAdaptiveConfig($this->assessment);
                $this->currentDifficulty = $this->adaptiveEngine->calculateCurrentDifficulty($config, $this->attempt);
            }
        }
    }

    protected function ensureNextAdaptiveQuestion(): void
    {
        if (! $this->isAdaptive || ! $this->attempt || $this->submitted) {
            return;
        }

        $answeredIds = $this->attempt->responses()->pluck('question_bank_id')->toArray();

        if (count($answeredIds) >= $this->totalQuestions) {
            return;
        }

        $nextQ = $this->adaptiveEngine->selectNextQuestion($this->assessment, $this->attempt);

        if ($nextQ && ! in_array($nextQ->id, $answeredIds)) {
            $this->attemptService->saveAnswer($this->attempt, $nextQ->id, null);
            $this->answers[$nextQ->id] = null;
        }

        $this->currentQuestionIndex = $this->questions->count() - 1;
    }

    public function tick(): void
    {
        if ($this->submitted) {
            return;
        }

        if ($this->secondsRemaining > 0) {
            $this->secondsRemaining--;
        }

        if ($this->secondsRemaining <= 0 && $this->assessment?->auto_submit) {
            $this->submit();
        }
    }

    public function submit(): void
    {
        if ($this->submitted || ! $this->attempt) {
            return;
        }

        $this->attemptService->submitAttempt($this->attempt);
        $this->submitted = true;

        $this->dispatch('assessment-submitted', attemptId: $this->attempt->id);
    }

    protected function calculateTimeRemaining(): void
    {
        if (! $this->assessment->duration_minutes || ! $this->attempt) {
            $this->secondsRemaining = 999999;

            return;
        }

        $elapsed = $this->attempt->started_at->diffInSeconds(now());
        $total = $this->assessment->duration_minutes * 60;
        $this->secondsRemaining = max(0, $total - (int) $elapsed);
    }

    public function formatTime(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }

        return sprintf('%02d:%02d', $minutes, $secs);
    }

    public function getAnsweredCount(): int
    {
        return collect($this->answers)->filter(fn ($v) => $v !== null && $v !== '' && $v !== [])->count();
    }

    public function render()
    {
        return view('livewire.assessment.take-assessment');
    }
}
