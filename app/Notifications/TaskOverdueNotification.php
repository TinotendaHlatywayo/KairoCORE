<?php

namespace App\Notifications;

use App\Filament\App\Pages\MyDay;
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
            'url' => MyDay::getUrl(),
        ];
    }
}
