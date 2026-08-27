<?php

namespace Modules\DigitalAssessment\Enums;

enum QuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case MultipleSelect = 'multiple_select';
    case TrueFalse = 'true_false';
    case ShortAnswer = 'short_answer';
    case Numeric = 'numeric';
    case Matching = 'matching';
    case Ordering = 'ordering';
    case FillInTheBlank = 'fill_in_the_blank';
    case Essay = 'essay';
    case FileUpload = 'file_upload';

    public function label(): string
    {
        return match ($this) {
            self::MultipleChoice => 'Multiple Choice',
            self::MultipleSelect => 'Multiple Select',
            self::TrueFalse => 'True / False',
            self::ShortAnswer => 'Short Answer',
            self::Numeric => 'Numeric',
            self::Matching => 'Matching',
            self::Ordering => 'Ordering',
            self::FillInTheBlank => 'Fill in the Blank',
            self::Essay => 'Essay',
            self::FileUpload => 'File Upload',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::MultipleChoice => 'Single correct answer from a list of options.',
            self::MultipleSelect => 'One or more correct answers from a list of options.',
            self::TrueFalse => 'Select True or False.',
            self::ShortAnswer => 'A brief free-text response.',
            self::Numeric => 'A numeric answer, optionally with units.',
            self::Matching => 'Match items from two columns.',
            self::Ordering => 'Arrange items in the correct order.',
            self::FillInTheBlank => 'Complete a sentence by filling in missing words.',
            self::Essay => 'A long-form written response requiring manual marking.',
            self::FileUpload => 'Upload a file as a response.',
        };
    }

    public function isAutoMarkable(): bool
    {
        return match ($this) {
            self::MultipleChoice,
            self::MultipleSelect,
            self::TrueFalse,
            self::Matching,
            self::Ordering,
            self::Numeric => true,
            self::ShortAnswer,
            self::FillInTheBlank,
            self::Essay,
            self::FileUpload => false,
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::MultipleChoice => 'heroicon-o-check-circle',
            self::MultipleSelect => 'heroicon-o-check-badge',
            self::TrueFalse => 'heroicon-o-arrows-right-left',
            self::ShortAnswer => 'heroicon-o-pencil',
            self::Numeric => 'heroicon-o-calculator',
            self::Matching => 'heroicon-o-link',
            self::Ordering => 'heroicon-o-bars-3',
            self::FillInTheBlank => 'heroicon-o-document-text',
            self::Essay => 'heroicon-o-document-duplicate',
            self::FileUpload => 'heroicon-o-arrow-up-tray',
        };
    }

    public static function autoMarkable(): array
    {
        return array_filter(self::cases(), fn ($case) => $case->isAutoMarkable());
    }

    public static function subjective(): array
    {
        return array_filter(self::cases(), fn ($case) => ! $case->isAutoMarkable());
    }
}
