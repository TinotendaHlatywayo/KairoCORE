<?php

namespace App\Notifications;

use App\Models\School;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Admin\Enums\EmailCategory;
use Modules\Admin\Services\TenantEmailConfigurationService;

class AccountActivationNotification extends Notification
{
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

        $branding = email_branding($school);

        // Send from the school's own mailbox whenever possible. A configured
        // tenant mailer (Communication category) wins; otherwise fall back to
        // the school's recorded contact address.
        $emailConfig = app(TenantEmailConfigurationService::class);

        $message = (new MailMessage)
            ->subject(__('Activate your '.$branding['company_name'].' account'))
            ->view('emails.brand', brand_email_view_data([
                'logoUrl' => $branding['logo_url'],
                'companyName' => $branding['company_name'],
                'companyAddress' => $branding['company_address'],
                'companyPhone' => $branding['company_phone'],
                'companyEmail' => $branding['company_email'],
                'heading' => __('You are one step away'),
                'greeting' => __('Hello :name,', ['name' => $this->user->name]),
                'introLines' => [
                    __('Your account at <strong>:school</strong> has been created and is ready to be activated.', ['school' => e($school->name ?: $branding['company_name'])]),
                    __('For security, every account starts locked. Set your username and a strong password using the button below — the secure link is valid for :hours hours and can only be used once.', ['hours' => config('auth.activation_token_ttl_hours', 48)]),
                    __('If you were not expecting this invitation, you can safely ignore this email.'),
                ],
                'actionUrl' => $activationUrl,
                'actionText' => __('Activate my account'),
                'outroLines' => [
                    __('Welcome aboard — we are glad to have you with us!'),
                ],
                'signature' => __('The :name Team', ['name' => $branding['company_name']]),
                'footerNote' => __('You received this email because an account was created for you at :school.', ['school' => $school->name ?: $branding['company_name']]),
            ]));

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
