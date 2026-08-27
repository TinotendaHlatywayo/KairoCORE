<?php

namespace Modules\DigitalAssessment\Enums;

enum AssessmentMode: string
{
    case Standard = 'standard';
    case Adaptive = 'adaptive';

    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard',
            self::Adaptive => 'Adaptive',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Standard => 'Fixed set of questions presented in a set order.',
            self::Adaptive => 'Questions adapt based on learner performance.',
        };
    }
}
