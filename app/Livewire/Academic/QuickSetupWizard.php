<?php

namespace App\Livewire\Academic;

use App\Services\Academic\AcademicWorkflowEngine;
use Livewire\Component;

class QuickSetupWizard extends Component
{
    public int $currentStep = 1;

    public array $formData = [];

    public ?int $schoolId = null;

    public array $availableSteps = [];

    public function mount(?int $schoolId = null)
    {
        $this->schoolId = $schoolId ?? auth()->user()?->school_id;
        $engine = new AcademicWorkflowEngine($this->schoolId);
        $this->availableSteps = array_keys(AcademicWorkflowEngine::SETUP_WORKFLOW);
    }

    public function nextStep(): void
    {
        if ($this->currentStep < count($this->availableSteps)) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= count($this->availableSteps)) {
            $this->currentStep = $step;
        }
    }

    public function render()
    {
        $engine = new AcademicWorkflowEngine($this->schoolId);
        $currentKey = $this->availableSteps[$this->currentStep - 1] ?? null;
        $stepInfo = $currentKey ? $engine->getStepCompletionStatus($currentKey) : [];
        $stepDetails = $currentKey ? AcademicWorkflowEngine::SETUP_WORKFLOW[$currentKey] : [];

        return view('livewire.academic.quick-setup-wizard', [
            'currentKey' => $currentKey,
            'stepInfo' => $stepInfo,
            'stepDetails' => $stepDetails,
            'isCompleted' => $stepInfo['status'] ?? 'pending' === 'completed',
            'totalSteps' => count($this->availableSteps),
            'progress' => $this->currentStep / count($this->availableSteps) * 100,
            'engine' => $engine,
        ]);
    }
}
