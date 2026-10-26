<?php

namespace Tests\Feature;

use App\Filament\App\Pages\Finance\FinancialStatementPage;
use App\Models\School;
use App\Security\CapabilityCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Finance\Models\FinancialStatementEntry;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Finance\Services\FinancialAnalyticsEngine;
use Modules\Finance\Services\FinancialStatementCsvService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class FinancialStatementImportTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => 'Historic Statement School',
            'subdomain' => 'historic-statement-'.uniqid(),
            'status' => 'active',
        ]);

        $this->actingAsTenant($this->school);
    }

    private function columnMap(): array
    {
        return array_combine(
            array_keys(FinancialStatementCsvService::columns()),
            array_column(FinancialStatementCsvService::columns(), 'label'),
        );
    }

    private function csv(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'financial-statement');
        $handle = fopen($path, 'w');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, FinancialStatementCsvService::templateHeaders(), escape: '\\');

        foreach ($rows as $row) {
            fputcsv($handle, $row, escape: '\\');
        }

        fclose($handle);

        return $path;
    }

    public function test_imports_historic_entries_and_merges_them_into_statement_totals(): void
    {
        $bank = SchoolBankAccount::create([
            'school_id' => $this->school->id,
            'bank_name' => 'Main Bank',
            'account_name' => 'School Operating Account',
            'account_number' => '1234567890',
            'balance' => 0,
            'is_active' => true,
            'is_default' => true,
        ]);

        $path = $this->csv([
            ['fee_income', '2024-02-15', 'Historic Tuition', 'Tuition Fees', '1200.00', 'Main Bank', 'HIST-1', 'Old register'],
            ['other_income', '2024-06-10', 'Historic Bus Hire', 'Other Income', '300.00', '', 'HIST-2', ''],
            ['refund', '2024-07-20', 'Historic Fee Refund', 'Refunds', '40.00', 'Main Bank', 'HIST-3', ''],
            ['expense', '2024-10-05', 'Historic Utilities', 'Utilities', '180.00', 'Main Bank', 'HIST-4', ''],
        ]);

        try {
            $result = FinancialStatementCsvService::import($path, $this->school->id, $this->columnMap(), null, [
                'requester_id' => null,
            ]);
        } finally {
            @unlink($path);
        }

        $this->assertSame(['success' => 4, 'total' => 4, 'failures' => []], $result);
        $this->assertDatabaseCount('financial_statement_entries', 4);
        $this->assertDatabaseHas('financial_statement_entries', [
            'school_id' => $this->school->id,
            'type' => FinancialStatementEntry::TYPE_FEE_INCOME,
            'description' => 'Historic Tuition',
            'bank_account_id' => $bank->id,
            'source' => 'import',
        ]);
        $this->assertDatabaseHas('financial_statement_entries', [
            'school_id' => $this->school->id,
            'type' => FinancialStatementEntry::TYPE_OTHER_INCOME,
            'bank_account_id' => null,
        ]);

        $engine = app(FinancialAnalyticsEngine::class);
        $start = '2024-01-01';
        $end = '2024-12-31';

        $this->assertSame(1200.0, $engine->getStatementEntryTotal($this->school->id, FinancialStatementEntry::TYPE_FEE_INCOME, $start, $end));
        $this->assertSame(300.0, $engine->getStatementEntryTotal($this->school->id, FinancialStatementEntry::TYPE_OTHER_INCOME, $start, $end));
        $this->assertSame(40.0, $engine->getStatementEntryTotal($this->school->id, FinancialStatementEntry::TYPE_REFUND, $start, $end));
        $this->assertSame(180.0, $engine->getStatementEntryTotal($this->school->id, FinancialStatementEntry::TYPE_EXPENSE, $start, $end));

        $this->assertSame('Historic Tuition', $engine->getFeeCollectionsForPeriod($this->school->id, $start, $end)[0]['name']);
        $this->assertSame('Historic Bus Hire', $engine->getRevenueStreamsForPeriod($this->school->id, $start, $end)[0]['name']);
        $this->assertSame('Historic Fee Refund', $engine->getRefundsForPeriod($this->school->id, $start, $end)[0]['name']);
        $this->assertSame('Historic Utilities', $engine->getExpensesForPeriod($this->school->id, $start, $end)[0]['name']);
    }

    public function test_rejects_invalid_rows_without_saving_them(): void
    {
        $path = $this->csv([
            ['banana', 'not-a-date', '', 'Category', '0', 'Not A Bank', '', ''],
        ]);

        try {
            $result = FinancialStatementCsvService::import($path, $this->school->id, $this->columnMap());
        } finally {
            @unlink($path);
        }

        $this->assertSame(0, $result['success']);
        $this->assertSame(1, $result['total']);
        $this->assertCount(1, $result['failures']);
        $this->assertDatabaseCount('financial_statement_entries', 0);

        $errors = implode(' ', $result['failures'][0]['errors']);
        $this->assertStringContainsString('Transaction Type', $errors);
        $this->assertStringContainsString('Date is required', $errors);
        $this->assertStringContainsString('Description is required', $errors);
        $this->assertStringContainsString('positive number', $errors);
        $this->assertStringContainsString('Bank Account', $errors);
    }

    public function test_financial_statement_page_exposes_import_and_export_capabilities(): void
    {
        $page = CapabilityCatalog::pageForClass(FinancialStatementPage::class);

        $this->assertNotNull($page);
        $this->assertContains('import', $page['actions']);
        $this->assertContains('export', $page['actions']);
    }

    public function test_statement_pdf_uses_the_bundled_logo_when_the_school_has_none(): void
    {
        $this->assertSame(public_path('img/kairo-core-logo.png'), finance_document_logo_path([], []));
    }

    public function test_template_covers_the_last_three_calendar_years(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'financial-statement-template').'.xlsx';
        file_put_contents($path, FinancialStatementCsvService::templateXlsx());

        try {
            $book = IOFactory::load($path);
        } finally {
            @unlink($path);
        }

        $this->assertSame(['Worksheet', 'Template Guide'], $book->getSheetNames());

        $sheet = $book->getSheet(0);
        $this->assertSame(19, $sheet->getHighestDataRow());
        $this->assertSame(FinancialStatementCsvService::templateHeaders(), $sheet->rangeToArray('A1:H1')[0]);

        $dates = $sheet->rangeToArray('B2:B19', null, true, false, true);
        $years = collect($dates)->flatten()->map(fn ($date) => (int) substr((string) $date, 0, 4))->unique()->sort()->values()->all();

        $this->assertSame([now()->year - 2, now()->year - 1, now()->year], $years);
    }
}
