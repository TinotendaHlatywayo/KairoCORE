<?php

namespace Tests\Feature;

use App\Models\School;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\FinanceAuditLog;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceItem;
use Modules\Finance\Models\Payment;
use Modules\Finance\Services\FinancialHistoryCsvService;
use Modules\Finance\Services\PaymentSettlementService;
use Modules\Finance\Services\StudentFinancialHistoryService;
use Modules\Students\Models\Student;

class TempFinancialHistoryImportTest extends TestCase
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

    public function test_imports_charges_payments_waiver_carry_forwards_and_refunds(): void
    {
        DB::beginTransaction();

        try {
            $student = Student::withoutTenantScope()->create([
                'school_id' => $this->schoolId,
                'student_id_number' => 'FINH-'.uniqid(),
                'admission_number' => 'FINHADM-'.uniqid(),
                'first_name' => 'Financial',
                'last_name' => 'History '.uniqid(),
                'gender' => 'male',
                'date_of_birth' => now()->subYears(13)->toDateString(),
                'admission_date' => now()->subYear()->toDateString(),
                'status' => 'active',
            ]);

            $bank = PaymentSettlementService::defaultBankAccount($this->schoolId);
            $bankBefore = $bank ? (float) $bank->balance : null;

            $id = $student->student_id_number;
            $rows = [
                ['Student ID', 'Transaction Type', 'Date', 'Description / Fee Name', 'Amount (USD)', 'Receipt Number', 'Reference Number', 'Payment Method', 'Invoice Number', 'Academic Year', 'Term', 'Notes'],
                [$id, 'charge', '2026-01-15', 'Tuition Fees', '500.00', '', '', 'bank_transfer', '', '2026', 'Term 1', 'Termly tuition'],
                [$id, 'waiver', '2026-01-16', 'Scholarship 10%', '50.00', '', '', '', '', '2026', 'Term 1', ''],
                [$id, 'payment', '2026-01-20', '', '300.00', 'RCP-1001', 'REF-1001', 'cash', '', '2026', 'Term 1', ''],
                [$id, 'payment', '2026-02-01', '', '250.00', 'RCP-1002', 'REF-1002', 'mobile_money', '', '2026', 'Term 1', 'Overpayment expected'],
                [$id, 'debit_carry_forward', '2026-03-01', 'Balance Brought Forward', '200.00', '', '', '', '', '2026', 'Term 1', ''],
                [$id, 'payment', '2026-03-05', '', '200.00', 'RCP-1003', 'REF-1003', 'bank_transfer', '', '2026', 'Term 1', ''],
                [$id, 'credit_carry_forward', '2026-03-10', 'Carrying over the overpayment', '80.00', '', '', '', '', '2026', 'Term 1', ''],
                [$id, 'refund', '2026-04-01', '', '30.00', 'REF-2001', 'REFUND-2001', 'cash', '', '2026', 'Term 1', 'Partial refund'],
            ];

            $file = tempnam(sys_get_temp_dir(), 'finh-import');
            $out = fopen($file, 'w');
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($out, $row, escape: '\\');
            }
            fclose($out);

            $columns = FinancialHistoryCsvService::columns();
            $columnMap = array_combine(array_keys($columns), array_values(array_map(
                fn ($c) => $c['label'],
                $columns,
            )));

            $result = FinancialHistoryCsvService::import($file, $this->schoolId, $columnMap, null, [
                'requester_id' => null,
            ]);

            $this->assertSame(['success' => 8, 'total' => 8, 'failures' => []], $result);

            $student->refresh();
            $invoices = Invoice::withoutTenantScope()->where('student_id', $student->id)->orderBy('id')->get();

            $this->assertCount(2, $invoices);

            // Charge invoice
            $charge = $invoices->first();
            $this->assertSame('500.00', (string) $charge->subtotal_amount);
            $this->assertSame('2026-01-15', $charge->created_at->toDateString());
            $this->assertSame('50.00', (string) $charge->discount_amount);
            $this->assertTrue($charge->items->contains(fn (InvoiceItem $i) => $i->name === 'Tuition Fees'));

            // Debit carry forward invoice
            $cf = $invoices->last();
            $this->assertStringStartsWith('CF-', (string) $cf->invoice_number);
            $this->assertSame('200.00', (string) $cf->subtotal_amount);
            $this->assertSame('2026-03-01', $cf->created_at->toDateString());

            // Payments
            $payments = Payment::withoutTenantScope()
                ->whereIn('invoice_id', $invoices->pluck('id'))
                ->where('is_reversed', false)
                ->get();

            $this->assertCount(5, $payments);
            $this->assertSame(1, $payments->where('is_refund', true)->count());

            $refund = $payments->firstWhere('is_refund', true);
            $this->assertSame(-30.0, (float) $refund->amount);

            $creditPayments = $payments->where('payment_method', 'credit');
            $this->assertCount(1, $creditPayments);

            // Student credit balance: 100 overpayment + 80 carry-forward credit
            $this->assertSame(180.0, (float) $student->credit_balance);

            if ($bank) {
                // settle() increments bank by the full payment amount for MODE_CREDIT,
                // and by the applied amount for normal payments.  Refund decrements it.
                // Row 3: +300, Row 4: +250 (MODE_CREDIT), Row 6: +200, Refund: -30 = +720 net.
                $this->assertSame(round($bankBefore + 720, 2), round((float) $bank->refresh()->balance, 2));
            }

            // Ledger
            $ledger = StudentFinancialHistoryService::buildLedger($student);
            $labels = array_column($ledger['rows'], 'description');
            $this->assertContains('Balance Brought Forward - Debit Carry Forward ('.$cf->invoice_number.')', $labels);
            $this->assertSame(30.0, (float) $ledger['closing_balance']);

            // Audit trail
            $audits = FinanceAuditLog::withoutTenantScope()
                ->where('student_id', $student->id)
                ->get();
            $this->assertGreaterThanOrEqual(7, $audits->count());
            $this->assertContains('import.charge', $audits->pluck('action'));
            $this->assertContains('import.refund', $audits->pluck('action'));
            $this->assertContains('import.credit_carry_forward', $audits->pluck('action'));

            // Template still generates
            $xlsx = FinancialHistoryCsvService::templateXlsx();
            $this->assertStringStartsWith('PK', $xlsx);
        } finally {
            DB::rollBack();
        }
    }

    public function test_rejects_unknown_students_and_bad_types(): void
    {
        DB::beginTransaction();

        try {
            $csv = implode("\n", [
                'Student ID,Transaction Type,Date,Description / Fee Name,Amount (USD),Receipt Number,Reference Number,Payment Method,,Academic Year,Term,Notes',
                'DOES-NOT-EXIST,charge,2026-01-15,Tuition,100,,,,,2026,Term 1,',
                '9999,weird,2026-01-15,Tuition,100,,,,,2026,Term 1,',
            ]);

            $file = tempnam(sys_get_temp_dir(), 'finh-import');
            file_put_contents($file, "\xEF\xBB\xBF".$csv);

            $columns = FinancialHistoryCsvService::columns();
            $columnMap = array_combine(array_keys($columns), array_values(array_map(
                fn ($c) => $c['label'],
                $columns,
            )));

            $result = FinancialHistoryCsvService::import($file, $this->schoolId, $columnMap);

            $this->assertSame(0, $result['success']);
            $this->assertSame(2, $result['total']);
            $this->assertCount(2, $result['failures']);
            $this->assertStringContainsString('No student was found', $result['failures'][0]['errors'][0]);
            $this->assertStringContainsString('Transaction Type must be one of', $result['failures'][1]['errors'][0]);
        } finally {
            DB::rollBack();
        }
    }
}