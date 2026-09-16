<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\URL;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Services\PaymentSettlementService;
use Modules\Finance\Services\StudentFinancialHistoryService;
use Modules\Students\Models\Student;

class TempFinancialHistorySmokeTest extends TestCase
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

    protected function tenantHost(): string
    {
        $school = School::findOrFail($this->schoolId);

        return $school->subdomain.'.'.parse_url(config('app.url'), PHP_URL_HOST).':8000';
    }

    private function makePaidFixture(bool $withPayment = true): array
    {
        $student = Student::withoutGlobalScopes()->create([
            'school_id' => $this->schoolId,
            'student_id_number' => 'HS-'.uniqid(),
            'admission_number' => 'HSADM-'.uniqid(),
            'first_name' => 'History',
            'last_name' => 'Fixture '.uniqid(),
            'gender' => 'female',
            'date_of_birth' => now()->subYears(14)->toDateString(),
            'admission_date' => now()->subYear()->toDateString(),
            'status' => 'active',
        ]);

        $invoice = Invoice::withoutGlobalScopes()->create([
            'school_id' => $this->schoolId,
            'student_id' => $student->id,
            'invoice_number' => 'HSINV-'.uniqid(),
            'currency' => 'USD',
            'subtotal_amount' => 100,
            'total_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => 'unpaid',
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        if ($withPayment) {
            PaymentSettlementService::settle($invoice, 40, [
                'receipt_number' => 'HSRCP-'.uniqid(),
                'reference_number' => 'HSREF-'.uniqid(),
                'payment_method' => 'cash',
                'payment_date' => now(),
                'currency' => 'USD',
            ]);
            $invoice->refresh();
        }

        return [$student, $invoice];
    }

    private function captureStream(\Symfony\Component\HttpFoundation\StreamedResponse $response): string
    {
        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }

    public function test_new_pages_and_routes_render(): void
    {
        $user = User::where('school_id', $this->schoolId)->where('requested_role', 'administrator')->firstOrFail();
        $this->actingAs($user)->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $this->get('/workspace/fee-collections')->assertOk();
        echo "\nFEE COLLECTIONS OK\n";

        [$student, $invoice] = $this->makePaidFixture();

        try {
            $this->get('/workspace/student-financial-history?student='.$student->id)->assertOk();
            echo "FINANCIAL HISTORY PAGE OK\n";

            $this->get('/workspace/student-financial-history')->assertOk();
            echo "FINANCIAL HISTORY PAGE (SCHOOL SUMMARY) OK\n";

            $ledger = StudentFinancialHistoryService::buildLedger($student);
            $this->assertCount(2, $ledger['rows']);
            $this->assertEquals(100.0, $ledger['total_billed']);
            $this->assertEquals(40.0, $ledger['total_paid']);
            $this->assertEquals(60.0, $ledger['closing_balance']);
            echo "SERVICE OK (billed=100 paid=40 closing=60)\n";

            $this->get('/documents/finance/students/'.$student->id.'/history/pdf?scope=full')->assertOk();
            echo "SINGLE PDF OK\n";

            $this->get('/documents/finance/students/history/bulk?ids='.$student->id.'&scope=full&format=pdf&mode=combined')->assertOk();
            echo "BULK PDF OK\n";

            $this->get('/documents/finance/students/history/bulk?ids='.$student->id.'&scope=full&format=csv')->assertOk();
            echo "BULK CSV OK\n";

            $this->get('/documents/finance/students/history/bulk?ids='.$student->id.'&scope=full&format=xlsx')->assertOk();
            echo "BULK XLSX OK\n";

            $collections = StudentFinancialHistoryService::collectionsForRange($this->schoolId, now()->startOfDay(), now()->endOfDay());
            $this->assertGreaterThanOrEqual(40.0, $collections['total']);
            echo 'COLLECTIONS OK total='.$collections['total']."\n";

            Payment::create([
                'school_id' => $this->schoolId,
                'invoice_id' => $invoice->id,
                'received_by_id' => $user->id,
                'receipt_number' => 'HSREF-'.uniqid(),
                'reference_number' => 'REFUND-TEST',
                'amount' => -10,
                'currency' => 'USD',
                'payment_method' => 'cash',
                'payment_date' => now(),
                'is_refund' => true,
                'excess_handling' => 'refund',
            ]);

            $collections = StudentFinancialHistoryService::collectionsForRange($this->schoolId, now()->startOfDay(), now()->endOfDay());
            $this->assertEquals(10.0, $collections['refunds']);
            echo 'REFUNDS OK refunds='.$collections['refunds']."\n";

            // Excel exports must stream a valid workbook
            $ledger = StudentFinancialHistoryService::buildLedger($student);
            $scope = ['start' => null, 'end' => now()];
            $response = \Modules\Finance\Services\FinancialHistoryExcelService::downloadStatement($ledger, $student, $scope);
            $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
            $workbook = $this->captureStream($response);
            $this->assertStringStartsWith('PK', $workbook, 'XLSX must be a valid ZIP container');
            echo 'EXCEL SINGLE OK bytes='.strlen($workbook)."\n";
            $summaryResponse = \Modules\Finance\Services\FinancialHistoryExcelService::downloadSummary(
                ['summaries' => [['student' => $student, 'admission_number' => $student->admission_number, 'class' => 'Form 1', 'gender' => 'female', 'billed' => 100, 'paid' => 40, 'balance' => 60, 'status' => 'partial']],
                 'total_billed' => 100, 'total_paid' => 40, 'total_balance' => 60],
                $scope
            );
            $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $summaryResponse);
            $summaryWorkbook = $this->captureStream($summaryResponse);
            $this->assertStringStartsWith('PK', $summaryWorkbook, 'XLSX must be a valid ZIP container');
            echo 'EXCEL BULK OK bytes='.strlen($summaryWorkbook)."\n";
        } finally {
            Payment::where('invoice_id', $invoice->id)->delete();
            $invoice->delete();
            $student->forceDelete();
        }
    }
}
