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
            // Category drives the distinct CHAT styling in the Command Center
            // and the notification-history filters.
            'category' => 'chat',
            'format' => 'platform_message',
            'message_id' => $this->message->id,
            'thread_id' => $this->message->thread_id,
            'sender_type' => $this->message->sender_type,
            'sender_label' => $fromPlatform
                ? platform_name()
                : ($this->message->school?->name ?? 'A school'),
            'subject' => $this->message->subject,
            'preview' => mb_strimwidth($this->message->body, 0, 120, '…'),
            // Deep link is best-effort and opens THE ACTUAL MESSAGE via
            // Filament's table-action deep link (?tableAction=view_thread&
            // tableActionRecord=id). Only audiences that may actually open an
            // inbox get a URL (students don't), panels are pinned explicitly,
            // and tenants can never be pointed at the platform panel.
            'url' => (function () use ($notifiable): ?string {
                try {
                    if (filled($notifiable->school_id ?? null)) {
                        if (! \Modules\Admin\Services\PermissionRegistry::checkPermission('communication.contact_platform')) {
                            return null;
                        }

                        return tenant_workspace_url(
                            $notifiable->school,
                            'workspace/platform-inboxes?tableAction=view_thread&tableActionRecord='.$this->message->id,
                        );
                    }

                    return \App\Filament\Admin\Resources\PlatformMessageResource::getUrl(panel: 'admin')
                        .'?tableAction=view_thread&tableActionRecord='.$this->message->id;
                } catch (\Throwable) {
                    return null;
                }
            })(),
        ];
    }
}
