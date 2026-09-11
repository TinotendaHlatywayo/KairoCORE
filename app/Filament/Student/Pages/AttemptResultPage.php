<?php

namespace App\Filament\Student\Pages;

use App\Filament\Student\Resources\StudentAssessmentResource;
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

        $student = StudentAssessmentResource::currentStudent();

        if (! $student) {
            abort(403, 'No student record linked to this account.');
        }

        $service = app(AttemptService::class);

        $attemptModel = DigitalAssessmentAttempt::findOrFail($attempt);

        // A student may only view their own attempts.
        if ((int) $attemptModel->student_id !== (int) $student->id) {
            abort(403, 'You are not authorised to view this attempt.');
        }

        $this->attemptModel = $service->getAttemptWithResponses($attemptModel);

        $this->summary = $service->getAttemptSummary($this->attemptModel);

        // Mark feedback as viewed (once) when the student may actually see the
        // correct-answer / explanation feedback for this attempt.
        $assessment = $this->attemptModel->assessment;
        $mode = $assessment?->feedback_mode?->value ?? 'after_submission';
        $feedbackVisible = match ($mode) {
            'never' => false,
            'after_deadline' => (bool) ($assessment->deadline_at && now()->gte($assessment->deadline_at)),
            default => true,
        };

        if ($feedbackVisible && ! $this->attemptModel->feedback_viewed_at) {
            $this->attemptModel->update(['feedback_viewed_at' => now()]);
            $this->attemptModel->refresh();
        }
    }
}
