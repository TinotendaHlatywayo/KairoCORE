<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Communication\Models\EventCalendar;

/** Reminds school members that a calendar event is about to start. */
class EventReminderNotification extends Notification
{
    use Queueable;

    public function __construct(public EventCalendar $event) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format' => 'event_reminder',
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'start_time' => $this->event->start_time?->toIso8601String(),
            'location' => $this->event->location,
            'page' => 'schedule',
            // The link is resolved when the notification is read, against the
            // reader's own school. Resolving it here would bake in the host
            // that happened to be current when the notice was written — and
            // notices are written by the scheduler, where that host is the
            // platform, which would drop a tenant on the platform panel.
        ];
    }
}
