<?php

namespace App\Notifications;

use App\Filament\App\Resources\ApplicationResource;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Admissions\Models\Application;

/** Notifies school staff that a new online admission application has been received. */
class NewApplicationNotification extends Notification
{
    use Queueable;

    public function __construct(public Application $application) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format' => 'admission',
            'application_id' => $this->application->id,
            'application_number' => $this->application->application_number,
            'applicant_name' => $this->application->full_name,
            'parent_name' => $this->application->parent_name,
            'parent_email' => $this->application->parent_email,
            'parent_phone' => $this->application->parent_phone,
            'url' => ApplicationResource::getUrl('edit', ['record' => $this->application]),
        ];
    }
}
