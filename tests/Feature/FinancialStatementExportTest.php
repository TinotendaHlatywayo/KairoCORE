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

    private function statementDataWithBreakdown(): array
    {
        $data = $this->statementData();

        $data['accountBreakdown'] = [
            'feeBreakdown' => [
                ['account' => 'Stanbic Bank Zimbabwe — School Operating Account', 'amount' => 195.0],
                ['account' => 'EcoCash', 'amount' => 95.0],
                ['account' => 'Cash', 'amount' => 55.0],
            ],
            'incomeBreakdown' => [
                ['account' => 'Stanbic Bank Zimbabwe — School Operating Account', 'amount' => 120.0],
            ],
            'refundBreakdown' => [
                ['account' => 'Stanbic Bank Zimbabwe — School Operating Account', 'amount' => 20.0],
            ],
            'expenseBreakdown' => [
                ['account' => 'Stanbic Bank Zimbabwe — School Operating Account', 'amount' => 200.0],
                ['account' => 'EcoCash', 'amount' => 50.0],
            ],
            'openingByAccount' => [
                ['account' => 'Stanbic Bank Zimbabwe — School Operating Account', 'amount' => 500.0],
                ['account' => 'EcoCash', 'amount' => 0.0],
            ],
            'closingByAccount' => [
                ['account' => 'Stanbic Bank Zimbabwe — School Operating Account', 'amount' => 595.0],
                ['account' => 'EcoCash', 'amount' => 0.0],
            ],
        ];

        return $data;
    }

    private function singleAccountStatementData(): array
    {
        $data = $this->statementData();
        $data['bankAccountName'] = 'Stanbic Bank Zimbabwe — School Operating Account';

        return $data;
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

    public function test_excel_statement_renders_per_account_breakdown_when_combined(): void
    {
        $response = FinancialReportService::download('xlsx', $this->statementDataWithBreakdown(), now()->subMonth(), now());
        $bytes = $this->captureStream($response);

        $path = tempnam(sys_get_temp_dir(), 'stmt').'.xlsx';
        file_put_contents($path, $bytes);

        try {
            $spreadsheet = IOFactory::load($path);
            $statement = $spreadsheet->getSheetByName('Statement');

            // Per-account fee lines replace the single "school fees" lump when
            // the combined view is active.
            $this->assertNotNull($this->rowOf($statement, 'EcoCash'));
            $this->assertNull($this->rowOf($statement, 'School fees recorded within the period'));

            $expenseBreakdownLabel = $this->rowOf($statement, 'By Account');
            $this->assertNotNull($expenseBreakdownLabel);

            // Per-account opening/closing balances appear under the combined
            // closing balance.
            $this->assertNotNull($this->rowOf($statement, 'Balances by Bank Account'));
            $this->assertNotNull($this->rowOf($statement, 'Stanbic Bank Zimbabwe — School Operating Account (Closing)'));
        } finally {
            @unlink($path);
        }
    }

    public function test_excel_statement_keeps_lumps_when_single_account_selected(): void
    {
        $response = FinancialReportService::download('xlsx', $this->singleAccountStatementData(), now()->subMonth(), now());
        $bytes = $this->captureStream($response);

        $path = tempnam(sys_get_temp_dir(), 'stmt').'.xlsx';
        file_put_contents($path, $bytes);

        try {
            $spreadsheet = IOFactory::load($path);
            $statement = $spreadsheet->getSheetByName('Statement');

            // A single-account export keeps the classic "school fees" lump and
            // no per-account breakdown blocks.
            $this->assertNotNull($this->rowOf($statement, 'School fees recorded within the period'));
            $this->assertNull($this->rowOf($statement, 'By Account'));
            $this->assertNull($this->rowOf($statement, 'Balances by Bank Account'));
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

    public function test_pdf_statement_renders_per_account_breakdown_when_combined(): void
    {
        $html = view('finance.financial-statement-pdf', ['data' => $this->statementDataWithBreakdown()])->render();

        $this->assertStringContainsString('EcoCash', $html);
        $this->assertStringContainsString('By Account', $html);
        $this->assertStringContainsString('Balances by Bank Account', $html);
        $this->assertStringContainsString('Stanbic Bank Zimbabwe — School Operating Account — Closing', $html);
        $this->assertStringNotContainsString('School fees recorded within the period', $html);
    }

    public function test_pdf_statement_falls_back_to_lumps_for_single_account(): void
    {
        $html = view('finance.financial-statement-pdf', ['data' => $this->singleAccountStatementData()])->render();

        $this->assertStringContainsString('School fees recorded within the period', $html);
        $this->assertStringNotContainsString('By Account', $html);
        $this->assertStringNotContainsString('Balances by Bank Account', $html);
    }

    public function test_csv_and_txt_statements_include_per_account_breakdown_when_combined(): void
    {
        $csv = $this->captureStream(FinancialReportService::download('csv', $this->statementDataWithBreakdown(), now()->subMonth(), now()));
        $txt = $this->captureStream(FinancialReportService::download('txt', $this->statementDataWithBreakdown(), now()->subMonth(), now()));

        $this->assertStringContainsString('Stanbic Bank Zimbabwe — School Operating Account', $csv);
        $this->assertStringContainsString('EcoCash', $csv);
        $this->assertStringContainsString('Balances by Bank Account', $csv);
        $this->assertStringNotContainsString('School fees recorded within the period', $csv);

        $this->assertStringContainsString('Stanbic Bank Zimbabwe — School Operating Account', $txt);
        $this->assertStringContainsString('Balances by Bank Account', $txt);
        $this->assertStringNotContainsString('School fees recorded within the period', $txt);
    }

    public function test_engine_groups_items_into_per_account_breakdown(): void
    {
        $engine = app(\Modules\Finance\Services\FinancialAnalyticsEngine::class);

        $breakdown = $engine->buildStatementAccountBreakdown(
            feeItems: [
                ['account_id' => 1, 'account' => 'Stanbic', 'method' => 'Bank Transfer', 'amount' => 195.0],
                ['account_id' => null, 'account' => null, 'method' => 'EcoCash', 'amount' => 95.0],
                ['account_id' => null, 'account' => null, 'method' => null, 'amount' => 55.0],
            ],
            incomeItems: [
                ['account_id' => 1, 'account' => 'Stanbic', 'method' => null, 'amount' => 120.0],
            ],
            refundItems: [
                ['account_id' => 1, 'account' => 'Stanbic', 'method' => 'Bank Transfer', 'amount' => 20.0],
            ],
            expenseItems: [
                ['account_id' => 1, 'account' => 'Stanbic', 'method' => null, 'amount' => 200.0],
                ['account_id' => null, 'account' => null, 'method' => null, 'amount' => 50.0],
            ],
            bankAccounts: [
                ['id' => 1, 'bank_name' => 'Stanbic Bank Zimbabwe', 'account_name' => 'School Operating Account', 'balance' => 500.0, 'created_at' => '2020-01-01'],
            ],
            defaultAccountId: 1,
            startDate: '2026-09-01',
        );

        $labels = array_column($breakdown['feeBreakdown'], 'account');
        $this->assertContains('Stanbic Bank Zimbabwe — School Operating Account', $labels);
        $this->assertContains('EcoCash', $labels);

        // The unassigned fee (no account, no method) is booked to the default
        // account, mirroring SchoolBankAccount::filterClosure().
        $defaultFee = collect($breakdown['feeBreakdown'])->firstWhere('account', 'Stanbic Bank Zimbabwe — School Operating Account');
        $this->assertSame(250.0, $defaultFee['amount']);

        // Fees + income − refunds − expenses reconciled per account. The
        // EcoCash fee (95) is also booked to the default account id because it
        // has no bank account: 500 + 345 + 120 − 20 − 250 = 695.
        $this->assertSame(695.0, $breakdown['closingByAccount'][0]['amount']);
        $this->assertSame(500.0, $breakdown['openingByAccount'][0]['amount']);
    }
}
