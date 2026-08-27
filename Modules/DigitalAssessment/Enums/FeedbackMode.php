<?php

namespace Modules\DigitalAssessment\Enums;

enum FeedbackMode: string
{
    case Immediate = 'immediate';
    case Delayed = 'delayed';
    case AfterDeadline = 'after_deadline';
    case Never = 'never';

    public function label(): string
    {
        return match ($this) {
            self::Immediate => 'Immediate',
            self::Delayed => 'After Submission',
            self::AfterDeadline => 'After Deadline',
            self::Never => 'No Feedback',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Immediate => 'Show feedback for each question as the learner answers.',
            self::Delayed => 'Show feedback after the attempt is submitted.',
            self::AfterDeadline => 'Show feedback after the assessment deadline passes.',
            self::Never => 'Do not show feedback to learners.',
        };
    }
}
