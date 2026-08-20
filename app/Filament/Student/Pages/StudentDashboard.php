<?php

namespace App\Filament\Student\Pages;

use App\Filament\Student\Resources\HomeworkResource;
use Filament\Pages\Page;
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
        }

        return [
            'student' => $student,
            'upcoming' => $upcoming,
            'invoices' => $invoices,
            'submissionsCount' => $submissionsCount,
            'unpaidBalance' => $unpaidBalance,
            'pendingPayments' => $pendingPayments,
        ];
    }
}
