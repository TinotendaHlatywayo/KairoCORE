<?php

namespace Modules\Finance\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Builds styled Microsoft Excel (XLSX) exports for the Student Financial
 * History feature. Mirrors the layout of the CSV downloads but with proper
 * column widths, styled header rows, currency formatting and print setup so
 * the workbook is presentable straight out of the box.
 */
class FinancialHistoryExcelService
{
    protected const HEADER_FILL = '1D4ED8';
    protected const HEADER_FONT = 'FF';
    protected const TOTAL_FILL = 'E0F2FE';
    protected const SECTION_FILL = 'F3F4F6';

    /** Styled single-student statement workbook as a streamed download. */
    public static function downloadStatement(array $ledger, $student, array $scope): StreamedResponse
    {
        $spreadsheet = self::statementWorkbook($ledger, $student, $scope);

        return self::stream($spreadsheet, 'Financial_History_'.self::safe($student->admission_number).'_'.now()->format('Ymd_His').'.xlsx');
    }

    /** Styled whole-school summary workbook as a streamed download. */
    public static function downloadSummary(array $data, array $scope): StreamedResponse
    {
        $spreadsheet = self::summaryWorkbook($data, $scope);

        return self::stream($spreadsheet, 'Financial_History_Bulk_'.now()->format('Ymd_His').'.xlsx');
    }

    /** Styled fee-collections workbook (period summary) as a streamed download. */
    public static function downloadCollections(array $data, string $rangeLabel, $start, $end): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Fee Collections');

        $row = 1;
        $sheet->setCellValue("A{$row}", 'Fee Collections — '.$rangeLabel);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(16);
        $row++;

        $sheet->setCellValue("A{$row}", 'Period');
        $sheet->setCellValue("B{$row}", $start->toDateString().' to '.$end->toDateString());
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue("A{$row}", 'Total Collected');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        self::currencyCell($sheet, "B{$row}", (float) ($data['total'] ?? 0));
        $row++;
        $sheet->setCellValue("A{$row}", 'Refunds');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        self::currencyCell($sheet, "B{$row}", (float) ($data['refunds'] ?? 0));
        $row += 2;

        $columns = ['Date', 'Student', 'Admission', 'Form', 'Amount ($)', 'Method', 'Receipt No.', 'Reference', 'Received By'];
        self::writeHeaderRow($sheet, $row, $columns);

        foreach ($data['payments'] as $payment) {
            $student = $payment->invoice?->student;
            $row++;
            $sheet->setCellValueExplicit("A{$row}", $payment->payment_date?->toDateString() ?? '', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$row}", trim((string) (($student->first_name ?? '').' '.($student->last_name ?? ''))), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$row}", (string) ($student->admission_number ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$row}", trim((string) (($student->currentEnrollment?->course?->name ?? '').' '.($student->currentEnrollment?->section?->name ?? ''))), DataType::TYPE_STRING);
            self::currencyCell($sheet, "E{$row}", (float) ($payment->amount ?? 0));
            $sheet->setCellValueExplicit("F{$row}", (string) ($payment->payment_method ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$row}", (string) ($payment->receipt_number ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("H{$row}", (string) ($payment->reference_number ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("I{$row}", (string) ($payment->receivedBy?->name ?? ''), DataType::TYPE_STRING);
        }

        self::decorateSheet($sheet, range('A', 'I'));

        return self::stream($spreadsheet, 'Fee_Collections_'.$start->format('Ymd').'-'.$end->format('Ymd').'.xlsx');
    }

    /**
     * Styled multi-student workbook with one sheet per student, mirroring the
     * bulk CSV layout of the document route.
     *
     * @param  array<int, object>  $students
     */
    public static function downloadBulkLedgers(iterable $students, array $scope, string $scopeLabel = ''): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        foreach ($students as $student) {
            $ledger = StudentFinancialHistoryService::buildLedger($student, $scope['start'] ?? null, $scope['end'] ?? null);

            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(self::sheetTitle($student));

            $row = 1;
            $sheet->setCellValue("A{$row}", 'Student Financial History'.($scopeLabel !== '' ? ' — '.$scopeLabel : ''));
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
            $row += 2;

            $headers = [
                'Student' => $student->full_name,
                'Admission No.' => $student->admission_number,
                'Class' => trim(($student->currentEnrollment?->course?->name ?? '').' '.($student->currentEnrollment?->section?->name ?? '')),
                'Enrolled' => $student->admission_date?->toDateString(),
            ];

            foreach ($headers as $label => $value) {
                $sheet->setCellValue("A{$row}", $label);
                $sheet->setCellValue("B{$row}", (string) $value);
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $row++;
            }

            $row += 1;

            $columns = ['Date', 'Description', 'Receipt', 'Reference', 'Method', 'Debit ($)', 'Credit ($)', 'Balance ($)', 'Received By'];
            self::writeHeaderRow($sheet, $row, $columns);

            foreach ($ledger['rows'] as $entry) {
                $row++;
                $sheet->setCellValueExplicit("A{$row}", $entry['date'] instanceof \Carbon\Carbon ? $entry['date']->toDateString() : '', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("B{$row}", (string) ($entry['description'] ?? ''), DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("C{$row}", (string) ($entry['receipt'] ?? ''), DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("D{$row}", (string) ($entry['reference'] ?? ''), DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("E{$row}", (string) ($entry['method'] ?? ''), DataType::TYPE_STRING);
                self::currencyCell($sheet, "F{$row}", (float) ($entry['debit'] ?? 0));
                self::currencyCell($sheet, "G{$row}", (float) ($entry['credit'] ?? 0));
                self::currencyCell($sheet, "H{$row}", (float) ($entry['running_balance'] ?? 0));
                $sheet->setCellValueExplicit("I{$row}", (string) ($entry['received_by'] ?? ''), DataType::TYPE_STRING);
            }

            $row += 2;

            $totals = [
                'Opening Balance' => (float) $ledger['opening_balance'],
                'Closing Balance' => (float) $ledger['closing_balance'],
            ];
            foreach ($totals as $label => $value) {
                $sheet->setCellValue("A{$row}", $label);
                $sheet->getStyle("A{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::TOTAL_FILL);
                self::currencyCell($sheet, "B{$row}", $value);
                $sheet->getStyle("B{$row}")->getFont()->setBold(true);
                $row++;
            }

            self::decorateSheet($sheet, range('A', 'I'));
        }

        return self::stream($spreadsheet, 'Financial_Histories_'.now()->format('Ymd_His').'.xlsx');
    }

    protected static function sheetTitle($student): string
    {
        $name = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', trim((string) $student->full_name));
        $name = mb_substr($name, 0, 28);

        return $name !== '' ? $name : 'Student '.$student->id;
    }

    protected static function statementWorkbook(array $ledger, $student, array $scope): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Financial History');

        $row = 1;

        $sheet->setCellValue("A{$row}", 'Student Financial History');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $row++;

        $headers = [
            'Student' => $student->full_name,
            'Admission No.' => $student->admission_number,
            'Class' => trim(($student->currentEnrollment?->course?->name ?? '').' '.($student->currentEnrollment?->section?->name ?? '')),
            'Enrolled' => $student->admission_date?->toDateString(),
            'Period' => ($scope['start']?->toDateString() ?? 'From enrolment').' to '.($scope['end']?->toDateString() ?? 'today'),
            'Billing' => ucfirst(FinanceSettingsService::billingFrequency((int) ($student->school_id ?? current_tenant()?->id))),
        ];

        foreach ($headers as $label => $value) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("B{$row}", (string) $value);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
        }

        $row += 1;

        // Ledger columns
        $columns = ['Date', 'Description', 'Receipt', 'Reference', 'Method', 'Debit ($)', 'Credit ($)', 'Balance ($)', 'Received By'];
        self::writeHeaderRow($sheet, $row, $columns);

        foreach ($ledger['rows'] as $entry) {
            $row++;
            $sheet->setCellValueExplicit("A{$row}", $entry['date'] instanceof \Carbon\Carbon ? $entry['date']->toDateString() : '', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$row}", (string) ($entry['description'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$row}", (string) ($entry['receipt'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$row}", (string) ($entry['reference'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$row}", (string) ($entry['method'] ?? ''), DataType::TYPE_STRING);
            self::currencyCell($sheet, "F{$row}", (float) ($entry['debit'] ?? 0));
            self::currencyCell($sheet, "G{$row}", (float) ($entry['credit'] ?? 0));
            self::currencyCell($sheet, "H{$row}", (float) ($entry['running_balance'] ?? 0));
            $sheet->setCellValueExplicit("I{$row}", (string) ($entry['received_by'] ?? ''), DataType::TYPE_STRING);
        }

        // Optional monthly summary block under the ledger when billing is monthly
        $monthly = $ledger['monthly_summary'] ?? [];
        if ($monthly) {
            $row += 2;
            $sheet->setCellValue("A{$row}", 'Monthly Summary');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(13);
            $row++;

            $monthColumns = ['Month', 'Billed ($)', 'Paid ($)', 'Refunded ($)', 'Balance ($)'];
            self::writeHeaderRow($sheet, $row, $monthColumns);
            $monthColLabel = 'A';
            foreach ($monthly as $bucket) {
                $row++;
                $sheet->setCellValueExplicit("A{$row}", (string) ($bucket['label'] ?? ''), DataType::TYPE_STRING);
                self::currencyCell($sheet, "B{$row}", (float) ($bucket['billed'] ?? 0));
                self::currencyCell($sheet, "C{$row}", (float) ($bucket['paid'] ?? 0));
                self::currencyCell($sheet, "D{$row}", (float) ($bucket['refunded'] ?? 0));
                self::currencyCell($sheet, "E{$row}", (float) ($bucket['balance'] ?? 0));
            }
        }

        $row += 2;

        $totals = [
            'Opening Balance' => (float) $ledger['opening_balance'],
            'Total Billed' => (float) $ledger['total_billed'],
            'Total Paid' => (float) $ledger['total_paid'],
            'Total Refunded' => (float) $ledger['total_refunded'],
            'Closing Balance' => (float) $ledger['closing_balance'],
        ];

        foreach ($totals as $label => $value) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::TOTAL_FILL);
            self::currencyCell($sheet, "B{$row}", $value);
            $sheet->getStyle("B{$row}")->getFont()->setBold(true);
            $row++;
        }

        self::decorateSheet($sheet, range('A', 'I'));

        return $spreadsheet;
    }

    protected static function summaryWorkbook(array $data, array $scope): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('School Summary');

        $row = 1;

        $sheet->setCellValue("A{$row}", 'Student Financial History — Bulk Export');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(16);
        $row++;

        $sheet->setCellValue("A{$row}", 'Period');
        $sheet->setCellValue("B{$row}", ($scope['start']?->toDateString() ?? 'From enrolment').' to '.($scope['end']?->toDateString() ?? 'today'));
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row += 2;

        $columns = ['Student Name', 'Admission No', 'Class', 'Gender', 'Total Billed ($)', 'Total Paid ($)', 'Balance ($)', 'Status'];
        self::writeHeaderRow($sheet, $row, $columns);

        foreach ($data['summaries'] as $summary) {
            $row++;
            $sheet->setCellValueExplicit("A{$row}", trim((string) ($summary['student']?->full_name ?? '')), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$row}", (string) ($summary['admission_number'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$row}", (string) ($summary['class'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$row}", ucfirst((string) ($summary['gender'] ?? '')), DataType::TYPE_STRING);
            self::currencyCell($sheet, "E{$row}", (float) ($summary['billed'] ?? 0));
            self::currencyCell($sheet, "F{$row}", (float) ($summary['paid'] ?? 0));
            self::currencyCell($sheet, "G{$row}", (float) ($summary['balance'] ?? 0));
            $sheet->setCellValueExplicit("H{$row}", self::statusLabel($summary['status'] ?? ''), DataType::TYPE_STRING);
        }

        $row += 1;

        $sheet->setCellValue("A{$row}", 'TOTALS');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::TOTAL_FILL);
        self::currencyCell($sheet, "E{$row}", (float) ($data['total_billed'] ?? 0));
        self::currencyCell($sheet, "F{$row}", (float) ($data['total_paid'] ?? 0));
        self::currencyCell($sheet, "G{$row}", (float) ($data['total_balance'] ?? 0));
        $sheet->getStyle("E{$row}:G{$row}")->getFont()->setBold(true);

        self::decorateSheet($sheet, range('A', 'H'));

        return $spreadsheet;
    }

    protected static function writeHeaderRow($sheet, int &$row, array $columns): void
    {
        foreach ($columns as $i => $header) {
            $cell = Coordinate::stringFromColumnIndex($i + 1).$row;
            $sheet->setCellValue($cell, $header);
            $sheet->getStyle($cell)->getFont()->setBold(true);
            $sheet->getStyle($cell)->getFont()->getColor()->setARGB(self::HEADER_FONT);
            $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::HEADER_FILL);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal($i >= 5 ? Alignment::HORIZONTAL_RIGHT : Alignment::HORIZONTAL_LEFT);
        }
    }

    protected static function currencyCell($sheet, string $cell, float $value): void
    {
        $sheet->setCellValue($cell, round($value, 2));
        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('$#,##0.00');
        $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    protected static function statusLabel(string $status): string
    {
        return match ($status) {
            'paid' => 'Paid',
            'partial' => 'Partially Paid',
            default => 'Unpaid',
        };
    }

    protected static function safe(string $value): string
    {
        return str_replace(['/', '\\'], '_', $value);
    }

    protected static function decorateSheet($sheet, array $columns): void
    {
        // Auto-size columns (bounded so descriptions never explode the layout)
        foreach ($columns as $i => $col) {
            $sheet->getColumnDimension($col)->setWidth(min(42, max(12, self::estimateWidth($sheet, $col))));
        }

        $sheet->freezePane('A2');
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
    }

    protected static function estimateWidth($sheet, string $col): float
    {
        $max = 10.0;
        foreach ($sheet->getColumnIterator($col, $col) as $column) {
            foreach ($column->getCellIterator() as $cell) {
                $len = mb_strlen((string) $cell->getValue());
                if ($len > $max) {
                    $max = (float) $len;
                }
            }
        }

        return $max + 4;
    }

    protected static function stream(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new XlsxWriter($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}