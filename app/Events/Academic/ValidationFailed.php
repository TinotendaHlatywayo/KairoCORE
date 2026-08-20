<?php

namespace App\Events\Academic;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ValidationFailed
{
    use Dispatchable, SerializesModels;

    public string $entityType;

    public int $entityId;

    public string $action;

    public array $errors;

    public array $warnings;

    public ?int $schoolId;

    public ?int $userId;

    public function __construct(string $entityType, int $entityId, string $action, array $errors, array $warnings = [], ?int $schoolId = null, ?int $userId = null)
    {
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->action = $action;
        $this->errors = $errors;
        $this->warnings = $warnings;
        $this->schoolId = $schoolId ?? config('current_tenant_id');
        $this->userId = $userId ?? auth()->id();
    }
}
