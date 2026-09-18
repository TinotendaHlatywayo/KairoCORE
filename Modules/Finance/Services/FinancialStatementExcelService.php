<?php

namespace Modules\Finance\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Builds the styled Excel (XLSX) version of the official Financial Statement.
 *
 * Two sheets are produced:
 *  - "Statement": the presentable statement with separate Outflows (−) and
 *    Inflows (+) columns, their totals, Net Cash Flow and Closing Balance.
 *  - "Details": a flat, filterable transaction list (date, type, description,
 *    category, reference, inflow, outflow) so the period can be analysed.
 */
class FinancialStatementExcelService
{
    private const HEADER_FILL = '1D4ED8';

    private const HEADER_FONT = 'FFFFFF';

    private const TOTAL_FILL = 'E0F2FE';

    private const INFLOW_FONT = '15803D';

    private const OUTFLOW_FONT = 'B91C1C';

    private const MUTED_FONT = '6B7280';

    private const SIGNED_FORMAT = '+$#,##0.00;-$#,##0.00';

    private const BALANCE_FORMAT = '$#,##0.00';

    public static function download(array $data, $startDate, $endDate): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

        self::buildStatementSheet($spreadsheet->getActiveSheet(), $data);
        self::buildDetailsSheet($spreadsheet->createSheet(), $data);
        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'financial-statement-'.$startDate->toDateString().'-to-'.$endDate->toDateString().'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new XlsxWriter($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private static function buildStatementSheet(Worksheet $sheet, array $data): void
    {
        $sheet->setTitle('Statement');
        $row = 1;

        $sheet->setCellValue("A{$row}", $data['school'] ?? 'School');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(16);
        $row++;

        if (! empty($data['companyTagline'])) {
            $sheet->setCellValue("A{$row}", $data['companyTagline']);
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->getColor()->setARGB(self::MUTED_FONT);
            $row++;
        }

        $sheet->setCellValue("A{$row}", 'Official Financial Statement & Cash Flow');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(13);
        $row++;
        $sheet->setCellValue("A{$row}", 'Reporting Period: '.$data['startDate'].' to '.$data['endDate']);
        $row++;
        $sheet->setCellValue("A{$row}", 'Bank Account: '.($data['bankAccountName'] ?? 'All Accounts (Combined)'));
        $row++;
        $sheet->setCellValue("A{$row}", 'Generated: '.($data['generatedAt'] ?? ''));
        $sheet->getStyle("A{$row}")->getFont()->getColor()->setARGB(self::MUTED_FONT);
        $row += 2;

        foreach (['Date', 'Description', 'Outflows (−)', 'Inflows (+)', 'Balance (USD)'] as $i => $header) {
            $cell = Coordinate::stringFromColumnIndex($i + 1).$row;
            $sheet->setCellValue($cell, $header);
            self::styleHeader($sheet, $cell, $i);
        }
        $headerRow = $row;
        $row++;

        self::statementRow($sheet, $row++, null, 'Opening Bank Balance', null, null, (float) ($data['openingBalance'] ?? 0), true);

        self::statementRow($sheet, $row++, null, 'Total Fees Collected (school fees in period)', null, (float) ($data['feeRevenue'] ?? 0), null, true);

        foreach ($data['revenueStreams'] ?? [] as $stream) {
            self::statementRow($sheet, $row++, $stream['date'] ?? null, $stream['name'].' ('.($stream['category'] ?? 'Other Income').')', null, (float) $stream['amount'], null);
        }

        self::statementRow($sheet, $row++, null, 'Less Refunds Issued', (float) ($data['totalRefunds'] ?? 0), null, null, true);

        foreach ($data['refundItems'] ?? [] as $refund) {
            self::statementRow($sheet, $row++, $refund['date'] ?? null, $refund['reference'] ?? 'Refund', (float) $refund['amount'], null, null);
        }

        self::statementRow($sheet, $row++, null, 'Total Expenses & Outflows', (float) ($data['totalExpenses'] ?? 0), null, null, true);

        foreach ($data['expenseItems'] ?? [] as $expense) {
            $description = $expense['name'].' ('.($expense['category'] ?? 'Uncategorised').')'
                .(! empty($expense['reference']) ? ' — '.$expense['reference'] : '');
            self::statementRow($sheet, $row++, $expense['date'] ?? null, $description, (float) $expense['amount'], null, null);
        }

        $salaryDescription = 'Of which — Staff Salaries'.(! empty($data['salariesDate']) ? ' — '.$data['salariesDate'] : '');
        self::statementRow($sheet, $row++, null, $salaryDescription, (float) ($data['totalSalaries'] ?? 0), null, null);

        self::statementRow($sheet, $row++, null, 'Total Outflows (−) / Total Inflows (+)', (float) ($data['totalOutflows'] ?? 0), (float) ($data['totalInflows'] ?? 0), null, true, true);

        self::statementRow($sheet, $row++, null, 'Net Cash Flow Balance', null, null, (float) ($data['netCashFlow'] ?? 0), true, true);
        self::statementRow($sheet, $row, null, 'Closing Balance', null, null, (float) ($data['closingBalance'] ?? 0), true, true);

        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(54);
        $sheet->getColumnDimension('C')->setWidth(16);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->freezePane('A'.($headerRow + 1));
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, $headerRow);
    }

    private static function buildDetailsSheet(Worksheet $sheet, array $data): void
    {
        $sheet->setTitle('Details');
        $row = 1;

        foreach (['Date', 'Type', 'Description', 'Category', 'Reference', 'Inflow (+)', 'Outflow (−)'] as $i => $header) {
            $cell = Coordinate::stringFromColumnIndex($i + 1).$row;
            $sheet->setCellValue($cell, $header);
            self::styleHeader($sheet, $cell, $i);
        }
        $headerRow = $row;
        $row++;

        // School fees are recorded in the ledger as a period total (individual
        // receipts live under fee collections), so expose one aggregate row.
        self::detailsRow($sheet, $row++, null, 'Fee Collection', 'Total Fees Collected', 'School Fees', null, (float) ($data['feeRevenue'] ?? 0), null);

        foreach ($data['revenueStreams'] ?? [] as $stream) {
            self::detailsRow($sheet, $row++, $stream['date'] ?? null, 'Other Income', $stream['name'], $stream['category'] ?? 'Other Income', null, (float) $stream['amount'], null);
        }

        foreach ($data['refundItems'] ?? [] as $refund) {
            self::detailsRow($sheet, $row++, $refund['date'] ?? null, 'Refund', 'Refund issued', null, $refund['reference'] ?? null, null, (float) $refund['amount']);
        }

        foreach ($data['expenseItems'] ?? [] as $expense) {
            self::detailsRow($sheet, $row++, $expense['date'] ?? null, 'Expense', $expense['name'], $expense['category'] ?? 'Uncategorised', $expense['reference'] ?? null, null, (float) $expense['amount']);
        }

        // Totals row
        $sheet->setCellValue("A{$row}", '');
        $sheet->setCellValue("B{$row}", 'TOTAL');
        $sheet->getStyle("A{$row}:G{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:G{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::TOTAL_FILL);
        self::signedCell($sheet, "F{$row}", (float) ($data['totalInflows'] ?? 0), self::INFLOW_FONT);
        self::signedCell($sheet, "G{$row}", -(float) ($data['totalOutflows'] ?? 0), self::OUTFLOW_FONT);

        foreach (['A' => 14, 'B' => 15, 'C' => 44, 'D' => 22, 'E' => 22, 'F' => 16, 'G' => 16] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $sheet->setAutoFilter("A{$headerRow}:G{$headerRow}");
        $sheet->freezePane('A'.($headerRow + 1));
        $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToWidth(1);
        $sheet->getPageSetup()->setFitToHeight(0);
    }

    private static function statementRow(
        Worksheet $sheet,
        int $row,
        ?string $date,
        string $description,
        ?float $outflow,
        ?float $inflow,
        ?float $balance,
        bool $bold = false,
        bool $total = false,
    ): void {
        $sheet->setCellValueExplicit("A{$row}", (string) ($date ?? ''), DataType::TYPE_STRING);
        $sheet->setCellValue("B{$row}", $description);

        if ($outflow !== null) {
            self::signedCell($sheet, "C{$row}", -abs($outflow), self::OUTFLOW_FONT);
        }
        if ($inflow !== null) {
            self::signedCell($sheet, "D{$row}", abs($inflow), self::INFLOW_FONT);
        }
        if ($balance !== null) {
            $sheet->setCellValue("E{$row}", $balance);
            $sheet->getStyle("E{$row}")->getNumberFormat()->setFormatCode(self::BALANCE_FORMAT);
        }

        $sheet->getStyle("C{$row}:E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A{$row}:E{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        if ($bold) {
            $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true);
        }
        if ($total) {
            $sheet->getStyle("A{$row}:E{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::TOTAL_FILL);
        }
    }

    private static function detailsRow(
        Worksheet $sheet,
        int $row,
        ?string $date,
        string $type,
        string $description,
        ?string $category,
        ?string $reference,
        ?float $inflow,
        ?float $outflow,
    ): void {
        $sheet->setCellValueExplicit("A{$row}", (string) ($date ?? ''), DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("B{$row}", $type, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("C{$row}", $description, DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("D{$row}", (string) ($category ?? ''), DataType::TYPE_STRING);
        $sheet->setCellValueExplicit("E{$row}", (string) ($reference ?? ''), DataType::TYPE_STRING);

        if ($inflow !== null) {
            self::signedCell($sheet, "F{$row}", abs($inflow), self::INFLOW_FONT);
        }
        if ($outflow !== null) {
            self::signedCell($sheet, "G{$row}", -abs($outflow), self::OUTFLOW_FONT);
        }
    }

    private static function signedCell(Worksheet $sheet, string $cell, float $value, string $color): void
    {
        $sheet->setCellValue($cell, round($value, 2));
        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode(self::SIGNED_FORMAT);
        $sheet->getStyle($cell)->getFont()->getColor()->setARGB($color);
        $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    private static function styleHeader(Worksheet $sheet, string $cell, int $index): void
    {
        $sheet->getStyle($cell)->getFont()->setBold(true);
        $sheet->getStyle($cell)->getFont()->getColor()->setARGB(self::HEADER_FONT);
        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::HEADER_FILL);
        $sheet->getStyle($cell)->getAlignment()->setHorizontal($index >= 2 ? Alignment::HORIZONTAL_RIGHT : Alignment::HORIZONTAL_LEFT);
    }
}
