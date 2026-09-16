<?php

namespace Tests\Feature;

use App\Filament\App\Pages\Finance\StudentFinancialHistoryPage;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\Finance\Models\FinanceAuditLog;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Services\PaymentSettlementService;
use Modules\Students\Models\Student;

class TempFinancialHistoryManualEntryTest extends TestCase
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
        URL::defaults(['tenant' => $school->subdomain]);
        $this->withSession(['locale' => 'en']);
    }

    private function makeStudent(): Student
    {
        return Student::withoutGlobalScopes()->create([
            'school_id' => $this->schoolId,
            'student_id_number' => 'MH-'.uniqid(),
            'admission_number' => 'MHADM-'.uniqid(),
            'first_name' => 'Manual',
            'last_name' => 'Entry '.uniqid(),
            'gender' => 'male',
            'date_of_birth' => now()->subYears(13)->toDateString(),
            'admission_date' => now()->subYear()->toDateString(),
            'status' => 'active',
        ]);
    }

    private function adminUser(): User
    {
        return User::where('school_id', $this->schoolId)->where('requested_role', 'administrator')->firstOrFail();
    }

    private function pageComponent(User $user, Student $student)
    {
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant(School::findOrFail($this->schoolId), true);

        return Livewire::actingAs($user)->test(StudentFinancialHistoryPage::class)
            ->set('student_id', $student->id);
    }

    public function test_record_charge_action_creates_invoice_and_audit(): void
    {
        DB::beginTransaction();

        try {
            $user = $this->adminUser();
            $student = $this->makeStudent();

            $component = $this->pageComponent($user, $student);
            $component->mountAction('record_charge');
            $component->assertActionMounted('record_charge');
            $component->setActionData([
                'amount' => 250,
                'date' => '2026-04-05',
                'description' => 'Tuition Fees',
            ]);
            $component->callMountedAction();
            $component->assertHasNoActionErrors();

            $invoice = Invoice::withoutGlobalScopes()->where('student_id', $student->id)->first();
            $this->assertNotNull($invoice, 'No invoice created by record_charge');
            $this->assertEquals(250.0, (float) $invoice->subtotal_amount);
            $this->assertEquals('2026-04-05', $invoice->created_at?->toDateString());
            $this->assertTrue(str_starts_with((string) $invoice->invoice_number, 'INV-'));

            $audit = FinanceAuditLog::withoutGlobalScopes()->where('student_id', $student->id)->where('action', 'manual.charge')->first();
            $this->assertNotNull($audit, 'manual.charge audit not recorded');
            echo "MANUAL CHARGE OK (invoice {$invoice->invoice_number}, audit action {$audit->action})\n";
        } finally {
            DB::rollBack();
        }
    }

    public function test_payment_refund_and_reverse_via_actions(): void
    {
        DB::beginTransaction();

        try {
            $user = $this->adminUser();
            $student = $this->makeStudent();

            // 1. Charge (via the same path legacy imported rows use)
            $invoice = Invoice::withoutGlobalScopes()->create([
                'school_id' => $this->schoolId,
                'student_id' => $student->id,
                'invoice_number' => 'MHINV-'.uniqid(),
                'currency' => 'USD',
                'subtotal_amount' => 100,
                'total_amount' => 100,
                'paid_amount' => 0,
                'balance_amount' => 100,
                'status' => 'unpaid',
                'due_date' => now()->addDays(30)->toDateString(),
            ]);

            // 2. Record payment through the action
            $component = $this->pageComponent($user, $student);
            $component->mountAction('record_payment');
            $component->assertActionMounted('record_payment');
            $component->setActionData([
                'amount' => 60,
                'date' => '2026-04-06',
                'payment_method' => 'cash',
                'receipt_number' => 'MHRCP-'.uniqid(),
                'invoice_id' => $invoice->id,
            ]);
            $component->callMountedAction();
            $component->assertHasNoActionErrors();

            $paymentCount = Payment::withoutGlobalScopes()->where('invoice_id', $invoice->id)->count();
            $this->assertGreaterThan(0, $paymentCount, 'No payment created for invoice');

            $invoice->refresh();
            $this->assertEquals(60.0, (float) $invoice->paid_amount);
            $this->assertEquals(40.0, (float) $invoice->balance_amount);

            // 3. Reverse it through the action
            $payment = Payment::withoutGlobalScopes()->where('invoice_id', $invoice->id)->firstOrFail();
            $component = $this->pageComponent($user, $student);
            $component->mountAction('reverse_payment', ['payment_id' => $payment->id]);
            $component->assertActionMounted('reverse_payment');
            $component->callMountedAction();
            $component->assertHasNoActionErrors();

            $payment->refresh();
            $this->assertTrue($payment->is_reversed);
            $invoice->refresh();
            $this->assertEquals(0.0, (float) $invoice->paid_amount);
            $this->assertEquals(100.0, (float) $invoice->balance_amount);
            echo "MANUAL PAYMENT + REVERSE OK (paid->60, reversed, paid->0)\n";
        } finally {
            DB::rollBack();
        }
    }

    public function test_edit_and_delete_invoice_actions(): void
    {
        DB::beginTransaction();

        try {
            $user = $this->adminUser();
            $student = $this->makeStudent();
            $invoice = Invoice::withoutGlobalScopes()->create([
                'school_id' => $this->schoolId,
                'student_id' => $student->id,
                'invoice_number' => 'MHINV2-'.uniqid(),
                'currency' => 'USD',
                'subtotal_amount' => 100,
                'total_amount' => 100,
                'paid_amount' => 0,
                'balance_amount' => 100,
                'status' => 'unpaid',
                'due_date' => now()->addDays(30)->toDateString(),
            ]);

            // Edit (no money moved so amount is editable)
            $component = $this->pageComponent($user, $student);
            $component->mountAction('edit_invoice', ['invoice_id' => $invoice->id]);
            $component->assertActionMounted('edit_invoice');
            $component->setActionData([
                'invoice_id' => $invoice->id,
                'date' => '2026-04-01',
                'description' => 'Edited Fee Name',
                'amount' => 120,
            ]);
            $component->callMountedAction();
            $component->assertHasNoActionErrors();

            $invoice->refresh();
            $this->assertEquals(120.0, (float) $invoice->subtotal_amount);
            $this->assertEquals('2026-04-01', $invoice->created_at?->toDateString());
            $this->assertEquals('Edited Fee Name', $invoice->items()->first()?->name);
            echo "MANUAL EDIT INVOICE OK\n";

            $editAudit = FinanceAuditLog::withoutGlobalScopes()->where('student_id', $student->id)->where('action', 'manual.edit_invoice')->first();
            $this->assertNotNull($editAudit);

            // Delete
            $component = $this->pageComponent($user, $student);
            $component->mountAction('delete_invoice', ['invoice_id' => $invoice->id]);
            $component->assertActionMounted('delete_invoice');
            $component->callMountedAction();
            $component->assertHasNoActionErrors();

            $this->assertNull(Invoice::withoutGlobalScopes()->find($invoice->id), 'Invoice not deleted');
            $delAudit = FinanceAuditLog::withoutGlobalScopes()->where('student_id', $student->id)->where('action', 'manual.delete_invoice')->first();
            $this->assertNotNull($delAudit);
            echo "MANUAL DELETE INVOICE OK\n";
        } finally {
            DB::rollBack();
        }
    }
}