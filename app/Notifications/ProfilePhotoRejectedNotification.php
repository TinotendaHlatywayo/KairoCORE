<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifies a student or staff member that their profile photo was removed by
 * an administrator and they need to upload a new passport-style photo.
 */
class ProfilePhotoRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $subject,
        public ?string $reason = null,
        public ?string $url = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format' => 'profile_photo_rejected',
            'subject' => $this->subject,
            'reason' => $this->reason,
            'url' => $this->url,
        ];
    }
}
