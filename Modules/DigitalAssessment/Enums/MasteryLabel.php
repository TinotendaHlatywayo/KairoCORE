<?php

namespace Modules\DigitalAssessment\Enums;

enum MasteryLabel: string
{
    case Beginning = 'beginning';
    case Developing = 'developing';
    case Proficient = 'proficient';
    case Mastered = 'mastered';

    public function label(): string
    {
        return match ($this) {
            self::Beginning => 'Beginning',
            self::Developing => 'Developing',
            self::Proficient => 'Proficient',
            self::Mastered => 'Mastered',
        };
    }

    public function minScore(): float
    {
        return match ($this) {
            self::Beginning => 0,
            self::Developing => 25,
            self::Proficient => 50,
            self::Mastered => 75,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Beginning => 'danger',
            self::Developing => 'warning',
            self::Proficient => 'info',
            self::Mastered => 'success',
        };
    }

    public static function fromScore(float $score): self
    {
        return match (true) {
            $score >= 75 => self::Mastered,
            $score >= 50 => self::Proficient,
            $score >= 25 => self::Developing,
            default => self::Beginning,
        };
    }
}
