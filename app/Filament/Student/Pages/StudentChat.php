<?php

namespace App\Filament\Student\Pages;

use Filament\Pages\Page;

class StudentChat extends Page
{
    protected static string $view = 'filament.student.pages.student-chat';

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Chat & Messages';

    protected static ?string $title = 'Chat & Messages';

    protected static ?string $slug = 'chat';

    public static function getNavigationLabel(): string
    {
        return __('Chat & Messages');
    }
}
