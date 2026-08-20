<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\SaaS\Models\PlatformMessage;

/**
 * Database notification raised when a platform<->tenant message is delivered.
 */
class PlatformMessageNotification extends Notification
{
    use Queueable;

    public function __construct(public PlatformMessage $message) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $fromPlatform = $this->message->isFromPlatform();

        return [
            'format' => 'platform_message',
            'message_id' => $this->message->id,
            'thread_id' => $this->message->thread_id,
            'sender_type' => $this->message->sender_type,
            'sender_label' => $fromPlatform
                ? 'SchoolCore Platform'
                : ($this->message->school?->name ?? 'A school'),
            'subject' => $this->message->subject,
            'preview' => mb_strimwidth($this->message->body, 0, 120, '…'),
        ];
    }
}
