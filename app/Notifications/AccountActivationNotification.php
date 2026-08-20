<?php

namespace App\Notifications;

use App\Models\School;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Admin\Enums\EmailCategory;
use Modules\Admin\Services\TenantEmailConfigurationService;

class AccountActivationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected School $school;

    protected User $user;

    protected string $token;

    public function __construct(School $school, User $user, string $token)
    {
        $this->school = $school;
        $this->user = $user;
        $this->token = $token;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $activationUrl = route('account.activate', ['token' => $this->token]);

        $school = $this->school;

        $message = (new MailMessage)
            ->subject(__('Activate Your '.($school->name ?: 'SchoolCore').' Account'))
            ->greeting(__('Hello '.$this->user->name.','))
            ->line(__('Your registration at **'.$school->name.'** is ready to be activated.'))
            ->line(__('To complete setting up your account, please click the secure activation link below to choose your username and secure password:'))
            ->action(__('Activate Account Now'), $activationUrl)
            ->line(__('This activation link is valid for :hours hours and can only be used once.', ['hours' => config('auth.activation_token_ttl_hours', 48)]));

        if (filled($school->phone_number)) {
            $message->line(__('School contact: ').$school->phone_number);
        }

        if (filled($school->physical_address)) {
            $message->line(__('School address: ').$school->physical_address);
        }

        $message->line(__('Thank you for joining us!'));

        // Send from the school's own mailbox whenever possible. A configured
        // tenant mailer (Communication category) wins; otherwise fall back to
        // the school's recorded contact address.
        $emailConfig = app(TenantEmailConfigurationService::class);

        if ($school->email_address) {
            $message->from($school->email_address, $school->name ?: null);
        }

        return $emailConfig->configureNotificationMailMessage(
            $message,
            EmailCategory::Communication,
            $school,
        );
    }
}
