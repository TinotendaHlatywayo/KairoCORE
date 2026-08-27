<?php

namespace Modules\DigitalAssessment\Enums;

enum XpType: string
{
    case AssessmentComplete = 'assessment';
    case Improvement = 'improvement';
    case Streak = 'streak';
    case Mastery = 'mastery';
    case Challenge = 'challenge';
    case Bonus = 'bonus';

    public function label(): string
    {
        return match ($this) {
            self::AssessmentComplete => 'Assessment Completed',
            self::Improvement => 'Score Improvement',
            self::Streak => 'Learning Streak',
            self::Mastery => 'Topic Mastery',
            self::Challenge => 'Challenge Completed',
            self::Bonus => 'Bonus',
        };
    }
}
