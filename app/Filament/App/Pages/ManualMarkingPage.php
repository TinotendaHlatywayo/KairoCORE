<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;

class ManualMarkingPage extends Page
{
    protected static string $view = 'filament.app.pages.manual-marking';

    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'Exams & Grading';

    protected static ?int $navigationSort = 63;

    protected static ?string $title = 'Manual Marking';

    protected static bool $shouldRegisterNavigation = false;

    public ?int $assessment = null;

    public static function getRoutePath(): string
    {
        return 'manual-marking/{assessment?}';
    }

    public function mount(?int $assessment = null): void
    {
        $this->assessment = $assessment;
    }

    public static function getNavigationLabel(): string
    {
        return __('Manual Marking');
    }
}
