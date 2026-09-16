<?php

namespace Tests\Feature;

use App\Models\School;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Term;
use Modules\Finance\Services\FinancialHistoryCsvService;
use Modules\Finance\Services\StudentFinancialHistoryService;
use Modules\Students\Models\Student;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class TempFinancialHistoryTemplateIntegrityTest extends TestCase
{
    protected int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'schoolcore']);

        $this->schoolId = (int) config('tenancy.single_tenant_id');
        $school = School::findOrFail($this->schoolId);
        app()->instance('current_tenant', $school);
    }

    private function templateRows(): array
    {
        $method = new \ReflectionMethod(FinancialHistoryCsvService::class, 'templateRows');

        return $method->invoke(null);
    }

    private function importTemplateRows(): void
    {
        $headers = ['Student ID', 'Transaction Type', 'Date', 'Description / Fee Name', 'Amount (USD)', 'Receipt Number', 'Reference Number', 'Payment Method', 'Invoice Number', 'Academic Year', 'Term', 'Notes'];

        foreach ($this->templateRows() as $positionalRow) {
            $mapped = array_combine($headers, $positionalRow);

            $student = Student::withoutTenantScope()->where('school_id', $this->schoolId)
                ->where('student_id_number', $mapped['Student ID'])->first();

            $this->assertNotNull($student, 'Example student '.$mapped['Student ID'].' must exist for the template import');

            $year = AcademicYear::withoutTenantScope()->where('school_id', $this->schoolId)
                ->where('name', $mapped['Academic Year'])->first();

            $term = null;
            if ($mapped['Term'] !== '') {
                $term = Term::withoutTenantScope()->where('school_id', $this->schoolId)
                    ->where('name', $mapped['Term'])->where('academic_year_id', $year->id)->first();
            }

            $data = [
                'student_id' => $mapped['Student ID'],
                'type' => $mapped['Transaction Type'],
                'date' => $mapped['Date'],
                'description' => $mapped['Description / Fee Name'],
                'amount' => $mapped['Amount (USD)'],
                'receipt_number' => $mapped['Receipt Number'],
                'reference_number' => $mapped['Reference Number'],
                'payment_method' => $mapped['Payment Method'],
                'invoice_number' => $mapped['Invoice Number'],
                'academic_year' => $mapped['Academic Year'],
                'term' => $mapped['Term'],
                'notes' => $mapped['Notes'],
                '_student' => $student,
                '_academic_year' => $year,
                '_term' => $term,
                '_date' => Carbon::parse($mapped['Date']),
                '_amount' => (float) $mapped['Amount (USD)'],
            ];

            FinancialHistoryCsvService::persistRow($data, $this->schoolId, null, 'integrity');
        }
    }

    private function closingBalance(string $studentId): float
    {
        $student = Student::withoutTenantScope()->where('school_id', $this->schoolId)
            ->where('student_id_number', $studentId)->firstOrFail();

        return (float) StudentFinancialHistoryService::buildLedger($student)['closing_balance'];
    }

    public function test_every_example_ledger_reconciles_after_import(): void
    {
        DB::beginTransaction();

        try {
            foreach (['R260001A', 'R260001B', 'R260001C'] as $sid) {
                $student = Student::withoutTenantScope()->create([
                    'school_id' => $this->schoolId,
                    'student_id_number' => $sid,
                    'admission_number' => 'ADM-'.$sid,
                    'first_name' => 'Template',
                    'last_name' => $sid,
                    'gender' => 'male',
                    'date_of_birth' => '2010-01-01',
                    'admission_date' => '2017-01-15',
                    'status' => 'active',
                ]);
                $this->assertNotNull($student);
            }

            // The template drives term dates straight from the year, so create
            // matching academic years and terms (2017..2026) for the tenant.
            foreach (range(2017, 2026) as $yearName) {
                $year = AcademicYear::withoutTenantScope()->create([
                    'school_id' => $this->schoolId,
                    'name' => (string) $yearName,
                    'start_date' => $yearName.'-01-01',
                    'end_date' => $yearName.'-12-31',
                    'is_active' => $yearName === '2026' ? 1 : 0,
                ]);
                foreach (['Term 1', 'Term 2', 'Term 3'] as $termName) {
                    Term::withoutTenantScope()->create([
                        'school_id' => $this->schoolId,
                        'academic_year_id' => $year->id,
                        'name' => $termName,
                        'start_date' => $yearName.'-01-01',
                        'end_date' => $yearName.'-12-31',
                    ]);
                }
            }

            $this->importTemplateRows();

            $dump = '';
            foreach (['R260001A', 'R260001B', 'R260001C'] as $sid) {
                $student = Student::withoutTenantScope()->where('school_id', $this->schoolId)->where('student_id_number', $sid)->firstOrFail();
                $ledger = StudentFinancialHistoryService::buildLedger($student);
                $dump .= "== $sid closing=".$ledger['closing_balance'].' billed='.$ledger['total_billed'].' paid='.$ledger['total_paid'].' refunded='.$ledger['total_refunded']." ==\n";
                foreach ($ledger['rows'] as $r) {
                    if (in_array($r['type'], ['invoice_billed', 'waiver', 'refund', 'credit', 'opening'], true) || ($r['type'] === 'payment' && (float) $r['credit'] !== 0.0)) {
                        $dump .= '  '.$r['date']->toDateString().' | '.$r['type'].' | debit='.$r['debit'].' credit='.$r['credit'].' | bal='.$r['running_balance'].' | '.substr($r['description'], 0, 60)."\n";
                    }
                }
            }
            file_put_contents('/tmp/tpl-ledger.txt', $dump);

            // R260001A, B, and C are fully settled at 0.00.
            $this->assertEqualsWithDelta(0.00, $this->closingBalance('R260001A'), 0.001);
            $this->assertEqualsWithDelta(0.00, $this->closingBalance('R260001B'), 0.001);
            $this->assertEqualsWithDelta(0.00, $this->closingBalance('R260001C'), 0.001);
        } finally {
            DB::rollBack();
        }
    }

    /** The reviewed structural defects must not reappear in templateRows(). */
    public function test_template_rows_contain_no_duplicate_carry_forwards_and_matching_labels(): void
    {
        $rows = $this->templateRows();

        $byStudent = [];
        foreach ($rows as $row) {
            $byStudent[$row[0]][] = $row;
        }

        $a = $byStudent[FinancialHistoryCsvService::EXAMPLE_A];
        $aCarryForwards = array_values(array_filter($a, fn ($r) => $r[1] === 'debit_carry_forward'));
        $this->assertCount(1, $aCarryForwards, 'A must import its brought-forward debit exactly once');
        $this->assertSame('34.00', $aCarryForwards[0][4]);

        // 2024 terms: Term 1 covers charge plus the 34.00 brought-forward debit;
        // Terms 2 and 3 are fully paid.
        $a2024 = array_values(array_filter($a, fn ($r) => $r[9] === '2024'));
        $this->assertNotEmpty($a2024);
        $a2024Terms = [];
        foreach ($a2024 as $row) {
            if ($row[1] === 'payment' || $row[1] === 'charge') {
                $a2024Terms[$row[10]][$row[1]][] = (float) $row[4];
            }
        }
        $this->assertEqualsWithDelta(120.00, array_sum($a2024Terms['Term 1']['charge']), 0.001);
        $this->assertEqualsWithDelta(154.00, array_sum($a2024Terms['Term 1']['payment']), 0.001);
        foreach (['Term 2', 'Term 3'] as $termName) {
            $this->assertEqualsWithDelta(array_sum($a2024Terms[$termName]['charge']), array_sum($a2024Terms[$termName]['payment']), 0.001);
        }

        // B: the waiver must be exactly 10% of its charge, and the payment the
        // remaining balance.
        $b = $byStudent[FinancialHistoryCsvService::EXAMPLE_B];
        $b2026T1 = array_values(array_filter($b, fn ($r) => $r[9] === '2026' && $r[10] === 'Term 1'));
        $charge = array_values(array_filter($b2026T1, fn ($r) => $r[1] === 'charge'))[0];
        $waiver = array_values(array_filter($b2026T1, fn ($r) => $r[1] === 'waiver'))[0];
        $payment = array_values(array_filter($b2026T1, fn ($r) => $r[1] === 'payment'))[0];
        $fee = (float) $charge[4];
        $discount = (float) $waiver[4];
        $this->assertEqualsWithDelta($fee * 0.10, $discount, 0.001, 'waiver amount must be exactly 10% of the fee');
        $this->assertEqualsWithDelta($fee - $discount, (float) $payment[4], 0.001);

        // C: exactly one credit carry-forward representing unitemized prior years.
        $c = $byStudent[FinancialHistoryCsvService::EXAMPLE_C];
        $cCredits = array_values(array_filter($c, fn ($r) => $r[1] === 'credit_carry_forward'));
        $this->assertCount(1, $cCredits, 'C must import its carried credit once');
        $this->assertSame('15.00', $cCredits[0][4]);

        // The credit must be applied after the 2026 Term 1 charge and before
        // that term's payment (i.e. against an open invoice).
        $c2026T1 = array_values(array_filter($c, fn ($r) => $r[9] === '2026' && $r[10] === 'Term 1'));
        $chargeDate = array_values(array_filter($c2026T1, fn ($r) => $r[1] === 'charge'))[0][2];
        $paymentDate = array_values(array_filter($c2026T1, fn ($r) => $r[1] === 'payment'))[0][2];
        $cfDate = $cCredits[0][2];

        $this->assertLessThan($cfDate, $chargeDate);
        $this->assertLessThan($paymentDate, $cfDate);
    }

    /** The downloadable workbook keeps the data sheet first and active, with the docs tabs appended. */
    public function test_template_workbook_keeps_data_sheet_active_with_doc_tabs(): void
    {
        $bytes = FinancialHistoryCsvService::templateXlsx();
        $this->assertStringStartsWith("PK\x03\x04", $bytes);

        $tmp = tempnam(sys_get_temp_dir(), 'finh-tpl').'.xlsx';
        file_put_contents($tmp, $bytes);

        $reader = new Xlsx;
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($tmp);

        $titles = collect($spreadsheet->getAllSheets())->map(fn ($s) => $s->getTitle())->values()->all();
        $this->assertSame(['Import Data', 'Balance Summary', 'Change Log'], $titles);
        $this->assertSame(0, $spreadsheet->getActiveSheetIndex(), 'the import data sheet must stay active');
        $this->assertSame('Import Data', $spreadsheet->getActiveSheet()->getTitle());

        @unlink($tmp);
    }
}
