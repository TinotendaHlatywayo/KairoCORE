<?php

namespace Modules\DigitalAssessment\Enums;

enum AssessmentStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Active = 'active';
    case Closed = 'closed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
            self::Active => 'Active',
            self::Closed => 'Closed',
            self::Archived => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Scheduled => 'info',
            self::Published => 'success',
            self::Active => 'warning',
            self::Closed => 'danger',
            self::Archived => 'gray',
        };
    }

    public function canStudentsAccess(): bool
    {
        return in_array($this, [self::Published, self::Active]);
    }
}
