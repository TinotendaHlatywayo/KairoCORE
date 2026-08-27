<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserTask;
use App\Notifications\EventReminderNotification;
use App\Notifications\TaskOverdueNotification;
use App\Notifications\TaskReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Modules\Communication\Models\EventCalendar;

/**
 * Scheduled sweeper that turns configured reminders into database
 * notifications, reusing the existing Kairo CORE notification architecture.
 *
 * Runs every few minutes via the scheduler:
 *   - task reminders whose `reminder_at` has arrived
 *   - task overdue notifications (once per day per task)
 *   - event reminders when the event is about to start
 */
class SendScheduleReminders extends Command
{
    protected $signature = 'schedule:send-reminders';

    protected $description = 'Send due task/event reminders through the notification system';

    public function handle(): int
    {
        $now = now();

        $this->sendTaskReminders($now);
        $this->sendOverdueNotices($now);
        $this->sendEventReminders($now);

        return self::SUCCESS;
    }

    protected function sendTaskReminders(Carbon $now): void
    {
        UserTask::query()
            ->where('status', UserTask::STATUS_OPEN)
            ->whereNotNull('reminder_at')
            ->where('reminder_at', '<=', $now)
            ->whereNull('reminder_sent_at')
            ->with(['assignee', 'creator'])
            ->chunkById(200, function ($tasks) use ($now) {
                foreach ($tasks as $task) {
                    $assignee = $task->assignee;
                    if (! $assignee) {
                        continue;
                    }

                    $assignee->notify(new TaskReminderNotification($task));
                    $task->forceFill(['reminder_sent_at' => $now])->save();
                }
            });
    }

    protected function sendOverdueNotices(Carbon $now): void
    {
        UserTask::query()
            ->where('status', UserTask::STATUS_OPEN)
            ->whereNotNull('due_date')
            ->where('due_date', '<', $now->toDateString())
            ->with(['assignee', 'creator'])
            ->chunkById(200, function ($tasks) use ($now) {
                foreach ($tasks as $task) {
                    $assignee = $task->assignee;
                    if (! $assignee) {
                        continue;
                    }

                    $alreadyNotified = $assignee->notifications()
                        ->where('type', TaskOverdueNotification::class)
                        ->whereDate('created_at', $now->toDateString())
                        ->whereJsonContains('data->task_id', (string) $task->id)
                        ->exists();

                    if ($alreadyNotified) {
                        continue;
                    }

                    $assignee->notify(new TaskOverdueNotification($task));
                }
            });
    }

    protected function sendEventReminders(Carbon $now): void
    {
        EventCalendar::query()
            ->whereNotNull('reminder_minutes')
            ->whereNull('reminder_sent_at')
            ->where('start_time', '>', $now)
            ->whereRaw('DATE_SUB(start_time, INTERVAL reminder_minutes MINUTE) <= ?', [$now])
            ->chunkById(200, function ($events) use ($now) {
                foreach ($events as $event) {
                    $recipients = User::query()
                        ->where('school_id', $event->school_id)
                        ->where('account_status', User::STATUS_ACTIVE)
                        ->get(['id', 'name', 'email', 'school_id']);

                    foreach ($recipients as $recipient) {
                        $recipient->notify(new EventReminderNotification($event));
                    }

                    $event->forceFill(['reminder_sent_at' => $now])->save();
                }
            });
    }
}
