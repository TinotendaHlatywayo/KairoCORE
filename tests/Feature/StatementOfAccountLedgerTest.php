<?php

namespace Tests\Feature;

use App\Models\School;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Services\BillingDocumentSettingsService;
use Modules\Finance\Services\StudentFinancialHistoryService;
use Modules\Students\Models\Student;

class StatementOfAccountLedgerTest extends TestCase
{
    protected int $schoolId;

    protected School $school;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'schoolcore']);

        $this->schoolId = (int) config('tenancy.single_tenant_id');
        $this->school = School::findOrFail($this->schoolId);
        app()->instance('current_tenant', $this->school);
        URL::defaults(['tenant' => $this->school->subdomain]);
        $this->withSession(['locale' => 'en']);
    }

    private function makeStudent(): Student
    {
        return Student::withoutGlobalScopes()->create([
            'school_id' => $this->schoolId,
            'student_id_number' => 'SOA-'.uniqid(),
            'admission_number' => 'SOAADM-'.uniqid(),
            'first_name' => 'Statement',
            'last_name' => 'Fixture '.uniqid(),
            'gender' => 'female',
            'date_of_birth' => now()->subYears(14)->toDateString(),
            'admission_date' => now()->subYear()->toDateString(),
            'status' => 'active',
        ]);
    }

    private function makeInvoice(Student $student, float $subtotal): Invoice
    {
        return Invoice::withoutGlobalScopes()->create([
            'school_id' => $this->schoolId,
            'student_id' => $student->id,
            'invoice_number' => 'SOAINV-'.uniqid(),
            'currency' => 'USD',
            'subtotal_amount' => $subtotal,
            'total_amount' => $subtotal,
            'paid_amount' => 0,
            'balance_amount' => $subtotal,
            'status' => 'unpaid',
            'due_date' => now()->addDays(30)->toDateString(),
        ]);
    }

    private function makePayment(Invoice $invoice, float $amount, string $paymentDate, ?string $receipt = null, bool $isRefund = false): Payment
    {
        $payment = Payment::withoutGlobalScopes()->create([
            'school_id' => $this->schoolId,
            'invoice_id' => $invoice->id,
            'received_by_id' => null,
            'receipt_number' => $receipt ?? 'SOARCP-'.uniqid(),
            'reference_number' => 'SOAREF-'.uniqid(),
            'amount' => $amount,
            'currency' => 'USD',
            'payment_method' => 'cash',
            'payment_date' => $paymentDate,
            'is_refund' => $isRefund,
            'excess_handling' => $isRefund ? 'refund' : 'allocate',
        ]);

        DB::table('payments')->where('id', $payment->id)->update(['payment_date' => $paymentDate]);

        return $payment;
    }

    /**
     * Tadiwa scenario: a payment received before any billing, followed by a
     * refund, then the actual bill. The printed balances must reflect the true
     * chronological order (-50 → 0 → 260) and the refund must be labelled and
     * show its amount.
     */
    public function test_ledger_orders_payment_refund_and_bill_chronologically(): void
    {
        $student = $this->makeStudent();
        $invoice = $this->makeInvoice($student, 260);
        $payment = $this->makePayment($invoice, 50, '2026-09-01 08:00:00', 'RCP-42489');
        $refund = $this->makePayment($invoice, -50, '2026-09-02 08:00:00', 'REF-PAY-87143', true);

        DB::table('invoices')->where('id', $invoice->id)->update(['created_at' => '2026-09-03 08:00:00']);

        try {
            $result = StudentFinancialHistoryService::buildStatementLedger($student, $this->schoolId);
            $rows = $result['ledger'];

            $this->assertCount(3, $rows);

            $this->assertStringContainsString('Payment Received (Receipt: RCP-42489)', $rows[0]['type']);
            $this->assertEquals(50.0, (float) $rows[0]['credit']);
            $this->assertEquals(-50.0, (float) $rows[0]['running_balance']);

            $this->assertStringContainsString('Refund Issued (Receipt: REF-PAY-87143)', $rows[1]['type']);
            $this->assertTrue($rows[1]['is_refund']);
            $this->assertEquals(-50.0, (float) $rows[1]['credit']);
            $this->assertEquals(0.0, (float) $rows[1]['running_balance']);

            $this->assertStringContainsString('Gross Fees Billed ('.$invoice->invoice_number.')', $rows[2]['type']);
            $this->assertEquals(260.0, (float) $rows[2]['debit']);
            $this->assertEquals(260.0, (float) $rows[2]['running_balance']);

            $this->assertEquals(260.0, (float) $result['current_balance']);

            $html = view('modules.finance.statement-pdf', [
                'student' => $student,
                'school' => $this->school,
                'ledger' => $rows,
                'current_balance' => $result['current_balance'],
                'config' => BillingDocumentSettingsService::get(),
                'template' => null,
                'verify_hash' => null,
            ])->render();

            $this->assertStringContainsString('Refund Issued (Receipt: REF-PAY-87143)', $html);
            $this->assertStringContainsString('$50.00 (refunded)', $html);
        } finally {
            Payment::where('invoice_id', $invoice->id)->delete();
            $invoice->delete();
            $student->forceDelete();
        }
    }

    /**
     * Tatenda scenario: an invoice billed then a payment a day later must print
     * billed first (300) and payment second (200) - never the reverse.
     */
    public function test_ledger_orders_bill_before_payment(): void
    {
        $student = $this->makeStudent();
        $invoice = $this->makeInvoice($student, 300);
        $payment = $this->makePayment($invoice, 100, '2026-09-02 08:00:00', 'RCP-44972');

        DB::table('invoices')->where('id', $invoice->id)->update(['created_at' => '2026-09-01 08:00:00']);

        try {
            $result = StudentFinancialHistoryService::buildStatementLedger($student, $this->schoolId);
            $rows = $result['ledger'];

            $this->assertCount(2, $rows);

            $this->assertStringContainsString('Gross Fees Billed ('.$invoice->invoice_number.')', $rows[0]['type']);
            $this->assertEquals(300.0, (float) $rows[0]['running_balance']);

            $this->assertStringContainsString('Payment Received (Receipt: RCP-44972)', $rows[1]['type']);
            $this->assertEquals(100.0, (float) $rows[1]['credit']);
            $this->assertEquals(200.0, (float) $rows[1]['running_balance']);

            $this->assertEquals(200.0, (float) $result['current_balance']);
        } finally {
            Payment::where('invoice_id', $invoice->id)->delete();
            $invoice->delete();
            $student->forceDelete();
        }
    }

    public function test_same_day_bill_and_payment_keep_correct_balance(): void
    {
        $student = $this->makeStudent();
        $invoice = $this->makeInvoice($student, 400);
        $payment = $this->makePayment($invoice, 150, '2026-09-10 08:00:00', 'RCP-SAME');

        // Same calendar date: billing timestamp earlier than the payment so the
        // billing lands first and the running balance is 250, not -150.
        DB::table('invoices')->where('id', $invoice->id)->update(['created_at' => '2026-09-10 06:00:00']);

        try {
            $result = StudentFinancialHistoryService::buildStatementLedger($student, $this->schoolId);
            $rows = $result['ledger'];

            $this->assertCount(2, $rows);
            $this->assertStringContainsString('Gross Fees Billed', $rows[0]['type']);
            $this->assertEquals(400.0, (float) $rows[0]['running_balance']);
            $this->assertStringContainsString('Payment Received', $rows[1]['type']);
            $this->assertEquals(250.0, (float) $rows[1]['running_balance']);
            $this->assertEquals(250.0, (float) $result['current_balance']);
        } finally {
            Payment::where('invoice_id', $invoice->id)->delete();
            $invoice->delete();
            $student->forceDelete();
        }
    }
}