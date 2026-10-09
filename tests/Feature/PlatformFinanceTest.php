<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\PlatformBillingStatements;
use App\Filament\Admin\Pages\PlatformFinance;
use App\Filament\Admin\Resources\PlatformExpenseResource\Pages\CreatePlatformExpense;
use App\Filament\Admin\Resources\PlatformExpenseResource\Pages\ListPlatformExpenses;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\SaaS\Models\PlatformExpense;
use Modules\SaaS\Models\SaaSInvoice;
use Modules\SaaS\Models\SaaSPlan;
use Modules\SaaS\Models\SaaSReceipt;
use Modules\SaaS\Models\SaaSSubscription;
use Modules\SaaS\Models\SaaSTransaction;
use Modules\SaaS\Services\PlatformFinanceService;
use Tests\TestCase;

/**
 * Phase 2 platform finance guarantees:
 *  - recurring expenses are materialised and their next due date advances
 *  - recurrence honours an end date
 *  - the financial summary nets revenue against operating expenses
 *  - a tenant statement combines invoices, receipts and payments
 *  - the admin expense resource records expenses (with recurrence defaults)
 */
class PlatformFinanceTest extends TestCase
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

    public function test_recurrence_advancement_never_spills_into_the_next_month(): void
    {
        $next = PlatformExpense::nextOccurrenceFor('monthly', Carbon::parse('2026-01-31'));

        $this->assertSame('2026-02-28', $next->toDateString());
    }

    public function test_the_recurring_expense_command_generates_due_occurrences(): void
    {
        DB::beginTransaction();

        try {
            $today = now()->startOfDay();

            $template = PlatformExpense::create([
                'title' => 'VM hosting',
                'category' => 'Hosting & Domains',
                'amount' => 25.00,
                'currency' => 'USD',
                'expense_date' => $today->copy()->subMonthNoOverflow()->toDateString(),
                'is_recurring' => true,
                'recurrence' => 'monthly',
                'next_due_date' => $today->toDateString(),
                'is_paid' => false,
            ]);

            $this->artisan('finance:generate-recurring-expenses')->assertExitCode(0);

            $children = PlatformExpense::where('parent_expense_id', $template->id)->get();

            $this->assertCount(1, $children);
            $this->assertFalse((bool) $children->first()->is_recurring);
            $this->assertSame($today->toDateString(), $children->first()->expense_date->toDateString());
            $this->assertFalse((bool) $children->first()->is_paid);

            $this->assertTrue($template->fresh()->next_due_date->gt($today));
        } finally {
            DB::rollBack();
        }
    }

    public function test_recurring_expenses_stop_at_the_end_date(): void
    {
        DB::beginTransaction();

        try {
            $today = now()->startOfDay();

            $template = PlatformExpense::create([
                'title' => 'Legacy domain',
                'amount' => 12.00,
                'currency' => 'USD',
                'expense_date' => $today->copy()->subMonthNoOverflow()->toDateString(),
                'is_recurring' => true,
                'recurrence' => 'monthly',
                'next_due_date' => $today->toDateString(),
                'recurrence_ends_at' => $today->copy()->subDay()->toDateString(),
                'is_paid' => false,
            ]);

            $this->artisan('finance:generate-recurring-expenses')->assertExitCode(0);

            $this->assertSame(0, PlatformExpense::where('parent_expense_id', $template->id)->count());
        } finally {
            DB::rollBack();
        }
    }

    public function test_the_financial_summary_nets_revenue_against_expenses(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();
            $at = Carbon::parse('2030-03-15 10:00:00');

            SaaSTransaction::create([
                'school_id' => $school->id,
                'payment_gateway_key' => 'paynow',
                'transaction_reference' => 'fin-'.uniqid(),
                'amount' => 100.00,
                'currency' => 'USD',
                'status' => 'completed',
                'processed_at' => $at,
            ]);

            PlatformExpense::create([
                'title' => 'Cloud compute',
                'category' => 'Cloud Infrastructure',
                'amount' => 40.00,
                'currency' => 'USD',
                'expense_date' => '2030-03-10',
                'is_paid' => true,
                'paid_at' => $at,
            ]);

            $report = app(PlatformFinanceService::class)->summary('2030-03-01', '2030-03-31');

            $this->assertSame(100.0, $report['revenue']);
            $this->assertSame(40.0, $report['expenses']);
            $this->assertSame(60.0, $report['net_profit']);
            $this->assertSame(40.0, $report['expenses_paid']);
            $this->assertSame(0.0, $report['expenses_unpaid']);
            $this->assertSame(60.0, $report['margin']);

            $this->assertSame('Cloud Infrastructure', $report['by_category'][0]['category']);
            $this->assertSame(40.0, $report['by_category'][0]['total']);
        } finally {
            DB::rollBack();
        }
    }

    public function test_the_tenant_statement_combines_invoices_receipts_and_payments(): void
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
            ]);

            $invoice = SaaSInvoice::create([
                'school_id' => $school->id,
                'saas_subscription_id' => $subscription->id,
                'invoice_number' => 'INV-FIN-'.uniqid(),
                'issue_date' => '2030-04-01',
                'due_date' => '2030-04-07',
                'subtotal' => 200.00,
                'discount' => 0.00,
                'total' => 200.00,
                'currency' => 'USD',
                'status' => 'unpaid',
            ]);

            $transaction = SaaSTransaction::create([
                'school_id' => $school->id,
                'saas_invoice_id' => $invoice->id,
                'payment_gateway_key' => 'paynow',
                'transaction_reference' => 'stmt-'.uniqid(),
                'amount' => 200.00,
                'currency' => 'USD',
                'status' => 'completed',
                'processed_at' => '2030-04-03 09:00:00',
            ]);

            SaaSReceipt::create([
                'school_id' => $school->id,
                'saas_invoice_id' => $invoice->id,
                'saas_transaction_id' => $transaction->id,
                'receipt_number' => 'RCP-FIN-'.uniqid(),
                'amount_paid' => 200.00,
                'currency' => 'USD',
                'issued_at' => '2030-04-03 09:05:00',
            ]);

            $statement = app(PlatformFinanceService::class)->tenantStatement($school->id, '2030-04-01', '2030-04-30');

            $this->assertSame($school->id, $statement['school']->id);
            $this->assertSame(200.0, $statement['invoiced_total']);
            $this->assertSame(200.0, $statement['receipts_total']);
            $this->assertSame(200.0, $statement['payments_total']);
            $this->assertSame(200.0, $statement['outstanding_total']);
            $this->assertCount(1, $statement['invoices']);
            $this->assertCount(1, $statement['receipts']);
            $this->assertCount(1, $statement['payments']);
        } finally {
            DB::rollBack();
        }
    }

    public function test_the_admin_expense_form_records_a_recurring_expense_with_a_default_due_date(): void
    {
        DB::beginTransaction();

        try {
            $admin = User::whereNull('school_id')
                ->where('account_status', User::STATUS_ACTIVE)
                ->firstOrFail();

            $this->actingAs($admin);
            Filament::setCurrentPanel(Filament::getPanel('admin'));

            Livewire::test(ListPlatformExpenses::class)->assertOk();

            Livewire::test(CreatePlatformExpense::class)
                ->fillForm([
                    'title' => 'SMTP relay',
                    'category' => 'Hosting & Domains',
                    'amount' => 15.00,
                    'currency' => 'USD',
                    'expense_date' => '2030-05-01',
                    'is_recurring' => true,
                    'recurrence' => 'monthly',
                ])
                ->call('create')
                ->assertHasNoFormErrors();

            $this->assertDatabaseHas('platform_expenses', [
                'title' => 'SMTP relay',
                'is_recurring' => true,
                'next_due_date' => '2030-06-01',
            ]);
        } finally {
            DB::rollBack();
        }
    }

    public function test_the_platform_finance_page_builds_a_report(): void
    {
        DB::beginTransaction();

        try {
            $admin = User::whereNull('school_id')
                ->where('account_status', User::STATUS_ACTIVE)
                ->firstOrFail();

            $this->actingAs($admin);
            Filament::setCurrentPanel(Filament::getPanel('admin'));

            Livewire::test(PlatformFinance::class)
                ->assertOk()
                ->assertSet('report.revenue', fn ($value) => is_numeric($value));
        } finally {
            DB::rollBack();
        }
    }

    public function test_the_tenant_statement_page_builds_a_statement(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->makeSchool();

            $admin = User::whereNull('school_id')
                ->where('account_status', User::STATUS_ACTIVE)
                ->firstOrFail();

            $this->actingAs($admin);
            Filament::setCurrentPanel(Filament::getPanel('admin'));

            Livewire::test(PlatformBillingStatements::class)
                ->fillForm([
                    'school_id' => $school->id,
                    'start_date' => '2030-04-01',
                    'end_date' => '2030-04-30',
                ])
                ->call('generate')
                ->assertOk()
                ->assertSet('statement.school.id', $school->id);
        } finally {
            DB::rollBack();
        }
    }

    private function makeSchool(): School
    {
        return School::create([
            'name' => 'Finance School '.substr(uniqid(), -6),
            'subdomain' => 'finance-'.substr(uniqid(), -8),
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
            'name' => 'Finance Test Plan',
            'slug' => 'finance-test-'.substr(uniqid(), -8),
            'price_monthly' => 50.00,
            'currency' => 'USD',
            'trial_days' => 14,
            'grace_days' => 7,
            'is_active' => true,
        ]);
    }
}
