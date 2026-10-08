<?php

namespace App\Notifications;

use App\Models\School;
use App\Models\UserTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifies a school administrator that the platform team assigned them a task.
 *
 * Unlike the in-school TaskAssignedNotification, the recipient lives on a
 * tenant subdomain, so the deep link is pinned to that school's own workspace
 * rather than resolved against whatever panel the platform admin happened to be
 * using when the task was created.
 */
class PlatformTaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public UserTask $task) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $school = $this->task->school_id
            ? School::find($this->task->school_id)
            : null;

        return [
            'format' => 'task_assigned',
            'source' => 'platform',
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'assigner_name' => $this->task->creator?->name,
            'due_date' => $this->task->due_date?->toDateString(),
            'url' => $school ? tenant_workspace_url($school, 'workspace/my-day') : null,
        ];
    }
}
