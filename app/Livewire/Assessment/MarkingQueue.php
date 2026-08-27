<?php

namespace App\Livewire\Assessment;

use Livewire\Component;
use Modules\DigitalAssessment\Models\DigitalAssessment;
use Modules\DigitalAssessment\Models\DigitalAssessmentResponse;
use Modules\DigitalAssessment\Services\ManualMarkingService;

class MarkingQueue extends Component
{
    public ?int $assessmentId = null;
    public ?DigitalAssessment $assessment = null;
    public $queue = [];
    public ?int $currentResponseId = null;
    public ?DigitalAssessmentResponse $currentResponse = null;
    public float $marksAwarded = 0;
    public string $feedback = '';
    public int $totalInQueue = 0;
    public int $markedCount = 0;
    public string $filter = 'all';

    protected ManualMarkingService $markingService;

    protected $listeners = ['refreshQueue' => 'loadQueue'];

    public function boot(): void
    {
        $this->markingService = app(ManualMarkingService::class);
    }

    public function mount(?int $assessment = null): void
    {
        $this->assessmentId = $assessment;

        if ($assessment) {
            $this->assessment = DigitalAssessment::with(['subject', 'section'])->find($assessment);
        }

        $this->loadQueue();
    }

    public function loadQueue(): void
    {
        $schoolId = current_tenant()?->id ?? auth()->user()->school_id;

        $this->queue = $this->markingService
            ->getMarkingQueue($this->assessmentId, $schoolId)
            ->toArray();

        $this->totalInQueue = count($this->queue);
        $this->markedCount = 0;

        if ($this->currentResponseId) {
            $this->selectResponse($this->currentResponseId);
        } elseif (! empty($this->queue)) {
            $this->selectResponse($this->queue[0]['id']);
        }
    }

    public function selectResponse(int $id): void
    {
        $this->currentResponseId = $id;
        $this->currentResponse = DigitalAssessmentResponse::with([
            'question',
            'attempt.student',
            'attempt.assessment',
        ])->find($id);

        $this->marksAwarded = (float) ($this->currentResponse?->marks_possible ?? 0);
        $this->feedback = $this->currentResponse?->teacher_feedback ?? '';
    }

    public function markResponse(): void
    {
        if (! $this->currentResponse) {
            return;
        }

        try {
            $this->markingService->markResponse(
                $this->currentResponse,
                $this->marksAwarded,
                $this->feedback ?: null,
                auth()->id()
            );

            $this->markedCount++;

            session()->flash('success', 'Response marked successfully.');

            $this->loadQueue();

            $remaining = collect($this->queue)->first();
            if ($remaining) {
                $this->selectResponse($remaining['id']);
            } else {
                $this->currentResponse = null;
                $this->currentResponseId = null;
            }
        } catch (\DomainException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function skipResponse(): void
    {
        $idx = array_search($this->currentResponseId, array_column($this->queue, 'id'));

        if ($idx !== false && $idx < count($this->queue) - 1) {
            $this->selectResponse($this->queue[$idx + 1]['id']);
        }
    }

    public function getProgressPercentage(): int
    {
        if ($this->totalInQueue === 0) {
            return 100;
        }

        return (int) round(($this->markedCount / $this->totalInQueue) * 100);
    }

    public function render()
    {
        return view('livewire.assessment.marking-queue');
    }
}
