<?php

namespace Tests\Feature;

use App\Models\School;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Finance\Services\PaymentSettlementService;
use Modules\Students\Models\Student;

class InvoiceOverpaymentSettlementTest extends TestCase
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

    private function makeStudent(): Student
    {
        return Student::withoutGlobalScopes()->create([
            'school_id' => $this->schoolId,
            'student_id_number' => 'OVP-'.uniqid(),
            'admission_number' => 'OVPADM-'.uniqid(),
            'first_name' => 'Overpay',
            'last_name' => 'Student '.uniqid(),
            'gender' => 'male',
            'date_of_birth' => now()->subYears(14)->toDateString(),
            'admission_date' => now()->subYear()->toDateString(),
            'status' => 'active',
        ]);
    }

    private function makeInvoice(Student $student, float $amount = 100): Invoice
    {
        return Invoice::withoutGlobalScopes()->create([
            'school_id' => $this->schoolId,
            'student_id' => $student->id,
            'invoice_number' => 'OVPINV-'.uniqid(),
            'currency' => 'USD',
            'subtotal_amount' => $amount,
            'total_amount' => $amount,
            'paid_amount' => 0,
            'balance_amount' => $amount,
            'status' => 'unpaid',
            'due_date' => now()->addDays(30)->toDateString(),
        ]);
    }

    private function makeBank(float $balance = 0): SchoolBankAccount
    {
        return SchoolBankAccount::create([
            'school_id' => $this->schoolId,
            'bank_name' => 'Overpay Test Bank',
            'account_name' => 'Overpay Test Account',
            'account_number' => 'OVP-'.uniqid(),
            'balance' => $balance,
            'is_active' => true,
            'is_default' => false,
        ]);
    }

    private function attrs(): array
    {
        return [
            'receipt_number' => 'OVPRCP-'.uniqid(),
            'reference_number' => 'OVPREF-'.uniqid(),
            'payment_method' => 'cash',
            'payment_date' => now(),
            'currency' => 'USD',
        ];
    }

    public function test_refunded_overpayment_keeps_only_the_fees_in_the_bank(): void
    {
        DB::beginTransaction();

        try {
            $student = $this->makeStudent();
            $invoice = $this->makeInvoice($student, 100);
            $bank = $this->makeBank(0);

            $result = PaymentSettlementService::settle(
                $invoice,
                150,
                $this->attrs(),
                PaymentSettlementService::MODE_REFUND,
                $bank->id,
            );

            $this->assertSame(100.0, (float) $result['applied']);
            $this->assertSame(50.0, (float) $result['excess']);
            $this->assertSame(50.0, (float) $result['refunded']);
            $this->assertSame(0.0, (float) $result['credited']);

            // Received 150, refunded 50 -> the school keeps the 100 in fees.
            $this->assertSame(100.0, (float) $bank->fresh()->balance);
            $this->assertSame(0.0, (float) $student->fresh()->credit_balance);
            $this->assertSame('100.00', (string) $invoice->fresh()->paid_amount);
            $this->assertSame('0.00', (string) $invoice->fresh()->balance_amount);

            $payments = Payment::withoutGlobalScopes()->where('invoice_id', $invoice->id)->get();
            $this->assertSame(100.0, (float) $payments->sum('amount'), 'Receipt and refund must net to the fees retained.');
            $this->assertSame(1, $payments->where('is_refund', true)->count());
            $this->assertSame(150.0, (float) $payments->where('is_refund', false)->sum('amount'));
            $this->assertSame(-50.0, (float) $payments->where('is_refund', true)->sum('amount'));
        } finally {
            DB::rollBack();
        }
    }

    public function test_credited_overpayment_is_kept_in_the_bank_and_carried_forward(): void
    {
        DB::beginTransaction();

        try {
            $student = $this->makeStudent();
            $invoice = $this->makeInvoice($student, 100);
            $bank = $this->makeBank(0);

            $result = PaymentSettlementService::settle(
                $invoice,
                150,
                $this->attrs(),
                PaymentSettlementService::MODE_CREDIT,
                $bank->id,
            );

            $this->assertSame(50.0, (float) $result['credited']);
            $this->assertSame(0.0, (float) $result['refunded']);
            $this->assertSame(150.0, (float) $bank->fresh()->balance);
            $this->assertSame(50.0, (float) $student->fresh()->credit_balance);
            $this->assertSame('100.00', (string) $invoice->fresh()->paid_amount);

            $payments = Payment::withoutGlobalScopes()->where('invoice_id', $invoice->id)->get();
            $this->assertSame(1, $payments->count());
            $this->assertSame(100.0, (float) $payments->sum('amount'));
        } finally {
            DB::rollBack();
        }
    }

    public function test_refund_on_an_already_paid_invoice_does_not_drain_the_bank(): void
    {
        DB::beginTransaction();

        try {
            $student = $this->makeStudent();
            $invoice = $this->makeInvoice($student, 100);
            $bank = $this->makeBank(0);

            PaymentSettlementService::settle($invoice, 100, $this->attrs(), null, $bank->id);
            $this->assertSame(100.0, (float) $bank->fresh()->balance);

            $result = PaymentSettlementService::settle(
                $invoice->fresh(),
                50,
                $this->attrs(),
                PaymentSettlementService::MODE_REFUND,
                $bank->id,
            );

            $this->assertSame(0.0, (float) $result['applied']);
            $this->assertSame(50.0, (float) $result['refunded']);
            $this->assertSame(100.0, (float) $bank->fresh()->balance, 'A refund must never push the bank below the fees actually retained.');
            $this->assertSame(0.0, (float) $student->fresh()->credit_balance);
        } finally {
            DB::rollBack();
        }
    }
}
