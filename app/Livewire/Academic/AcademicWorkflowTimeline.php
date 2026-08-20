<?php

namespace App\Livewire\Academic;

use App\Services\Academic\AcademicWorkflowEngine;
use Livewire\Component;
use Modules\Admin\Services\PermissionRegistry;

class AcademicWorkflowTimeline extends Component
{
    public ?int $schoolId = null;

    public array $steps = [];

    public string $viewMode = 'list';

    public bool $canManageWorkflow = false;

    protected $listeners = ['refreshTimeline' => 'loadSteps'];

    public function mount(?int $schoolId = null)
    {
        $this->schoolId = $schoolId ?? auth()->user()?->school_id;
        $this->canManageWorkflow = $this->userCanManageWorkflow();
        $this->syncSteps();
        $this->loadSteps();
    }

    protected function userCanManageWorkflow(): bool
    {
        if (class_exists('\Modules\Admin\Services\PermissionRegistry')) {
            return PermissionRegistry::checkPermission('academic_ops.manage_workflow');
        }

        return true;
    }

    public function syncSteps(): void
    {
        (new AcademicWorkflowEngine($this->schoolId))->syncWorkflowSteps($this->schoolId);
    }

    public function loadSteps(): void
    {
        $engine = new AcademicWorkflowEngine($this->schoolId);
        $workflowSteps = $engine->getWorkflowSteps();

        $this->steps = $workflowSteps->map(function ($step, $key) use ($engine) {
            $progress = $engine->getWorkflowProgress();
            $status = $progress['status_by_step'][$key] ?? 'pending';
            $isBlocked = in_array($key, $engine->getBlockedSteps());
            $dep = $step['depends_on'] ?? null;
            $isReady = ! $dep || $engine->isDependencySatisfied($dep);

            return [
                'key' => $key,
                'title' => $step['title'],
                'description' => $step['description'],
                'status' => $status,
                'is_blocked' => $isBlocked,
                'is_ready' => $isReady,
                'depends_on' => $dep,
                'route' => $engine->getStepRoute($key),
                'index' => array_search($key, array_keys(AcademicWorkflowEngine::SETUP_WORKFLOW)) + 1,
            ];
        })->values()->toArray();
    }

    public function setStatus(string $stepKey, string $status): void
    {
        if (! $this->userCanManageWorkflow()) {
            abort(403, 'You do not have permission to override workflow steps.');

            return;
        }

        $engine = new AcademicWorkflowEngine($this->schoolId);
        $engine->setStepStatus($stepKey, $status);
        $this->loadSteps();
        $this->dispatch('workflowStatusChanged', step: $stepKey, status: $status);
    }

    public function resetStatus(string $stepKey): void
    {
        if (! $this->userCanManageWorkflow()) {
            abort(403, 'You do not have permission to override workflow steps.');

            return;
        }

        (new AcademicWorkflowEngine($this->schoolId))->resetStepStatus($stepKey);
        $this->loadSteps();
        $this->dispatch('workflowStatusChanged', step: $stepKey, status: 'pending');
    }

    public function render()
    {
        return view('livewire.academic.academic-workflow-timeline', [
            'steps' => $this->steps,
            'progress' => $this->calculateProgress(),
            'canManageWorkflow' => $this->canManageWorkflow,
        ]);
    }

    protected function calculateProgress(): array
    {
        $total = count($this->steps);
        $completed = count(array_filter($this->steps, fn ($s) => $s['status'] === 'completed'));
        $skipped = count(array_filter($this->steps, fn ($s) => $s['status'] === 'skipped'));
        $blocked = count(array_filter($this->steps, fn ($s) => $s['is_blocked']));
        $inProgress = count(array_filter($this->steps, fn ($s) => $s['status'] === 'pending' && ! $s['is_blocked']));

        return [
            'total' => $total,
            'completed' => $completed,
            'skipped' => $skipped,
            'percent' => $total > 0 ? round((($completed + $skipped) / $total) * 100) : 0,
            'blocked' => $blocked,
            'in_progress' => $inProgress,
        ];
    }
}
