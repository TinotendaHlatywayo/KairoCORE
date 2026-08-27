<?php

namespace Modules\DigitalAssessment\Enums;

enum ChallengeType: string
{
    case Individual = 'individual';
    case Class = 'class';
    case Subject = 'subject';
    case Topic = 'topic';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Individual',
            self::Class => 'Class',
            self::Subject => 'Subject',
            self::Topic => 'Topic',
        };
    }
}
