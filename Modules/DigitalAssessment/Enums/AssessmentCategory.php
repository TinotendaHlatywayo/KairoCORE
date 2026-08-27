<?php

namespace Modules\DigitalAssessment\Enums;

enum AssessmentCategory: string
{
    case Practice = 'practice';
    case Formative = 'formative';
    case Summative = 'summative';

    public function label(): string
    {
        return match ($this) {
            self::Practice => 'Practice',
            self::Formative => 'Formative',
            self::Summative => 'Summative',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Practice => 'For practice and self-assessment. Does not count toward grades.',
            self::Formative => 'Low-stakes assessment to inform teaching.',
            self::Summative => 'High-stakes assessment that counts toward grades.',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Practice => 'info',
            self::Formative => 'warning',
            self::Summative => 'danger',
        };
    }
}
