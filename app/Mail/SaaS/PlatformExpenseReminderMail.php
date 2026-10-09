<?php

namespace App\Mail\SaaS;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\SaaS\Models\PlatformExpense;

/**
 * Reminder that a recurring KairoCORE operating expense is approaching or
 * reached its next due date. Sent to the platform notification inbox (and the
 * expense owner, when known) at the "upcoming", "day before" and "due" stages.
 */
class PlatformExpenseReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public PlatformExpense $expense,
        public string $stage,
        public int $daysUntil,
        public CarbonInterface $dueDate,
    ) {}

    public function build(): self
    {
        $timing = match (true) {
            $this->stage === 'due' => $this->daysUntil < 0
                ? __('is now overdue')
                : __('is due today'),
            $this->stage === 'day_before' => __('is due tomorrow'),
            default => trans_choice('is due in :count day|is due in :count days', max(1, $this->daysUntil)),
        };

        return $this
            ->subject(trim($this->expense->title.' '.$timing))
            ->from(platform_system_from_email(), platform_email_name())
            ->view('modules.saas.emails.platform-expense-reminder')
            ->with([
                'expense' => $this->expense,
                'stage' => $this->stage,
                'daysUntil' => $this->daysUntil,
                'dueDate' => $this->dueDate,
                'timing' => $timing,
            ]);
    }
}
