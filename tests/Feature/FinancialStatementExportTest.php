<?php

namespace Tests\Feature;

use Modules\Finance\Services\FinancialReportService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class FinancialStatementExportTest extends TestCase
{
    private function captureStream(StreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }

    private function statementData(): array
    {
        return [
            'school' => 'Test School',
            'companyTagline' => 'Learn',
            'bankAccountName' => 'All Accounts (Combined)',
            'startDate' => '2026-09-01',
            'endDate' => '2026-09-30',
            'generatedAt' => '2026-09-30 10:00',
            'openingBalance' => 0.0,
            'feeRevenue' => 300.0,
            'revenueStreams' => [
                ['name' => 'Donation', 'category' => 'Other Income', 'amount' => 120.0, 'date' => '2026-09-10', 'bank' => 'Main'],
            ],
            'revenueStreamTotal' => 120.0,
            'totalRevenue' => 400.0,
            'totalRefunds' => 20.0,
            'refundItems' => [
                ['reference' => 'RCP-1', 'amount' => 20.0, 'date' => '2026-09-12', 'account' => 'Main'],
            ],
            'totalExpenses' => 250.0,
            'expenseItems' => [
                ['name' => 'Salaries September', 'category' => 'Payroll & Compensation', 'amount' => 200.0, 'date' => '2026-09-25', 'reference' => 'PAY-1'],
                ['name' => 'Utilities', 'category' => 'Utilities', 'amount' => 50.0, 'date' => '2026-09-05', 'reference' => 'EXP-1'],
            ],
            'totalInflows' => 420.0,
            'totalOutflows' => 270.0,
            'netCashFlow' => 150.0,
            'closingBalance' => 150.0,
            'template' => null,
            'schoolModel' => null,
            'config' => [],
        ];
    }

    private function rowOf(Worksheet $sheet, string $needle, string $column = 'B'): ?int
    {
        foreach ($sheet->getRowIterator() as $row) {
            $value = (string) $sheet->getCell($column.$row->getRowIndex())->getValue();
            if ($value === $needle) {
                return $row->getRowIndex();
            }
        }

        return null;
    }

    public function test_excel_statement_has_split_columns_and_two_sheets(): void
    {
        $response = FinancialReportService::download('xlsx', $this->statementData(), now()->subMonth(), now());
        $bytes = $this->captureStream($response);

        $this->assertStringStartsWith('PK', $bytes, 'XLSX must be a valid ZIP container');

        $path = tempnam(sys_get_temp_dir(), 'stmt').'.xlsx';
        file_put_contents($path, $bytes);

        try {
            $spreadsheet = IOFactory::load($path);
            $this->assertSame(['Statement', 'Details'], $spreadsheet->getSheetNames());

            $statement = $spreadsheet->getSheetByName('Statement');
            $header = $this->rowOf($statement, 'Opening Bank Balance') - 1;
            $this->assertSame('Outflows (−)', $statement->getCell('C'.$header)->getValue());
            $this->assertSame('Inflows (+)', $statement->getCell('D'.$header)->getValue());
            $this->assertSame('Balance (USD)', $statement->getCell('E'.$header)->getValue());

            // Column totals contain only real period movements.
            $totals = $this->rowOf($statement, 'Total Outflows (−) / Total Inflows (+)');
            $this->assertNotNull($totals);
            $this->assertSame(-270.0, (float) $statement->getCell('C'.$totals)->getValue());
            $this->assertSame(420.0, (float) $statement->getCell('D'.$totals)->getValue());

            // The salaries memo line has been removed entirely from the statement.
            $memo = $this->rowOf($statement, 'Of which — Staff Salaries — 2026-09-25 — included in the Payroll line above (USD 200.00)');
            $this->assertNull($memo);
            $this->assertNull($this->rowOf($statement, 'Of which — Staff Salaries'));

            $closing = $this->rowOf($statement, 'Closing Balance');
            $this->assertNotNull($closing);
            $this->assertSame(150.0, (float) $statement->getCell('E'.$closing)->getValue());

            // The redundant net-revenue subtotal row is gone.
            $this->assertNull($this->rowOf($statement, 'Total Revenue / Inflows (Net of Refunds)'));

            $details = $spreadsheet->getSheetByName('Details');
            $this->assertSame('Inflow (+)', $details->getCell('F1')->getValue());
            $this->assertSame('Outflow (−)', $details->getCell('G1')->getValue());

            $donation = $this->rowOf($details, 'Donation', 'C');
            $this->assertNotNull($donation);
            $this->assertSame(120.0, (float) $details->getCell('F'.$donation)->getValue());

            $utilities = $this->rowOf($details, 'Utilities', 'C');
            $this->assertNotNull($utilities);
            $this->assertSame(-50.0, (float) $details->getCell('G'.$utilities)->getValue());

            $detailTotals = $this->rowOf($details, 'TOTAL');
            $this->assertNotNull($detailTotals);
            $this->assertSame(420.0, (float) $details->getCell('F'.$detailTotals)->getValue());
            $this->assertSame(-270.0, (float) $details->getCell('G'.$detailTotals)->getValue());
        } finally {
            @unlink($path);
        }
    }

    public function test_pdf_statement_renders_split_columns_and_salaries_date(): void
    {
        $html = view('finance.financial-statement-pdf', ['data' => $this->statementData()])->render();

        $this->assertStringContainsString('Outflows (−)', $html);
        $this->assertStringContainsString('Inflows (+)', $html);
        $this->assertStringNotContainsString('Of which — Staff Salaries', $html);
        $this->assertStringNotContainsString('Total Revenue / Inflows (Net of Refunds)', $html);
    }
}
