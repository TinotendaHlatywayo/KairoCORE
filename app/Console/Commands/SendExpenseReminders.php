<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\SaaS\Models\PlatformSetting;
use Modules\SaaS\Services\PlatformExpenseReminderService;

/**
 * Emails reminder notices for recurring KairoCORE operating expenses that are
 * approaching (or have reached) their next due date.
 */
class SendExpenseReminders extends Command
{
    protected $signature = 'finance:send-expense-reminders {--dry-run : Report what would happen without emailing}';

    protected $description = 'Sends upcoming/due reminders for recurring platform (KairoCORE) expenses';

    public function handle(PlatformExpenseReminderService $service): int
    {
        if (! $this->remindersEnabled()) {
            $this->info('Platform expense reminders are disabled in Platform Settings.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');

        $summary = $service->run(dryRun: $dryRun);

        $this->info(sprintf(
            'Platform expense reminders complete: %d %s across %d recurring expense(s).',
            $summary['sent'],
            $dryRun ? 'matched (dry run)' : 'sent',
            $summary['processed'],
        ));

        return self::SUCCESS;
    }

    protected function remindersEnabled(): bool
    {
        return filter_var(
            PlatformSetting::get('automation', 'send_expense_reminders', '1'),
            FILTER_VALIDATE_BOOLEAN,
        );
    }
}
