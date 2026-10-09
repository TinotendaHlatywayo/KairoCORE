<?php

namespace Modules\SaaS\Services;

use App\Mail\SaaS\PlatformExpenseReminderMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\SaaS\Models\PlatformExpense;
use Modules\SaaS\Models\PlatformExpenseReminder;
use Modules\SaaS\Models\PlatformSetting;

/**
 * Emails the platform operator as a recurring KairoCORE operating expense
 * approaches its next due date.
 *
 * The operator sees three warnings per occurrence:
 *
 *   first reminder   `expense_reminder_days` before due (default 5)
 *   day before       the day before due
 *   due              on the due date (and once if it was missed)
 *
 * Each stage is recorded in `platform_expense_reminders`, so a stage is sent at
 * most once per occurrence even if the scheduler runs repeatedly. This command
 * runs before the generator advances `next_due_date`, so the "due today" email
 * is still sent on the morning the occurrence is materialised.
 */
class PlatformExpenseReminderService
{
    public const DEFAULT_DAYS_BEFORE = 5;

    /**
     * @return array{processed:int,sent:int,skipped:int}
     */
    public function run(?Carbon $onDate = null, bool $dryRun = false): array
    {
        $today = ($onDate ? $onDate->copy() : Carbon::today())->startOfDay();
        $firstReminderDays = $this->daysBefore();

        $summary = ['processed' => 0, 'sent' => 0, 'skipped' => 0];

        $templates = PlatformExpense::query()
            ->where('is_recurring', true)
            ->whereNull('parent_expense_id')
            ->whereNotNull('recurrence')
            ->whereNotNull('next_due_date')
            ->get();

        foreach ($templates as $template) {
            $summary['processed']++;

            $due = $template->next_due_date->copy()->startOfDay();

            if ($template->recurrence_ends_at && $due->gt($template->recurrence_ends_at->copy()->endOfDay())) {
                $summary['skipped']++;

                continue;
            }

            $daysUntil = (int) round(($due->timestamp - $today->timestamp) / 86400);
            $stage = $this->stageFor($daysUntil, $firstReminderDays);

            if ($stage === null || $this->alreadySent($template, $due, $stage)) {
                $summary['skipped']++;

                continue;
            }

            if ($dryRun) {
                $summary['sent']++;

                continue;
            }

            $this->dispatch($template, $due, $stage, $daysUntil);
            $summary['sent']++;
        }

        return $summary;
    }

    /**
     * The reminder stage owed for an occurrence that is $daysUntil days away,
     * or null when it is still too far out (or the window is closed).
     */
    protected function stageFor(int $daysUntil, int $firstReminderDays): ?string
    {
        if ($daysUntil <= 0) {
            return PlatformExpenseReminder::STAGE_DUE;
        }

        if ($daysUntil === 1) {
            return PlatformExpenseReminder::STAGE_DAY_BEFORE;
        }

        if ($daysUntil <= max(1, $firstReminderDays)) {
            return PlatformExpenseReminder::STAGE_UPCOMING;
        }

        return null;
    }

    protected function alreadySent(PlatformExpense $template, Carbon $due, string $stage): bool
    {
        return PlatformExpenseReminder::query()
            ->where('platform_expense_id', $template->id)
            ->whereDate('due_date', $due->toDateString())
            ->where('stage', $stage)
            ->exists();
    }

    protected function dispatch(PlatformExpense $template, Carbon $due, string $stage, int $daysUntil): void
    {
        $recipients = array_filter([
            platform_notification_email(),
            $template->user?->email,
        ]);

        $recipients = array_values(array_unique($recipients));

        try {
            if ($recipients !== []) {
                Mail::to(array_shift($recipients))
                    ->cc($recipients)
                    ->send(new PlatformExpenseReminderMail($template, $stage, $daysUntil, $due));
            }
        } catch (\Throwable $e) {
            Log::warning('Platform expense reminder email failed', [
                'expense_id' => $template->id,
                'stage' => $stage,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        PlatformExpenseReminder::create([
            'platform_expense_id' => $template->id,
            'due_date' => $due->toDateString(),
            'stage' => $stage,
            'sent_at' => now(),
        ]);
    }

    protected function daysBefore(): int
    {
        $configured = (int) PlatformSetting::get(
            'automation',
            'expense_reminder_days',
            (string) self::DEFAULT_DAYS_BEFORE,
        );

        return $configured > 0 ? $configured : self::DEFAULT_DAYS_BEFORE;
    }
}
