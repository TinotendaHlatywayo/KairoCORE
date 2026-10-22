<?php

namespace App\Notifications;

use App\Models\UserTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Warns the assignee that an open task has passed its due date. */
class TaskOverdueNotification extends Notification
{
    use Queueable;

    public function __construct(public UserTask $task) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format' => 'task_overdue',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'due_date' => $this->task->due_date?->toDateString(),
            'page' => 'my-day',
            // The link is resolved when the notification is read, against the
            // reader's own school. Resolving it here would bake in the host
            // that happened to be current when the notice was written — and
            // notices are written by the scheduler, where that host is the
            // platform, which would drop a tenant on the platform panel.
        ];
    }
}
