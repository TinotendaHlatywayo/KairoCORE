<?php

namespace App\Notifications;

use App\Filament\App\Pages\Schedule;
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
            'url' => Schedule::getUrl(),
        ];
    }
}
