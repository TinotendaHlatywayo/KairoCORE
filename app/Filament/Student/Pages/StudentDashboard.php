<?php

namespace App\Filament\Student\Pages;

use App\Filament\Student\Resources\HomeworkResource;
use App\Filament\Student\Resources\StudentAssessmentResource;
use Filament\Pages\Page;
use Modules\DigitalAssessment\Enums\AssessmentStatus;
use Modules\DigitalAssessment\Enums\AttemptStatus;
use Modules\DigitalAssessment\Models\DigitalAssessment;
use Modules\DigitalAssessment\Models\DigitalAssessmentAttempt;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\StudentPaymentSubmission;
use Modules\Lms\Models\Homework;
use Modules\Lms\Models\HomeworkSubmission;

class StudentDashboard extends Page
{
    protected static string $view = 'filament.student.pages.student-dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'My Dashboard';

    protected static ?string $title = 'Student Portal';

    protected static ?string $slug = 'student-portal';

    public static function getNavigationLabel(): string
    {
        return __('My Dashboard');
    }

    public function getHeading(): string
    {
        return __('My Dashboard');
    }

    public function mount(): void {}

    protected function getViewData(): array
    {
        $student = HomeworkResource::currentStudent();

        $upcoming = collect();
        $invoices = collect();
        $submissionsCount = 0;
        $unpaidBalance = 0.0;
        $pendingPayments = 0;
        $availableAssessments = collect();
        $recentResults = collect();
        $assessmentStats = ['total' => 0, 'pending' => 0, 'completed' => 0, 'in_progress' => 0];

        if ($student) {
            $sectionIds = $student->enrollments()->pluck('section_id')->filter()->unique();

            $upcoming = Homework::whereIn('section_id', $sectionIds)
                ->where('due_date', '>=', now()->startOfDay())
                ->with([
                    'subject',
                    'submissions' => fn ($query) => $query->where('student_id', $student->id),
                ])
                ->orderBy('due_date')
                ->limit(5)
                ->get();

            $invoices = Invoice::where('student_id', $student->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $unpaidBalance = $invoices->sum(fn ($invoice) => (float) $invoice->balance_amount);

            $pendingPayments = StudentPaymentSubmission::where('student_id', $student->id)
                ->where('status', StudentPaymentSubmission::STATUS_PENDING)
                ->count();

            $submissionsCount = HomeworkSubmission::where('student_id', $student->id)
                ->whereNull('teacher_feedback')
                ->count();

            // ── Digital Assessments ──
            $enrolledSectionIds = $student->enrollments()->pluck('section_id')->filter()->toArray();

            $allAssessments = DigitalAssessment::query()
                ->whereIn('status', [AssessmentStatus::Published, AssessmentStatus::Active])
                ->where(function ($q) use ($enrolledSectionIds) {
                    $q->whereNull('section_id')
                      ->orWhereIn('section_id', $enrolledSectionIds);
                })
                ->with(['subject', 'attempts' => function ($q) use ($student) {
                    $q->where('student_id', $student->id);
                }])
                ->withCount('questions')
                ->orderByDesc('created_at')
                ->get();

            $allAssessments->each(function ($a) use ($student) {
                $attempts = $a->attempts->where('student_id', $student->id);
                $a->student_attempts_count = $attempts->count();
                $a->student_has_in_progress = $attempts->contains(fn ($at) => $at->status === AttemptStatus::InProgress);
                $a->student_has_completed = $attempts->contains(fn ($at) => in_array($at->status->value, ['submitted', 'graded', 'auto_submitted']));
                $a->student_best_percentage = $attempts->max('percentage') ?? 0;
                $a->student_latest_attempt = $attempts->sortByDesc('created_at')->first();
            });

            $availableAssessments = $allAssessments->take(5);
            $assessmentStats['total'] = $allAssessments->count();
            $assessmentStats['pending'] = $allAssessments->filter(fn ($a) => ! $a->student_has_completed && ! $a->student_has_in_progress)->count();
            $assessmentStats['in_progress'] = $allAssessments->filter(fn ($a) => $a->student_has_in_progress)->count();
            $assessmentStats['completed'] = $allAssessments->filter(fn ($a) => $a->student_has_completed)->count();

            // Recent results
            $recentResults = DigitalAssessmentAttempt::where('student_id', $student->id)
                ->whereIn('status', [AttemptStatus::Submitted, AttemptStatus::Graded, AttemptStatus::AutoSubmitted])
                ->with('assessment.subject')
                ->orderByDesc('submitted_at')
                ->take(5)
                ->get();
        }

        return [
            'student' => $student,
            'upcoming' => $upcoming,
            'invoices' => $invoices,
            'submissionsCount' => $submissionsCount,
            'unpaidBalance' => $unpaidBalance,
            'pendingPayments' => $pendingPayments,
            'availableAssessments' => $availableAssessments,
            'recentResults' => $recentResults,
            'assessmentStats' => $assessmentStats,
        ];
    }
}
