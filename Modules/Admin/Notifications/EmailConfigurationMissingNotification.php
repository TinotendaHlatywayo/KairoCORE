<?php

namespace Modules\Admin\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Admin\Enums\EmailCategory;

/**
 * Database notification sent to school administrators when an email is skipped
 * because the tenant's email configuration is missing or invalid. Never sent
 * via email — this would defeat the purpose of alerting about a broken mail path.
 */
class EmailConfigurationMissingNotification extends Notification
{
    use Queueable;

    public function __construct(
        public EmailCategory $category,
        public string $reason = 'No email configuration found.',
        public string $recipient = '',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format' => 'email_config_missing',
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'reason' => $this->reason,
            'recipient' => $this->recipient,
            'url' => '/app/email-configuration',
        ];
    }
}
