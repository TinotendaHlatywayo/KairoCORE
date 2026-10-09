<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\PlatformBillingStatements;
use App\Filament\Admin\Resources\SaaSInvoiceResource\Pages\ListSaaSInvoices;
use App\Filament\Admin\Resources\SchoolSubscriptionResource\Pages\ListSchoolSubscriptions;
use App\Models\School;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\SaaS\Models\SaaSInvoice;
use Modules\SaaS\Models\SaaSPlan;
use Modules\SaaS\Models\SaaSReceipt;
use Modules\SaaS\Models\SaaSSubscription;
use Modules\SaaS\Models\SaaSTransaction;
use Modules\SaaS\Services\BillingService;
use Modules\SaaS\Services\PlatformFinanceService;
use Tests\TestCase;

/**
 * Platform-admin billing documents: bulk invoice generation for every school,
 * per-school payment history, and the downloadable invoice/receipt documents
 * surfaced from the subscription and invoice tables.
 */
class PlatformBillingDocumentsTest extends TestCase
{
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
    }

    public function test_bulk_generation_invoices_billable_schools_and_skips_open_invoices(): void
    {
        DB::beginTransaction();

        try {
            $plan = $this->makePlan();

            $billable = SaaSSubscription::create([
                'school_id' => $this->makeSchool()->id,
                'saas_plan_id' => $plan->id,
                'billing_period' => 'monthly',
                'status' => 'active',
                'billing_start_date' => now()->subDays(3)->toDateString(),
            ]);

            $alreadyInvoiced = SaaSSubscription::create([
                'school_id' => $this->makeSchool()->id,
                'saas_plan_id' => $plan->id,
                'billing_period' => 'monthly',
                'status' => 'active',
                'billing_start_date' => now()->subDays(3)->toDateString(),
            ]);

            SaaSInvoice::create([
                'school_id' => $alreadyInvoiced->school_id,
                'saas_subscription_id' => $alreadyInvoiced->id,
                'invoice_number' => 'INV-OPEN-'.uniqid(),
                'issue_date' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'subtotal' => 50.00,
                'discount' => 0.00,
                'total' => 50.00,
                'currency' => 'USD',
                'status' => 'unpaid',
            ]);

            app(BillingService::class)->generateInvoicesForAllSchools();

            $this->assertSame(1, SaaSInvoice::where('saas_subscription_id', $billable->id)->count());
            $this->assertSame(1, SaaSInvoice::where('saas_subscription_id', $alreadyInvoiced->id)->count());
        } finally {
            DB::rollBack();
        }
    }

    public function test_payment_history_combines_invoices_receipts_and_payments(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $plan = $this->makePlan();

            $subscription = SaaSSubscription::create([
                'school_id' => $school->id,
                'saas_plan_id' => $plan->id,
                'billing_period' => 'monthly',
                'status' => 'active',
                'billing_start_date' => now()->subDays(5)->toDateString(),
            ]);

            $invoice = SaaSInvoice::create([
                'school_id' => $school->id,
                'saas_subscription_id' => $subscription->id,
                'invoice_number' => 'INV-HIST-'.uniqid(),
                'issue_date' => '2031-01-01',
                'due_date' => '2031-01-07',
                'subtotal' => 120.00,
                'discount' => 0.00,
                'total' => 120.00,
                'currency' => 'USD',
                'status' => 'paid',
            ]);

            $transaction = SaaSTransaction::create([
                'school_id' => $school->id,
                'saas_invoice_id' => $invoice->id,
                'payment_gateway_key' => 'paynow',
                'transaction_reference' => 'hist-'.uniqid(),
                'amount' => 120.00,
                'currency' => 'USD',
                'status' => 'completed',
                'processed_at' => '2031-01-03 10:00:00',
            ]);

            SaaSReceipt::create([
                'school_id' => $school->id,
                'saas_invoice_id' => $invoice->id,
                'saas_transaction_id' => $transaction->id,
                'receipt_number' => 'RCP-HIST-'.uniqid(),
                'amount_paid' => 120.00,
                'currency' => 'USD',
                'issued_at' => '2031-01-03 10:05:00',
            ]);

            $history = app(PlatformFinanceService::class)->paymentHistory($school->id);

            $this->assertSame(120.0, $history['invoiced_total']);
            $this->assertSame(120.0, $history['receipts_total']);
            $this->assertSame(120.0, $history['payments_total']);
            $this->assertSame(0.0, $history['outstanding_total']);
            $this->assertCount(3, $history['entries']);
        } finally {
            DB::rollBack();
        }
    }

    public function test_billing_documents_page_prefills_the_school_from_the_query_string(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();

            $admin = $this->admin();

            $this->actingAs($admin);
            Filament::setCurrentPanel(Filament::getPanel('admin'));

            Livewire::withQueryParams(['school_id' => $school->id])
                ->test(PlatformBillingStatements::class)
                ->assertOk()
                ->assertSet('statement.school.id', $school->id);
        } finally {
            DB::rollBack();
        }
    }

    public function test_the_bulk_invoice_action_runs_from_the_subscriptions_table(): void
    {
        DB::beginTransaction();

        try {
            $plan = $this->makePlan();

            SaaSSubscription::create([
                'school_id' => $this->makeSchool()->id,
                'saas_plan_id' => $plan->id,
                'billing_period' => 'monthly',
                'status' => 'active',
                'billing_start_date' => now()->subDays(3)->toDateString(),
            ]);

            $this->actingAs($this->admin());
            Filament::setCurrentPanel(Filament::getPanel('admin'));

            Livewire::test(ListSchoolSubscriptions::class)
                ->callAction('generate_all_invoices')
                ->assertHasNoActionErrors();
        } finally {
            DB::rollBack();
        }
    }

    public function test_the_invoice_table_exposes_a_receipt_download_when_paid(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $plan = $this->makePlan();

            $subscription = SaaSSubscription::create([
                'school_id' => $school->id,
                'saas_plan_id' => $plan->id,
                'billing_period' => 'monthly',
                'status' => 'active',
                'billing_start_date' => now()->subDays(5)->toDateString(),
            ]);

            $invoice = SaaSInvoice::create([
                'school_id' => $school->id,
                'saas_subscription_id' => $subscription->id,
                'invoice_number' => 'INV-RCP-'.uniqid(),
                'issue_date' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'subtotal' => 30.00,
                'discount' => 0.00,
                'total' => 30.00,
                'currency' => 'USD',
                'status' => 'paid',
            ]);

            $transaction = SaaSTransaction::create([
                'school_id' => $school->id,
                'saas_invoice_id' => $invoice->id,
                'payment_gateway_key' => 'manual_bank',
                'transaction_reference' => 'rcp-'.uniqid(),
                'amount' => 30.00,
                'currency' => 'USD',
                'status' => 'completed',
                'processed_at' => now(),
            ]);

            SaaSReceipt::create([
                'school_id' => $school->id,
                'saas_invoice_id' => $invoice->id,
                'saas_transaction_id' => $transaction->id,
                'receipt_number' => 'RCP-TBL-'.uniqid(),
                'amount_paid' => 30.00,
                'currency' => 'USD',
                'issued_at' => now(),
            ]);

            $this->actingAs($this->admin());
            Filament::setCurrentPanel(Filament::getPanel('admin'));

            Livewire::test(ListSaaSInvoices::class)
                ->assertOk()
                ->assertTableActionVisible('download_receipt', $invoice);
        } finally {
            DB::rollBack();
        }
    }

    public function test_the_branded_saas_pdf_views_render(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $plan = $this->makePlan();

            $subscription = SaaSSubscription::create([
                'school_id' => $school->id,
                'saas_plan_id' => $plan->id,
                'billing_period' => 'monthly',
                'status' => 'active',
                'billing_start_date' => now()->subDays(5)->toDateString(),
            ]);

            $invoice = SaaSInvoice::create([
                'school_id' => $school->id,
                'saas_subscription_id' => $subscription->id,
                'invoice_number' => 'INV-PDF-'.uniqid(),
                'issue_date' => now()->toDateString(),
                'due_date' => now()->toDateString(),
                'subtotal' => 40.00,
                'discount' => 0.00,
                'total' => 40.00,
                'currency' => 'USD',
                'status' => 'unpaid',
                'payment_instructions' => 'Please use the invoice number as your payment reference.',
            ]);

            $transaction = SaaSTransaction::create([
                'school_id' => $school->id,
                'saas_invoice_id' => $invoice->id,
                'payment_gateway_key' => 'paynow',
                'transaction_reference' => 'pdf-'.uniqid(),
                'amount' => 40.00,
                'currency' => 'USD',
                'status' => 'completed',
                'processed_at' => now(),
            ]);

            $receipt = SaaSReceipt::create([
                'school_id' => $school->id,
                'saas_invoice_id' => $invoice->id,
                'saas_transaction_id' => $transaction->id,
                'receipt_number' => 'RCP-PDF-'.uniqid(),
                'amount_paid' => 40.00,
                'currency' => 'USD',
                'issued_at' => now(),
            ]);

            $report = app(PlatformFinanceService::class)->summary(now()->startOfYear(), now());
            $statement = app(PlatformFinanceService::class)->tenantStatement($school->id, now()->startOfYear(), now());
            $history = app(PlatformFinanceService::class)->paymentHistory($school->id);

            $this->assertNotEmpty(Pdf::loadView('modules.saas.pdf.invoice', ['invoice' => $invoice])->output());
            $this->assertNotEmpty(Pdf::loadView('modules.saas.pdf.receipt', ['receipt' => $receipt])->output());
            $this->assertNotEmpty(Pdf::loadView('modules.saas.pdf.platform-financial-statement', ['report' => $report])->output());
            $this->assertNotEmpty(Pdf::loadView('modules.saas.pdf.tenant-billing-statement', ['statement' => $statement])->output());
            $this->assertNotEmpty(Pdf::loadView('modules.saas.pdf.payment-history', ['history' => $history])->output());
        } finally {
            DB::rollBack();
        }
    }

    private function admin(): User
    {
        return User::whereNull('school_id')
            ->where('account_status', User::STATUS_ACTIVE)
            ->firstOrFail();
    }

    private function makeSchool(): School
    {
        return School::create([
            'name' => 'Docs School '.substr(uniqid(), -6),
            'subdomain' => 'docs-'.substr(uniqid(), -8),
            'status' => 'active',
        ]);
    }

    private function makePlan(): SaaSPlan
    {
        $existing = SaaSPlan::query()->first();

        if ($existing) {
            return $existing;
        }

        return SaaSPlan::create([
            'name' => 'Docs Test Plan',
            'slug' => 'docs-test-'.substr(uniqid(), -8),
            'price_monthly' => 50.00,
            'currency' => 'USD',
            'trial_days' => 14,
            'grace_days' => 7,
            'is_active' => true,
        ]);
    }
}
