<?php

namespace Modules\Admin\Enums;

enum EmailCategory: string
{
    case Admissions = 'admissions';
    case Finance = 'finance';
    case Academic = 'academic';
    case Communication = 'communication';

    public function label(): string
    {
        return match ($this) {
            self::Admissions => 'Admissions',
            self::Finance => 'Finance',
            self::Academic => 'Academic',
            self::Communication => 'Communication',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Admissions => 'heroicon-o-document-text',
            self::Finance => 'heroicon-o-banknotes',
            self::Academic => 'heroicon-o-academic-cap',
            self::Communication => 'heroicon-o-chat-bubble-left-right',
        };
    }
}
