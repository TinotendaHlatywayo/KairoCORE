<?php

namespace App\Notifications;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Admin\Enums\EmailCategory;
use Modules\Admin\Services\TenantEmailConfigurationService;
use Modules\Hostels\Models\HostelOutPass;

class HostelOutPassOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public HostelOutPass $outPass) {}

    public function via($notifiable): array
    {
        $school = School::find($this->outPass->school_id);
        if (! $school || ! app(TenantEmailConfigurationService::class)
            ->isUsable($school, EmailCategory::Communication)) {
            return [];
        }

        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Boarding Exit OTP Authorization Request')
            ->line('An out-pass has been requested for your child: '.$this->outPass->student->first_name.' '.$this->outPass->student->last_name)
            ->line('Destination Reason: '.$this->outPass->reason)
            ->line('Planned Return: '.$this->outPass->expected_return)
            ->line('Please provide the validation code below to complete authorization:')
            ->line('Verification Code: **'.$this->outPass->parent_otp.'**')
            ->line('If you did not authorize this request, please contact school operations.');

        $school = School::find($this->outPass->school_id);
        if ($school) {
            return app(TenantEmailConfigurationService::class)
                ->configureNotificationMailMessage($message, EmailCategory::Communication, $school);
        }

        return $message;
    }
}
