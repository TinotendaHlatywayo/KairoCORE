<?php

namespace Tests\Feature;

use App\Filament\App\Resources\InvoiceResource\Pages\ListInvoices;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceItem;
use Modules\Students\Models\Student;

class InvoiceRecordPaymentActionTest extends TestCase
{
    protected int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');

        $this->schoolId = (int) config('tenancy.single_tenant_id');
        $school = School::findOrFail($this->schoolId);
        app()->instance('current_tenant', $school);
        URL::defaults(['tenant' => $school->subdomain]);
    }

    protected function tenantHost(): string
    {
        $school = School::findOrFail($this->schoolId);

        return $school->subdomain.'.'.parse_url(config('app.url'), PHP_URL_HOST).':8000';
    }

    /**
     * The Record Payment modal's amount field is live and reveals an
     * "Overpayment" radio (with a helper text) once the typed amount exceeds
     * the invoice balance. That helper used to be resolved through $this from
     * a closure defined in the static table() method, which threw
     * "Using $this when not in object context" (a 500) as soon as the user
     * typed an overpayment amount.
     */
    public function test_typing_an_overpayment_amount_does_not_error(): void
    {
        $user = User::where('school_id', $this->schoolId)->where('requested_role', 'administrator')->firstOrFail();
        $this->actingAs($user)->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);
        Filament::setCurrentPanel(Filament::getPanel('app'));

        $student = Student::create([
            'school_id' => $this->schoolId,
            'student_id_number' => 'TEST-RPAY-'.uniqid(),
            'admission_number' => 'TEST-RPADM-'.uniqid(),
            'first_name' => 'Record',
            'last_name' => 'Payment',
            'gender' => 'female',
            'date_of_birth' => now()->subYears(14)->toDateString(),
            'admission_date' => now()->startOfYear()->toDateString(),
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'school_id' => $this->schoolId,
            'student_id' => $student->id,
            'invoice_number' => 'TEST-RPINV-'.strtoupper(uniqid()),
            'currency' => 'USD',
            'subtotal_amount' => 100,
            'total_amount' => 100,
            'paid_amount' => 0,
            'balance_amount' => 100,
            'status' => 'unpaid',
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'name' => 'Tuition Fees',
            'amount' => 100,
        ]);

        try {
            Livewire::test(ListInvoices::class)
                ->mountTableAction('recordPayment', $invoice)
                ->setTableActionData(['amount' => 250])
                ->assertOk()
                ->assertSee('overpay');
        } finally {
            InvoiceItem::where('invoice_id', $invoice->id)->delete();
            $invoice->delete();
            $student->forceDelete();
        }
    }
}
