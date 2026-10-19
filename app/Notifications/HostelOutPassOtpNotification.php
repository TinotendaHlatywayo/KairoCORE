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
        $school = School::find($this->outPass->school_id);

        $student = $this->outPass->student->first_name.' '.$this->outPass->student->last_name;

        $message = (new MailMessage)
            ->subject('Boarding Exit OTP Authorization Request')
            ->view('emails.hostel-outpass', [
                'school' => $school,
                'lines' => [
                    __('An out-pass has been requested for your child: :student', ['student' => $student]),
                    __('Destination Reason: :reason', ['reason' => $this->outPass->reason]),
                    __('Planned Return: :return', ['return' => $this->outPass->expected_return]),
                    __('Please provide the validation code below to complete authorization:'),
                ],
                'code' => $this->outPass->parent_otp,
                'closingLine' => __('If you did not authorize this request, please contact school operations.'),
            ]);

        if ($school) {
            return app(TenantEmailConfigurationService::class)
                ->configureNotificationMailMessage($message, EmailCategory::Communication, $school);
        }

        return $message;
    }
}
