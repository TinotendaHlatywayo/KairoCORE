<?php

namespace Modules\DigitalAssessment\Enums;

enum QuestionDifficulty: string
{
    case Foundation = 'foundation';
    case Developing = 'developing';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
    case Expert = 'expert';

    public function label(): string
    {
        return match ($this) {
            self::Foundation => 'Foundation',
            self::Developing => 'Developing',
            self::Intermediate => 'Intermediate',
            self::Advanced => 'Advanced',
            self::Expert => 'Expert',
        };
    }

    public function level(): int
    {
        return match ($this) {
            self::Foundation => 1,
            self::Developing => 2,
            self::Intermediate => 3,
            self::Advanced => 4,
            self::Expert => 5,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Foundation => 'gray',
            self::Developing => 'info',
            self::Intermediate => 'warning',
            self::Advanced => 'danger',
            self::Expert => 'success',
        };
    }
}
