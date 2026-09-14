<?php

namespace App\Filament\App\Pages\Finance;

use Filament\Pages\Page;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Term;
use Modules\Finance\Services\StudentFinancialHistoryService;
use Modules\Students\Models\Student;

class StudentFinancialHistoryPage extends Page
{
    protected static string $view = 'filament.app.pages.finance.student-financial-history';

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Student Financial History';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'student-financial-history';

    public ?int $student_id = null;

    public ?int $year_id = null;

    public ?int $term_id = null;

    public ?string $start_date = null;

    public ?string $end_date = null;

    public string $scope = 'active_year';

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

    protected function getViewData(): array
    {
        $schoolId = current_tenant()?->id;
        $scope = $this->scopeDates();

        $data = [
            'student' => null,
            'ledger' => [],
            'students' => Student::withoutTenantScope()
                ->where('school_id', $schoolId)
                ->orderBy('last_name')
                ->get()
                ->mapWithKeys(fn ($s) => [$s->id => trim($s->full_name.' ('.($s->admission_number ?? '').')')]),
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
            return $data;
        }

        $student = Student::withoutTenantScope()
            ->with(['currentEnrollment.course', 'currentEnrollment.section'])
            ->find($this->student_id);

        if (! $student || $student->school_id !== $schoolId) {
            return $data;
        }

        $ledger = StudentFinancialHistoryService::buildLedger($student, $scope['start'], $scope['end']);

        return array_merge($data, [
            'student' => $student,
            'ledger' => $ledger,
        ]);
    }

    public function downloadCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
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
                ($row['date'] instanceof \Carbon\Carbon ? $row['date']->toDateString() : ''),
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
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'Financial_History_'.$safeAdm.'_'.now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv']);
    }
}