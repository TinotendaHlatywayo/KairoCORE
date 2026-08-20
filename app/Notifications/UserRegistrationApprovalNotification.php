<?php

namespace App\Notifications;

use App\Filament\App\Resources\UserAccountResource;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Informs every authorized approver that a new user account is awaiting
 * review. Rendered with a distinct "Action Required" presentation in the
 * unified top-bar notification center.
 */
class UserRegistrationApprovalNotification extends Notification
{
    use Queueable;

    public function __construct(public User $user) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format' => 'approval',
            'action_required' => true,
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'user_email' => $this->user->email,
            'requested_role' => $this->user->requested_role,
            'requested_role_label' => $this->user->requestedRoleLabel() ?? 'Generic',
            'url' => UserAccountResource::getUrl('edit', ['record' => $this->user]),
        ];
    }
}
