<?php

namespace App\Filament\Student\Pages;

use Filament\Pages\Page;
use Modules\DigitalAssessment\Services\GamificationService;
use Modules\DigitalAssessment\Models\GamificationSettings;

class GamificationProfilePage extends Page
{
    protected static string $view = 'filament.student.pages.gamification-profile';

    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static ?string $navigationGroup = 'Learning';

    protected static ?int $navigationSort = 12;

    protected static ?string $title = 'My Achievements';

    public array $stats = [];
    public bool $gamificationEnabled = false;

    public function mount(): void
    {
        $settings = app(GamificationService::class)->getSettings();
        $this->gamificationEnabled = $settings->isAnyGamificationEnabled();

        if (! $this->gamificationEnabled) {
            return;
        }

        $student = \App\Filament\Student\Resources\StudentAssessmentResource::currentStudent();

        if (! $student) {
            return;
        }

        $this->stats = app(GamificationService::class)->getStudentStats($student->id);
    }
}
