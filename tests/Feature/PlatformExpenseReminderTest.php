<?php

namespace Tests\Feature;

use App\Mail\SaaS\PlatformExpenseReminderMail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\SaaS\Models\PlatformExpense;
use Modules\SaaS\Models\PlatformExpenseReminder;
use Modules\SaaS\Services\PlatformExpenseReminderService;
use Tests\TestCase;

/**
 * The recurring-expense lifecycle must warn the operator five days before, one
 * day before and on the due date of each occurrence, without ever emailing the
 * same reminder twice.
 */
class PlatformExpenseReminderTest extends TestCase
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

    public function test_it_reminds_five_days_one_day_and_on_the_due_date_without_duplicates(): void
    {
        DB::beginTransaction();

        try {
            Mail::fake();

            $today = now()->startOfDay();
            $expense = $this->recurringExpense($today->copy()->addDays(5));
            $service = app(PlatformExpenseReminderService::class);

            $service->run($today);
            Mail::assertSent(PlatformExpenseReminderMail::class, 1);
            $this->assertDatabaseHas('platform_expense_reminders', [
                'platform_expense_id' => $expense->id,
                'stage' => PlatformExpenseReminder::STAGE_UPCOMING,
            ]);

            // Running again on the same day must not resend.
            $service->run($today);
            Mail::assertSent(PlatformExpenseReminderMail::class, 1);

            // Day before.
            $service->run($today->copy()->addDays(4));
            Mail::assertSent(PlatformExpenseReminderMail::class, 2);
            $this->assertDatabaseHas('platform_expense_reminders', [
                'platform_expense_id' => $expense->id,
                'stage' => PlatformExpenseReminder::STAGE_DAY_BEFORE,
            ]);

            // Due date.
            $service->run($today->copy()->addDays(5));
            Mail::assertSent(PlatformExpenseReminderMail::class, 3);
            $this->assertDatabaseHas('platform_expense_reminders', [
                'platform_expense_id' => $expense->id,
                'stage' => PlatformExpenseReminder::STAGE_DUE,
            ]);
        } finally {
            DB::rollBack();
        }
    }

    public function test_it_does_not_remind_before_the_configured_window(): void
    {
        DB::beginTransaction();

        try {
            Mail::fake();

            $today = now()->startOfDay();
            $this->recurringExpense($today->copy()->addDays(20));

            app(PlatformExpenseReminderService::class)->run($today);

            Mail::assertNothingSent();
            $this->assertSame(0, PlatformExpenseReminder::count());
        } finally {
            DB::rollBack();
        }
    }

    public function test_the_command_sends_due_reminders(): void
    {
        DB::beginTransaction();

        try {
            Mail::fake();

            $today = now()->startOfDay();
            $expense = $this->recurringExpense($today);

            $this->artisan('finance:send-expense-reminders')->assertExitCode(0);

            Mail::assertSent(PlatformExpenseReminderMail::class);
            $this->assertDatabaseHas('platform_expense_reminders', [
                'platform_expense_id' => $expense->id,
                'stage' => PlatformExpenseReminder::STAGE_DUE,
            ]);
        } finally {
            DB::rollBack();
        }
    }

    private function recurringExpense(Carbon $nextDue): PlatformExpense
    {
        return PlatformExpense::create([
            'title' => 'VM hosting '.substr(uniqid(), -6),
            'category' => 'Hosting & Domains',
            'amount' => 25.00,
            'currency' => 'USD',
            'expense_date' => now()->startOfDay()->toDateString(),
            'is_recurring' => true,
            'recurrence' => 'monthly',
            'next_due_date' => $nextDue->toDateString(),
            'is_paid' => false,
        ]);
    }
}
