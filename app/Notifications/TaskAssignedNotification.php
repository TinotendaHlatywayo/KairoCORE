<?php

namespace App\Notifications;

use App\Filament\App\Pages\MyDay;
use App\Models\UserTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Informs the assignee that a task was assigned to them by a colleague. */
class TaskAssignedNotification extends Notification
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
            'format' => 'task_assigned',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'assigner_name' => $this->task->creator?->name,
            'due_date' => $this->task->due_date?->toDateString(),
            'url' => MyDay::getUrl(),
        ];
    }
}
