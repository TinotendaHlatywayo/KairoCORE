<?php

namespace App\Filament\App\Pages\Finance;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Navigation\ModuleNavigationService;
use App\Services\ModuleVisibilityManager;
use Filament\Pages\Page;
use Modules\Finance\Models\Invoice;
use Modules\Students\Models\Student;

class StudentBillingHub extends Page
{
    use ModuleAwareActiveNavigation;

    protected static string $view = 'filament.app.pages.finance.student-billing-hub';

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Student Billing & Revenue (Receivables)';

    protected static ?string $title = 'Student Billing & Revenue (Receivables)';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'finance-student-billing';

    public static function canAccess(): bool
    {
        return ModuleVisibilityManager::isModuleVisible('finance');
    }

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    public function mount(): void
    {
        // Arrived straight from a dashboard card (e.g. "Student Credits" or
        // "Outstanding Fees"): stay on this page and show the requested section.
        if (in_array(request()->query('hub'), ['credits', 'outstanding'], true)) {
            return;
        }

        $last = session('nav.last.finance.'.$this->getCategoryLabel());
        $pages = $this->getCategoryPages();

        if (empty($pages)) {
            return;
        }

        $validUrls = collect($pages)->pluck('url')->all();
        $target = in_array($last, $validUrls, true) ? $last : ($pages[0]['url'] ?? null);

        if ($target && $target !== request()->url()) {
            redirect($target);
        }
    }

    public function getCategoryLabel(): string
    {
        return __('Student Billing & Revenue');
    }

    public function getCategoryPages(): array
    {
        $service = app(ModuleNavigationService::class);
        $module = $service->moduleBySlug('finance');
        $tabs = array_merge($service->moduleTabs($module), $service->moduleMoreTabs($module));

        return array_values(array_filter(
            $tabs,
            fn ($t) => ($t['group'] ?? null) === $this->getCategoryLabel() && ($t['class'] ?? null) !== static::class
        ));
    }

    protected function getViewData(): array
    {
        $schoolId = current_tenant()?->id ?? 1;

        // Students holding a carried-forward credit from a previous term.
        $creditStudents = Student::where('school_id', $schoolId)
            ->where('credit_balance', '>', 0)
            ->with(['currentEnrollment.course', 'currentEnrollment.section'])
            ->orderByDesc('credit_balance')
            ->limit(15)
            ->get();

        $totalCredits = (float) Student::where('school_id', $schoolId)
            ->sum('credit_balance');

        // Students with a live outstanding balance (open invoices). Carried-forward
        // originals have already been settled onto their CF- invoice, so each
        // balance is counted exactly once.
        $openInvoices = Invoice::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('status', '!=', 'paid')
            ->where('status', '!=', 'void')
            ->where('balance_amount', '>', 0)
            ->whereNull('carried_forward_at')
            ->with(['student.currentEnrollment.course', 'student.currentEnrollment.section', 'term'])
            ->get();

        $outstandingStudents = $openInvoices
            ->groupBy(fn ($invoice) => $invoice->student_id)
            ->map(function ($invoices) {
                $first = $invoices->first();

                return [
                    'student' => $first?->student,
                    'total' => round((float) $invoices->sum('balance_amount'), 2),
                    'count' => $invoices->count(),
                    'term' => $invoices->sortByDesc('created_at')->first()?->term?->name,
                ];
            })
            ->values()
            ->filter(fn ($row) => $row['student'] !== null)
            ->sortByDesc('total')
            ->take(15)
            ->values()
            ->all();

        $totalOutstanding = round((float) $openInvoices->sum('balance_amount'), 2);

        return [
            'categoryLabel' => __('Student Billing & Revenue (Receivables)'),
            'categoryPages' => $this->getCategoryPages(),
            'creditStudents' => $creditStudents,
            'totalCredits' => $totalCredits,
            'outstandingStudents' => $outstandingStudents,
            'totalOutstanding' => $totalOutstanding,
        ];
    }
}
