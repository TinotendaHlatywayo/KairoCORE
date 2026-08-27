<?php

namespace App\Filament\Student\Pages;

use Filament\Pages\Page;

class TakeAssessmentPage extends Page
{
    protected static string $view = 'filament.student.pages.take-assessment';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Academics';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Take Assessment';

    public ?int $assessment = null;

    public static function getRoutePath(): string
    {
        return 'take-assessment/{assessment}';
    }

    public function mount(int $assessment): void
    {
        $this->assessment = $assessment;
    }
}
