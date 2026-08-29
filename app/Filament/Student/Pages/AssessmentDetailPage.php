<?php

namespace App\Filament\Student\Pages;

use App\Filament\Student\Resources\StudentAssessmentResource;
use Filament\Pages\Page;
use Modules\DigitalAssessment\Enums\AttemptStatus;
use Modules\DigitalAssessment\Models\DigitalAssessment;
use Modules\DigitalAssessment\Models\DigitalAssessmentAttempt;

class AssessmentDetailPage extends Page
{
    protected static string $view = 'filament.student.pages.assessment-detail';

    protected static ?string $navigationIcon = 'heroicon-o-information-circle';

    protected static ?string $navigationGroup = 'Academics';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Assessment Details';

    protected static bool $shouldRegisterNavigation = false;

    public ?int $assessmentId = null;

    public ?DigitalAssessment $assessment = null;

    public ?DigitalAssessmentAttempt $currentAttempt = null;

    public int $attemptCount = 0;

    public int $attemptsRemaining = 0;

    public bool $canStart = false;

    public string $blockReason = '';

    public static function getRoutePath(): string
    {
        return 'assessment-detail/{assessment}';
    }

    public function mount(int $assessment): void
    {
        $this->assessmentId = $assessment;

        $this->assessment = DigitalAssessment::with(['subject', 'attempts'])
            ->withCount('questions')
            ->findOrFail($assessment);

        $student = StudentAssessmentResource::currentStudent();

        if (! $student) {
            $this->blockReason = 'Student profile not found.';

            return;
        }

        // Only students enrolled in the assessment's target section (or a
        // school-wide assessment with a null section) may view/attempt it.
        $sectionIds = $student->enrollments()->pluck('section_id')->all();

        if (
            $this->assessment->section_id !== null
            && ! in_array((int) $this->assessment->section_id, array_map('intval', $sectionIds), true)
        ) {
            abort(403, 'This assessment is not assigned to your class.');
        }

        $this->currentAttempt = $this->assessment->attempts()
            ->where('student_id', $student->id)
            ->where('status', AttemptStatus::InProgress)
            ->first();

        $this->attemptCount = $this->assessment->attempts()
            ->where('student_id', $student->id)
            ->count();

        $this->attemptsRemaining = max(0, $this->assessment->max_attempts - $this->attemptCount);

        if ($this->currentAttempt) {
            $this->canStart = true;
            $this->blockReason = '';
        } elseif ($this->attemptsRemaining <= 0) {
            $this->canStart = false;
            $this->blockReason = 'You have used all available attempts for this assessment.';
        } elseif ($this->assessment->availability_start_at && now()->lt($this->assessment->availability_start_at)) {
            $this->canStart = false;
            $this->blockReason = 'This assessment is not available yet. Opens: '.$this->assessment->availability_start_at->format('d M Y, H:i');
        } elseif ($this->assessment->availability_end_at && now()->gt($this->assessment->availability_end_at)) {
            $this->canStart = false;
            $this->blockReason = 'This assessment is no longer available.';
        } elseif (! in_array($this->assessment->status->value, ['published', 'active'])) {
            $this->canStart = false;
            $this->blockReason = 'This assessment is not accepting attempts.';
        } else {
            $this->canStart = true;
        }
    }
}
