<?php

namespace Tests\Feature;

use App\Models\School;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Term;
use Modules\Finance\Models\FeeCategory;
use Modules\Finance\Models\FeeStructure;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Services\InvoicingService;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;

class BalanceCarryForwardTest extends TestCase
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

    public function test_activating_term_carries_unpaid_balances_forward(): void
    {
        DB::beginTransaction();
        try {
            // Isolate the scenario: no other school term may be "active".
            Term::withoutTenantScope()->where('school_id', $this->schoolId)->update(['is_active' => false]);

            $year = AcademicYear::withoutTenantScope()->firstOrCreate(
                ['school_id' => $this->schoolId, 'name' => 'CF-Year-'.uniqid()],
                ['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]
            );

            $previousTerm = Term::withoutTenantScope()->create([
                'school_id' => $this->schoolId,
                'academic_year_id' => $year->id,
                'name' => 'CF Term 1-'.uniqid(),
                'start_date' => '2026-01-15',
                'end_date' => '2026-04-15',
                'is_active' => true,
            ]);

            $nextTerm = Term::withoutTenantScope()->create([
                'school_id' => $this->schoolId,
                'academic_year_id' => $year->id,
                'name' => 'CF Term 2-'.uniqid(),
                'start_date' => '2026-05-01',
                'end_date' => '2026-08-15',
                'is_active' => false,
            ]);

            $student = Student::withoutGlobalScopes()->create([
                'school_id' => $this->schoolId,
                'student_id_number' => 'CF-'.uniqid(),
                'admission_number' => 'CFADM-'.uniqid(),
                'first_name' => 'Carry',
                'last_name' => 'Forward '.uniqid(),
                'gender' => 'female',
                'date_of_birth' => now()->subYears(10)->toDateString(),
                'admission_date' => now()->subYear()->toDateString(),
                'status' => 'active',
            ]);

            $course = Course::withoutTenantScope()->create([
                'school_id' => $this->schoolId,
                'name' => 'Grade 1CF-'.uniqid(),
            ]);

            $section = \Modules\Academics\Models\Section::withoutTenantScope()->create([
                'school_id' => $this->schoolId,
                'course_id' => $course->id,
                'name' => $course->name.' Stream A',
                'code' => 'A',
            ]);

            Enrollment::withoutTenantScope()->create([
                'school_id' => $this->schoolId,
                'student_id' => $student->id,
                'course_id' => $course->id,
                'section_id' => $section->id,
                'academic_year_id' => $year->id,
                'status' => 'active',
            ]);

            // One unpaid invoice ($60) in the previous active term.
            $sourceInvoice = new Invoice([
                'school_id' => $this->schoolId,
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'term_id' => $previousTerm->id,
                'invoice_number' => 'INV-2026-'.str_pad((string) mt_rand(1, 99999), 5, '0', STR_PAD_LEFT),
                'currency' => 'USD',
                'subtotal_amount' => 100,
                'discount_amount' => 0,
                'total_amount' => 100,
                'paid_amount' => 40,
                'balance_amount' => 60,
                'status' => 'partially_paid',
                'due_date' => '2026-04-10',
            ]);
            $sourceInvoice->created_at = '2026-01-20';
            $sourceInvoice->save();

            // ACTIVATE the next term — this must trigger the carry-forward.
            $nextTerm->update(['is_active' => true]);

            $cfInvoices = Invoice::withoutTenantScope()
                ->where('school_id', $this->schoolId)
                ->where('student_id', $student->id)
                ->where('term_id', $nextTerm->id)
                ->where('invoice_number', 'like', 'CF-%')
                ->get();

            $this->assertCount(1, $cfInvoices, 'Exactly one CF- invoice should be created for the activated term');
            $cfInvoice = $cfInvoices->first();
            $this->assertSame('60.00', (string) $cfInvoice->balance_amount);

            // The source invoice must be settled and marked as carried forward.
            $sourceInvoice->refresh();
            $this->assertNotNull($sourceInvoice->carried_forward_at, 'Source invoice must be marked carried-forward');
            $this->assertEquals($cfInvoice->id, $sourceInvoice->carried_forward_to_invoice_id);
            $this->assertSame('0.00', (string) $sourceInvoice->balance_amount);
            $this->assertSame('paid', $sourceInvoice->status);

            // A visible credit transfer must exist on the statement ledger.
            $transfer = Payment::where('school_id', $this->schoolId)
                ->where('invoice_id', $sourceInvoice->id)
                ->where('payment_method', 'credit')
                ->where('reference_number', 'like', 'CARRY-FWD-%')
                ->first();
            $this->assertNotNull($transfer, 'A credit transfer should settle the source invoice');
            $this->assertSame('60.00', (string) $transfer->amount);

            // The student's overpayment credit must remain untouched.
            $this->assertSame('0.00', (string) $student->fresh()->credit_balance);

            // Outstanding stays exactly the carried amount (no double counting).
            $outstanding = (float) Invoice::withoutTenantScope()
                ->where('school_id', $this->schoolId)
                ->where('student_id', $student->id)
                ->where('status', '!=', 'paid')
                ->sum('balance_amount');
            $this->assertEquals(60.0, $outstanding);

            // The source invoice itself no longer contributes to outstanding.
            $sourceStillCounted = (float) Invoice::withoutTenantScope()
                ->where('student_id', $student->id)
                ->where('term_id', $previousTerm->id)
                ->sum('balance_amount');
            $this->assertEquals(0.0, $sourceStillCounted);

            // Re-activating (idempotency) must not duplicate the CF- invoice.
            $nextTerm->update(['is_active' => true]);
            $this->assertSame(1, Invoice::withoutTenantScope()
                ->where('school_id', $this->schoolId)
                ->where('student_id', $student->id)
                ->where('term_id', $nextTerm->id)
                ->where('invoice_number', 'like', 'CF-%')
                ->count());

            // CF- presence must NOT block the billing engine for the new term.
            $category = FeeCategory::withoutTenantScope()->firstOrCreate(
                ['school_id' => $this->schoolId, 'name' => 'CF Tuition-'.uniqid()],
                ['is_active' => true]
            );
            FeeStructure::withoutTenantScope()->create([
                'school_id' => $this->schoolId,
                'fee_category_id' => $category->id,
                'academic_year_id' => $year->id,
                'term_id' => $nextTerm->id,
                'scope_type' => 'all',
                'currency' => 'USD',
                'amount' => 250,
            ]);

            $result = app(InvoicingService::class)->runInvoicingEngine('school', $year->id, $nextTerm->id, '2026-05-15');

            $this->assertArrayHasKey('already_billed', $result);
            $newInvoice = Invoice::withoutTenantScope()
                ->where('school_id', $this->schoolId)
                ->where('student_id', $student->id)
                ->where('term_id', $nextTerm->id)
                ->where('invoice_number', 'not like', 'CF-%')
                ->orderByDesc('id')
                ->first();
            $this->assertNotNull($newInvoice, 'The billing engine should still generate the real term fees');
        } finally {
            DB::rollBack();
        }
    }
}
