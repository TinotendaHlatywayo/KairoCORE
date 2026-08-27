<?php

namespace Modules\DigitalAssessment\Enums;

enum AttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case AutoSubmitted = 'auto_submitted';
    case Graded = 'graded';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In Progress',
            self::Submitted => 'Submitted',
            self::AutoSubmitted => 'Auto-Submitted',
            self::Graded => 'Graded',
            self::Published => 'Published',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::InProgress => 'info',
            self::Submitted => 'warning',
            self::AutoSubmitted => 'warning',
            self::Graded => 'success',
            self::Published => 'success',
        };
    }

    public function isComplete(): bool
    {
        return in_array($this, [self::Submitted, self::AutoSubmitted, self::Graded, self::Published]);
    }
}
