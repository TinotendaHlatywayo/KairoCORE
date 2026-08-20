<?php

namespace App\Notifications;

use App\Filament\App\Pages\MyDay;
use App\Models\UserTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Reminds the assignee that a task is due soon. */
class TaskReminderNotification extends Notification
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
            'format' => 'task_reminder',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'due_date' => $this->task->due_date?->toDateString(),
            'due_time' => $this->task->due_time,
            'url' => MyDay::getUrl(),
        ];
    }
}
