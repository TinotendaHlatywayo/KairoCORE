<?php

namespace App\Filament\App\Pages\Finance;

use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Term;
use Modules\Finance\Models\Payment;
use Modules\Finance\Services\StudentFinancialHistoryService;
use Modules\Students\Models\Student;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentFinancialHistoryPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.app.pages.finance.student-financial-history';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Student Financial History';

    protected static ?int $navigationSort = 6;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'student-financial-history';

    public ?int $student_id = null;

    public array $data = [];

    public ?int $year_id = null;

    public ?int $term_id = null;

    public ?string $start_date = null;

    public ?string $end_date = null;

    public string $scope = 'active_year';

    // Filters for whole-school view
    public ?int $filter_course_id = null;

    public ?int $filter_section_id = null;

    public string $filter_payment_status = 'all';

    public ?string $filter_gender = null;

    // Pagination for the whole-school summary table
    public int $per_page = 25;

    public int $page = 1;

    public function getTitle(): string
    {
        return __('Student Financial History');
    }

    public static function getNavigationLabel(): string
    {
        return __('Student Financial History');
    }

    public function mount(): void
    {
        $this->student_id = (int) request()->query('student', $this->student_id ?? 0) ?: null;

        $this->year_id = $this->year_id
            ?? AcademicYear::withoutTenantScope()
                ->where('school_id', current_tenant()?->id)
                ->where('is_active', true)
                ->value('id');

        $this->form->fill(['student_id' => $this->student_id]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('student_id')
                    ->label(__('Select Student'))
                    ->searchable()
                    ->preload()
                    ->placeholder(__('Search and select a student...'))
                    ->options(fn () => Student::withoutTenantScope()
                        ->where('school_id', current_tenant()?->id)
                        ->orderBy('last_name')
                        ->get()
                        ->mapWithKeys(fn (Student $s) => [
                            $s->id => trim($s->full_name.' ('.($s->student_id_number ?: ($s->admission_number ?? '')).')'),
                        ]))
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->student_id = (int) $state ?: null;
                    }),
            ])
            ->statePath('data');
    }

    public function selectStudent(int $studentId): void
    {
        $this->student_id = $studentId;
        $this->data['student_id'] = $studentId;
    }

    public function backToSchoolSummary(): void
    {
        $this->student_id = null;
        $this->data['student_id'] = null;
    }

    public function goToPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
    }

    public function restoreDefaults(): void
    {
        $activeYear = AcademicYear::withoutTenantScope()
            ->where('school_id', current_tenant()?->id)
            ->where('is_active', true)
            ->first();

        $this->year_id = $activeYear?->id;
        $this->term_id = null;
        $this->start_date = null;
        $this->end_date = null;
        $this->scope = 'active_year';
        $this->filter_course_id = null;
        $this->filter_section_id = null;
        $this->filter_payment_status = 'all';
        $this->filter_gender = null;
    }

    protected function scopeDates(): array
    {
        $start = $this->start_date;
        $end = $this->end_date;

        if ($this->scope !== 'custom') {
            $start = null;
            $end = null;
        }

        return StudentFinancialHistoryService::scopeDates(
            current_tenant()?->id,
            $this->scope === 'full' ? null : $this->year_id,
            $this->scope === 'term' ? $this->term_id : null,
            $start,
            $end,
        );
    }

    /**
     * Build the summary data for the whole-school view (no student selected).
     */
    protected function buildSchoolSummary(): array
    {
        $schoolId = current_tenant()?->id;
        $scope = $this->scopeDates();

        $query = Student::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->with(['currentEnrollment.course', 'currentEnrollment.section']);

        // Apply filters
        if ($this->filter_course_id) {
            $query->whereHas('currentEnrollment', fn ($q) => $q->where('course_id', $this->filter_course_id));
        }

        if ($this->filter_section_id) {
            $query->whereHas('currentEnrollment', fn ($q) => $q->where('section_id', $this->filter_section_id));
        }

        if ($this->filter_gender && $this->filter_gender !== 'all') {
            $query->where('gender', $this->filter_gender);
        }

        $students = $query->orderBy('last_name')->get();

        $summaries = [];
        foreach ($students as $student) {
            $ledger = StudentFinancialHistoryService::buildLedger($student, $scope['start'], $scope['end']);

            $billed = $ledger['total_billed'];
            $paid = $ledger['total_paid'];
            $balance = $ledger['closing_balance'];

            // Payment status filter
            if ($this->filter_payment_status === 'paid' && $balance > 0.01) {
                continue;
            }
            if ($this->filter_payment_status === 'unpaid' && $balance < 0.01) {
                continue;
            }

            $summaries[] = [
                'student' => $student,
                'student_id' => $student->student_id_number,
                'admission_number' => $student->admission_number,
                'gender' => $student->gender,
                'class' => trim(($student->currentEnrollment?->course?->name ?? '').' '.($student->currentEnrollment?->section?->name ?? '')) ?: 'Unassigned',
                'billed' => $billed,
                'paid' => $paid,
                'balance' => $balance,
                'status' => $balance > 0.01 ? ($paid > 0.01 ? 'partial' : 'unpaid') : 'paid',
            ];
        }

        $totalBilled = array_sum(array_column($summaries, 'billed'));
        $totalPaid = array_sum(array_column($summaries, 'paid'));
        $totalBalance = array_sum(array_column($summaries, 'balance'));

        $totalCount = count($summaries);
        $perPage = max(1, $this->per_page);
        $totalPages = max(1, (int) ceil($totalCount / $perPage));
        $this->page = min(max(1, $this->page), $totalPages);
        $currentPage = $this->page;
        $pageSummaries = array_slice($summaries, ($currentPage - 1) * $perPage, $perPage);

        return [
            'summaries' => $pageSummaries,
            'count' => $totalCount,
            'total_pages' => $totalPages,
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total_billed' => $totalBilled,
            'total_paid' => $totalPaid,
            'total_balance' => $totalBalance,
            'courses' => Course::where('school_id', $schoolId)->orderBy('name')->pluck('name', 'id'),
            'sections' => Section::whereHas('course', fn ($q) => $q->where('school_id', $schoolId))
                ->orderBy('name')
                ->pluck('name', 'id'),
        ];
    }

    protected function getViewData(): array
    {
        $schoolId = current_tenant()?->id;
        $scope = $this->scopeDates();

        $data = [
            'student' => null,
            'ledger' => [],
            'years' => AcademicYear::withoutTenantScope()->where('school_id', $schoolId)->orderBy('name')->get(),
            'terms' => Term::withoutTenantScope()
                ->where('school_id', $schoolId)
                ->when($this->year_id, fn ($q) => $q->where('academic_year_id', $this->year_id))
                ->with('academicYear')
                ->orderBy('start_date')
                ->get(),
            'start' => $scope['start'],
            'end' => $scope['end'],
            'label' => $scope['label'],
        ];

        if (! $this->student_id) {
            return array_merge($data, $this->buildSchoolSummary());
        }

        $student = Student::withoutTenantScope()
            ->with(['currentEnrollment.course', 'currentEnrollment.section'])
            ->find($this->student_id);

        if (! $student || $student->school_id !== $schoolId) {
            return array_merge($data, $this->buildSchoolSummary());
        }

        $ledger = StudentFinancialHistoryService::buildLedger($student, $scope['start'], $scope['end']);

        return array_merge($data, [
            'student' => $student,
            'ledger' => $ledger,
        ]);
    }

    public function downloadCsv(): StreamedResponse
    {
        $schoolId = current_tenant()?->id;
        $scope = $this->scopeDates();

        $student = $this->student_id
            ? Student::withoutTenantScope()->with(['currentEnrollment.course', 'currentEnrollment.section'])->find($this->student_id)
            : null;

        if (! $student || $student->school_id !== $schoolId) {
            abort(404);
        }

        $ledger = StudentFinancialHistoryService::buildLedger($student, $scope['start'], $scope['end']);

        $rows = [];
        $rows[] = ['Student Financial History'];
        $rows[] = ['Student', $student->full_name];
        $rows[] = ['Admission No.', $student->admission_number];
        $rows[] = ['Class', trim(($student->currentEnrollment?->course?->name ?? '').' '.($student->currentEnrollment?->section?->name ?? ''))];
        $rows[] = ['Enrolled', $student->admission_date?->toDateString()];
        $rows[] = ['Period', $scope['start']?->toDateString() ?? 'From enrolment', 'to', $scope['end']?->toDateString() ?? 'today'];
        $rows[] = [];
        $rows[] = ['Date', 'Description', 'Receipt', 'Reference', 'Method', 'Debit ($)', 'Credit ($)', 'Balance ($)', 'Received By'];

        foreach ($ledger['rows'] as $row) {
            $rows[] = [
                ($row['date'] instanceof Carbon ? $row['date']->toDateString() : ''),
                $row['description'],
                $row['receipt'] ?? '',
                $row['reference'] ?? '',
                $row['method'] ?? '',
                number_format($row['debit'], 2, '.', ''),
                number_format($row['credit'], 2, '.', ''),
                number_format($row['running_balance'], 2, '.', ''),
                $row['received_by'] ?? '',
            ];
        }

        $rows[] = [];
        $rows[] = ['Opening Balance', number_format($ledger['opening_balance'], 2)];
        $rows[] = ['Total Billed', number_format($ledger['total_billed'], 2)];
        $rows[] = ['Total Paid', number_format($ledger['total_paid'], 2)];
        $rows[] = ['Total Refunded', number_format($ledger['total_refunded'], 2)];
        $rows[] = ['Closing Balance', number_format($ledger['closing_balance'], 2)];

        $safeAdm = str_replace(['/', '\\'], '_', $student->admission_number);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($out, $row, escape: '\\');
            }
            fclose($out);
        }, 'Financial_History_'.$safeAdm.'_'.now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function downloadBulkCsv(): StreamedResponse
    {
        $schoolId = current_tenant()?->id;
        $scope = $this->scopeDates();

        $data = $this->buildSchoolSummary();

        $rows = [];
        $rows[] = ['Student Financial History — Bulk Export'];
        $rows[] = ['Period', $scope['start']?->toDateString() ?? 'From enrolment', 'to', $scope['end']?->toDateString() ?? 'today'];
        $rows[] = [];
        $rows[] = ['Student Name', 'Admission No', 'Class', 'Gender', 'Total Billed ($)', 'Total Paid ($)', 'Balance ($)', 'Status'];

        foreach ($data['summaries'] as $s) {
            $rows[] = [
                trim($s['student']->full_name),
                $s['admission_number'] ?? '',
                $s['class'],
                ucfirst($s['gender'] ?? ''),
                number_format($s['billed'], 2, '.', ''),
                number_format($s['paid'], 2, '.', ''),
                number_format($s['balance'], 2, '.', ''),
                match ($s['status']) {
                    'paid' => 'Paid', 'partial' => 'Partially Paid', default => 'Unpaid'
                },
            ];
        }

        $rows[] = [];
        $rows[] = ['TOTALS', '', '', '', number_format($data['total_billed'], 2), number_format($data['total_paid'], 2), number_format($data['total_balance'], 2)];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($out, $row, escape: '\\');
            }
            fclose($out);
        }, 'Financial_History_Bulk_'.now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
