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
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Term;
use Modules\Admin\Models\SystemSetting;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceItem;
use Modules\Finance\Services\FinanceSettingsService;
use Modules\Finance\Services\InvoicingService;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;

class TempMonthlyBillingTest extends TestCase
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

    private function adminUser(): User
    {
        return User::where('school_id', $this->schoolId)->where('requested_role', 'administrator')->firstOrFail();
    }

    public function test_billing_frequency_defaults_to_termly(): void
    {
        DB::beginTransaction();
        try {
            $freq = FinanceSettingsService::billingFrequency($this->schoolId);
            $this->assertEquals('termly', $freq);
        } finally {
            DB::rollBack();
        }
    }

    public function test_billing_frequency_reads_setting(): void
    {
        DB::beginTransaction();
        try {
            SystemSetting::updateOrCreate(
                ['school_id' => $this->schoolId, 'group' => 'finance', 'key' => 'billing_frequency'],
                ['value' => 'monthly']
            );
            $freq = FinanceSettingsService::billingFrequency($this->schoolId);
            $this->assertEquals('monthly', $freq);
        } finally {
            DB::rollBack();
        }
    }

    public function test_monthly_invoicing_splits_across_months(): void
    {
        DB::beginTransaction();
        try {
            $user = $this->adminUser();

            // Set monthly billing
            SystemSetting::updateOrCreate(
                ['school_id' => $this->schoolId, 'group' => 'finance', 'key' => 'billing_frequency'],
                ['value' => 'monthly']
            );

            // Create student
            $student = Student::withoutGlobalScopes()->create([
                'school_id' => $this->schoolId,
                'student_id_number' => 'MB-'.uniqid(),
                'admission_number' => 'MBADM-'.uniqid(),
                'first_name' => 'Monthly',
                'last_name' => 'Billing '.uniqid(),
                'gender' => 'male',
                'date_of_birth' => now()->subYears(13)->toDateString(),
                'admission_date' => now()->subYear()->toDateString(),
                'status' => 'active',
            ]);

            // Create academic year + term spanning 3 months
            $year = AcademicYear::withoutTenantScope()->firstOrCreate(
                ['school_id' => $this->schoolId, 'name' => 'Year-'.uniqid()],
                ['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]
            );
            $term = Term::withoutTenantScope()->create([
                'school_id' => $this->schoolId,
                'academic_year_id' => $year->id,
                'name' => 'Term A-'.uniqid(),
                'start_date' => '2026-01-15',
                'end_date' => '2026-03-28',
            ]);

            // Create a course and enrollment
            $course = Course::withoutTenantScope()->create([
                'school_id' => $this->schoolId,
                'name' => 'Form 1A-'.uniqid(),
            ]);
            Enrollment::withoutTenantScope()->create([
                'school_id' => $this->schoolId,
                'student_id' => $student->id,
                'course_id' => $course->id,
                'academic_year_id' => $year->id,
                'section_id' => Section::withoutTenantScope()->create([
                    'school_id' => $this->schoolId,
                    'course_id' => $course->id,
                    'name' => $course->name.' Stream A',
                    'code' => 'A',
                ])->id,
                'status' => 'active',
            ]);

            // Create a fee structure for this term
            $category = \Modules\Finance\Models\FeeCategory::withoutTenantScope()->firstOrCreate(
                ['school_id' => $this->schoolId, 'name' => 'Tuition-'.uniqid()],
                ['is_active' => true]
            );
            \Modules\Finance\Models\FeeStructure::withoutTenantScope()->create([
                'school_id' => $this->schoolId,
                'fee_category_id' => $category->id,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
                'scope_type' => 'all',
                'currency' => 'USD',
                'amount' => 300,
            ]);

            // Run the engine
            $service = app(InvoicingService::class);
            $result = $service->runInvoicingEngine('school', $year->id, $term->id, '2026-02-01');
            $this->assertEquals(3, $result['generated'], 'Engine should have created 3 monthly invoices');
            $this->assertGreaterThan(0, $result['scanned']);

            // Should have 3 monthly invoices (Jan, Feb, Mar)
            $invoices = Invoice::withoutTenantScope()
                ->where('student_id', $student->id)
                ->where('term_id', $term->id)
                ->orderBy('created_at')
                ->get();

            $this->assertEquals(3, $invoices->count(), 'Monthly billing should produce 3 invoices for a 3-month term');
            $this->assertTrue($invoices->every(fn ($inv) => $inv->billing_period !== null), 'All monthly invoices should have billing_period set');

            // Total should equal the fee structure amount
            $totalAmount = $invoices->sum('total_amount');
            $this->assertEqualsWithDelta(300.0, $totalAmount, 0.02, 'Sum of monthly invoices should equal term total');

            // Invoice numbers should have month suffixes
            $numbers = $invoices->pluck('invoice_number')->toArray();
            $this->assertStringContainsString('-M01', $numbers[0], 'First invoice should be M01');
            $this->assertStringContainsString('-M02', $numbers[1], 'Second invoice should be M02');
            $this->assertStringContainsString('-M03', $numbers[2], 'Third invoice should be M03');

            // Each invoice should have a line item with the month label
            $firstItem = $invoices->first()->items()->first();
            $this->assertStringContainsString('January', $firstItem->name, 'Line item should include month name');

            // Verify billing periods
            $periods = $invoices->pluck('billing_period')->toArray();
            $this->assertEquals(['2026-01', '2026-02', '2026-03'], $periods);

            // Verify balances sum up
            $totalBalance = $invoices->sum('balance_amount');
            $this->assertEqualsWithDelta(300.0, $totalBalance, 0.02, 'Sum of monthly balances should equal term total');

            // Verify status
            $this->assertTrue($invoices->every(fn ($inv) => $inv->status === 'unpaid'));

            // Verify statement monthly summary groups by month
            $ledger = \Modules\Finance\Services\StudentFinancialHistoryService::buildLedger($student);
            $summary = $ledger['monthly_summary'] ?? [];
            $this->assertCount(3, $summary, 'Statement monthly summary should have 3 month buckets');
            $this->assertEquals('January 2026', $summary[0]['label'] ?? '');
            $this->assertEquals(100.0, $summary[0]['billed'] ?? 0.0, 'Jan billed should be ~100');
            $this->assertEquals('March 2026', $summary[2]['label'] ?? '');

            echo "MONTHLY BILLING OK: {$invoices->count()} invoices, total=\${$totalAmount}, summary months=".count($summary)."\n";
        } finally {
            DB::rollBack();
        }
    }

    public function test_termly_invoicing_still_works(): void
    {
        DB::beginTransaction();
        try {
            // Termly is default — no setting change needed
            $student = Student::withoutGlobalScopes()->create([
                'school_id' => $this->schoolId,
                'student_id_number' => 'TB-'.uniqid(),
                'admission_number' => 'TBADM-'.uniqid(),
                'first_name' => 'Termly',
                'last_name' => 'Billing '.uniqid(),
                'gender' => 'female',
                'date_of_birth' => now()->subYears(14)->toDateString(),
                'admission_date' => now()->subYear()->toDateString(),
                'status' => 'active',
            ]);

            $year = AcademicYear::withoutTenantScope()->firstOrCreate(
                ['school_id' => $this->schoolId, 'name' => 'Year-'.uniqid()],
                ['start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'is_active' => true]
            );
            $term = Term::withoutTenantScope()->create([
                'school_id' => $this->schoolId,
                'academic_year_id' => $year->id,
                'name' => 'Term T-'.uniqid(),
                'start_date' => '2026-01-15',
                'end_date' => '2026-03-28',
            ]);
            $course = Course::withoutTenantScope()->create([
                'school_id' => $this->schoolId,
                'name' => 'Form 2B-'.uniqid(),
            ]);
            Enrollment::withoutTenantScope()->create([
                'school_id' => $this->schoolId,
                'student_id' => $student->id,
                'course_id' => $course->id,
                'academic_year_id' => $year->id,
                'section_id' => Section::withoutTenantScope()->create([
                    'school_id' => $this->schoolId,
                    'course_id' => $course->id,
                    'name' => $course->name.' Stream A',
                    'code' => 'A',
                ])->id,
                'status' => 'active',
            ]);
            $category = \Modules\Finance\Models\FeeCategory::withoutTenantScope()->firstOrCreate(
                ['school_id' => $this->schoolId, 'name' => 'Tuition-'.uniqid()],
                ['is_active' => true]
            );
            \Modules\Finance\Models\FeeStructure::withoutTenantScope()->create([
                'school_id' => $this->schoolId,
                'fee_category_id' => $category->id,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
                'scope_type' => 'all',
                'currency' => 'USD',
                'amount' => 500,
            ]);

            $service = app(InvoicingService::class);
            $result = $service->runInvoicingEngine('school', $year->id, $term->id, '2026-02-15');
            $this->assertEquals(1, $result['generated']);

            $invoices = Invoice::withoutTenantScope()
                ->where('student_id', $student->id)
                ->where('term_id', $term->id)
                ->get();

            $this->assertEquals(1, $invoices->count(), 'Termly billing should produce 1 invoice');
            $this->assertNull($invoices->first()->billing_period, 'Termly invoice should have null billing_period');
            $this->assertEquals(500.0, (float) $invoices->first()->total_amount);

            echo "TERMLY BILLING OK: 1 invoice, total=\$500.00\n";
        } finally {
            DB::rollBack();
        }
    }
}
