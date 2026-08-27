<?php

namespace App\Filament\Student\Pages;

use Filament\Pages\Page;
use Modules\DigitalAssessment\Models\DigitalAssessmentAttempt;
use Modules\DigitalAssessment\Services\AttemptService;

class AttemptResultPage extends Page
{
    protected static string $view = 'filament.student.pages.attempt-result';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Academics';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Assessment Result';

    public ?int $attempt = null;

    public array $summary = [];

    public DigitalAssessmentAttempt $attemptModel;

    public static function getRoutePath(): string
    {
        return 'attempt-result/{attempt}';
    }

    public function mount(int $attempt): void
    {
        $this->attempt = $attempt;

        $service = app(AttemptService::class);

        $this->attemptModel = $service->getAttemptWithResponses(
            DigitalAssessmentAttempt::findOrFail($attempt)
        );

        $this->summary = $service->getAttemptSummary($this->attemptModel);
    }
}
