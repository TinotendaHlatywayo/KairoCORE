<?php

namespace App\Filament\App\Pages\Finance;

use Carbon\Carbon;
use Filament\Pages\Page;
use Modules\Finance\Services\StudentFinancialHistoryService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeeCollectionsPage extends Page
{
    protected static string $view = 'filament.app.pages.finance.fee-collections';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Fee Collections';

    protected static ?int $navigationSort = 5;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'fee-collections';

    public string $range = 'today';

    public ?string $start_date = null;

    public ?string $end_date = null;

    public function getTitle(): string
    {
        return __('Daily & Range Fee Collections');
    }

    public static function getNavigationLabel(): string
    {
        return __('Fee Collections');
    }

    /**
     * Resolve the inclusive collection window from the selected preset.
     *
     * @return array{start: Carbon, end: Carbon, label: string}
     */
    protected function resolveRange(): array
    {
        if ($this->range === 'custom' && $this->start_date && $this->end_date) {
            return [
                'start' => Carbon::parse($this->start_date)->startOfDay(),
                'end' => Carbon::parse($this->end_date)->endOfDay(),
                'label' => 'Custom range ('.$this->start_date.' to '.$this->end_date.')',
            ];
        }

        return match ($this->range) {
            'week' => [
                'start' => now()->startOfDay()->subDays(6),
                'end' => now()->endOfDay(),
                'label' => 'Last 7 days',
            ],
            'month' => [
                'start' => now()->startOfDay()->subDays(29),
                'end' => now()->endOfDay(),
                'label' => 'Last 30 days',
            ],
            default => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
                'label' => 'Today',
            ],
        };
    }

    protected function getViewData(): array
    {
        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id;
        $range = $this->resolveRange();
        $data = StudentFinancialHistoryService::collectionsForRange($schoolId, $range['start'], $range['end']);

        return [
            'label' => $range['label'],
            'range' => $this->range,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'total' => $data['total'],
            'refunds' => $data['refunds'],
            'payments_count' => $data['payments_count'],
            'students' => $data['students'],
            'student_count' => $data['students']->count(),
            'payments' => $data['payments'],
            'start' => $range['start']->toDateString(),
            'end' => $range['end']->toDateString(),
        ];
    }

    public function downloadCsv(): StreamedResponse
    {
        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id;
        $range = $this->resolveRange();
        $data = StudentFinancialHistoryService::collectionsForRange($schoolId, $range['start'], $range['end']);

        $rows = [];
        $rows[] = ['Fee Collections — '.$range['label']];
        $rows[] = ['Period', $range['start']->toDateString(), 'to', $range['end']->toDateString()];
        $rows[] = ['Total Collected', number_format($data['total'], 2)];
        $rows[] = ['Refunds', number_format($data['refunds'], 2)];
        $rows[] = [];
        $rows[] = ['Date', 'Student', 'Admission', 'Form', 'Amount ($)', 'Method', 'Receipt No.', 'Reference', 'Received By'];

        foreach ($data['payments'] as $payment) {
            $student = $payment->invoice?->student;
            $rows[] = [
                $payment->payment_date?->toDateString(),
                trim(($student->first_name ?? '').' '.($student->last_name ?? '')),
                $student->admission_number ?? '',
                trim(($student->currentEnrollment?->course?->name ?? '').' '.($student->currentEnrollment?->section?->name ?? '')),
                number_format((float) $payment->amount, 2, '.', ''),
                $payment->payment_method,
                $payment->receipt_number,
                $payment->reference_number,
                $payment->receivedBy?->name ?? '',
            ];
        }

        $filename = 'Fee_Collections_'.$range['start']->format('Ymd').'-'.$range['end']->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function downloadExcel(): StreamedResponse
    {
        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id;
        $range = $this->resolveRange();
        $data = StudentFinancialHistoryService::collectionsForRange($schoolId, $range['start'], $range['end']);

        return \Modules\Finance\Services\FinancialHistoryExcelService::downloadCollections(
            $data,
            $range['label'],
            $range['start'],
            $range['end'],
        );
    }
}
