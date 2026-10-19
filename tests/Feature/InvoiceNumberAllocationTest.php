<?php

namespace Tests\Feature;

use App\Models\School;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\SaaS\Models\SaaSInvoice;
use Modules\SaaS\Models\SaaSPlan;
use Modules\SaaS\Models\SaaSSubscription;
use Modules\SaaS\Services\BillingService;
use Tests\TestCase;

/**
 * Regression: invoice-number allocation must never reuse a number that a
 * soft-deleted invoice still holds in the unique index, and must recover
 * from a duplicate-key race instead of bubbling a QueryException to the UI.
 */
class InvoiceNumberAllocationTest extends TestCase
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

    public function test_soft_deleted_invoice_numbers_are_never_reused(): void
    {
        DB::beginTransaction();

        try {
            $subscription = $this->makeBillableSubscription();

            $first = app(BillingService::class)->generateUpcomingInvoice($subscription);
            $first->update(['status' => 'paid']);
            $first->delete(); // soft delete — number stays in the unique index

            $second = app(BillingService::class)->generateUpcomingInvoice($subscription->fresh());

            $this->assertNotSame($first->invoice_number, $second->invoice_number);
            $this->assertTrue(
                SaaSInvoice::withTrashed()->where('invoice_number', $second->invoice_number)->exists()
            );

            DB::rollBack();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function test_open_invoice_is_returned_instead_of_duplicating(): void
    {
        DB::beginTransaction();

        try {
            $subscription = $this->makeBillableSubscription();

            $first = app(BillingService::class)->generateUpcomingInvoice($subscription);
            $again = app(BillingService::class)->generateUpcomingInvoice($subscription->fresh());

            $this->assertSame($first->id, $again->id);

            DB::rollBack();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    private function makeBillableSubscription(): SaaSSubscription
    {
        $plan = SaaSPlan::create([
            'name' => 'Allocation Test Plan '.uniqid(),
            'price_monthly' => 50.00,
            'price_quarterly' => 135.00,
            'price_yearly' => 480.00,
            'is_active' => true,
        ]);

        $school = School::create([
            'name' => 'Allocation Test School '.substr(uniqid(), -6),
            'subdomain' => 'alloc-'.substr(uniqid(), -8),
            'status' => 'active',
        ]);

        return SaaSSubscription::create([
            'school_id' => $school->id,
            'saas_plan_id' => $plan->id,
            'billing_period' => 'monthly',
            'status' => 'active',
            'billing_start_date' => now()->subDays(3)->toDateString(),
        ]);
    }
}
