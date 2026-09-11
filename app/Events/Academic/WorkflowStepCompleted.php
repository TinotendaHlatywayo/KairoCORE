<?php

namespace App\Events\Academic;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowStepCompleted
{
    use Dispatchable, SerializesModels;

    public string $stepKey;

    public string $stepTitle;

    public int $schoolId;

    public ?int $userId;

    public array $metadata;

    public function __construct(string $stepKey, string $stepTitle, ?int $schoolId = null, ?int $userId = null, array $metadata = [])
    {
        $this->stepKey = $stepKey;
        $this->stepTitle = $stepTitle;
        $this->schoolId = $schoolId ?? (current_tenant()?->id ?? auth()->user()?->school_id ?? 1);
        $this->userId = $userId ?? auth()->id();
        $this->metadata = $metadata;
    }
}
