<?php

namespace Modules\DigitalAssessment\Enums;

enum LeaderboardScope: string
{
    case Class = 'class';
    case Subject = 'subject';
    case School = 'school';

    public function label(): string
    {
        return match ($this) {
            self::Class => 'Class',
            self::Subject => 'Subject',
            self::School => 'School',
        };
    }
}
