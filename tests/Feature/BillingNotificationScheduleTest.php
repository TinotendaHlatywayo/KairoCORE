<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\SaaS\Models\PlatformBillingNotificationLog;
use Modules\SaaS\Models\PlatformBillingSetting;
use Modules\SaaS\Models\PlatformMessage;
use Modules\SaaS\Models\SaaSPlan;
use Modules\SaaS\Models\SaaSSubscription;
use Modules\SaaS\Services\BillingNotificationService;
use Tests\TestCase;

/**
 * Exercises the per-tenant billing lifecycle against the real schema.
 *
 * The sweep date is fixed in early 2026 while every other tenant in the
 * development database was created later, so their free-period guard skips
 * them and only the fixture tenant is touched.
 */
class BillingNotificationScheduleTest extends TestCase
{
    private School $school;

    private SaaSPlan $plan;

    private SaaSSubscription $subscription;

    private array $originalSettings = [];

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        Config::set('mail.default', 'log');
        DB::purge('mysql');

        $settings = PlatformBillingSetting::current();
        $this->originalSettings = $settings->getAttributes();
        $settings->update([
            'remind_days_before' => 3,
            'day_before_reminder_offset' => 1,
            'overdue_reminder_offset' => 1,
            'suspension_warning_offset' => 4,
            'suspension_offset' => 5,
            'data_retention_months' => 6,
            'notify_super_admin_on_billing' => true,
            'super_admin_billing_email' => 'billing-test@example.com',
        ]);

        $suffix = substr(uniqid(), -6);

        $this->school = School::create([
            'name' => 'Billing Schedule Test School',
            'subdomain' => 'billing-sched-'.$suffix,
            'status' => 'active',
            'email_address' => 'billing-sched-'.$suffix.'@example.com',
        ]);

        $this->plan = SaaSPlan::create([
            'name' => 'Billing Schedule Plan',
            'slug' => 'billing-schedule-'.$suffix,
            'price_monthly' => 10,
            'price_quarterly' => 30,
            'price_yearly' => 120,
            'currency' => 'USD',
            'grace_days' => 5,
            'is_active' => true,
        ]);

        $this->subscription = SaaSSubscription::create([
            'school_id' => $this->school->id,
            'saas_plan_id' => $this->plan->id,
            'billing_period' => 'monthly',
            'status' => 'active',
            'billing_start_date' => '2025-12-05',
            'billing_day_of_month' => 5,
            'next_payment_date' => '2026-01-05',
        ]);
    }

    protected function tearDown(): void
    {
        $schoolId = $this->school->id;

        PlatformBillingNotificationLog::query()->where('school_id', $schoolId)->delete();
        PlatformMessage::withoutGlobalScopes()->whereHas('recipients', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })->delete();

        User::query()->where('school_id', $schoolId)->delete();
        SaaSSubscription::query()->where('school_id', $schoolId)->forceDelete();

        $this->school->forceDelete();
        $this->plan->forceDelete();

        $settings = PlatformBillingSetting::current();
        $settings->update([
            'super_admin_billing_email' => $this->originalSettings['super_admin_billing_email'] ?? null,
        ]);

        parent::tearDown();
    }

    private function runOn(string $date): array
    {
        return app(BillingNotificationService::class)->run(Carbon::parse($date));
    }

    private function logExists(string $key, string $billingDate = '2026-01-05'): bool
    {
        return PlatformBillingNotificationLog::query()
            ->where('school_id', $this->school->id)
            ->where('template_key', $key)
            ->whereDate('billing_date', $billingDate)
            ->exists();
    }

    public function test_first_reminder_fires_three_days_before_and_is_idempotent(): void
    {
        $this->runOn('2026-01-02');

        $this->assertTrue($this->logExists('billing_reminder'));
        $this->assertFalse($this->logExists('billing_overdue'));

        $this->runOn('2026-01-02');

        $this->assertSame(1, PlatformBillingNotificationLog::query()
            ->where('school_id', $this->school->id)
            ->where('template_key', 'billing_reminder')
            ->count());
    }

    public function test_billing_day_sends_notice_and_super_admin_alert(): void
    {
        $this->runOn('2026-01-05');

        $this->assertTrue($this->logExists('billing_due_today'));
        $this->assertTrue($this->logExists('super_admin_billing_alert'));
    }

    public function test_overdue_then_suspension_flow(): void
    {
        $this->runOn('2026-01-06');
        $this->assertTrue($this->logExists('billing_overdue'));

        $this->runOn('2026-01-09');
        $this->assertTrue($this->logExists('billing_suspension_warning'));

        $this->runOn('2026-01-10');
        $this->assertTrue($this->logExists('billing_suspended'));

        $this->assertSame('suspended', $this->subscription->fresh()->status);
        $this->assertSame('suspended', $this->school->fresh()->status);
    }

    public function test_paid_tenant_is_not_chased(): void
    {
        $this->subscription->update(['next_payment_date' => '2026-02-05']);

        $this->runOn('2026-01-02');

        $this->assertFalse($this->logExists('billing_reminder'));
    }

    public function test_free_period_suppresses_everything(): void
    {
        $this->subscription->update([
            'billing_start_date' => '2026-03-05',
            'next_payment_date' => '2026-03-05',
        ]);

        $this->runOn('2026-01-02');

        $this->assertSame(0, PlatformBillingNotificationLog::query()
            ->where('school_id', $this->school->id)
            ->count());
    }
}
