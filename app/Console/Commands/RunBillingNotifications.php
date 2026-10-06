<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\SaaS\Services\BillingNotificationService;

class RunBillingNotifications extends Command
{
    protected $signature = 'saas:run-billing-notifications {--date= : Run as if today were this date (Y-m-d)}';

    protected $description = 'Sends the per-tenant SaaS billing reminders and suspends accounts that remain unpaid';

    public function handle(BillingNotificationService $service): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : null;

        $summary = $service->run($date);

        $this->info(sprintf(
            'Billing sweep complete: %d tenants processed, %d messages sent, %d skipped, %d suspended.',
            $summary['processed'],
            $summary['sent'],
            $summary['skipped'],
            $summary['suspended'],
        ));

        return self::SUCCESS;
    }
}
