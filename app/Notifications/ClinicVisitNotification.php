<?php

namespace App\Notifications;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Admin\Enums\EmailCategory;
use Modules\Admin\Services\TenantEmailConfigurationService;
use Modules\Clinic\Models\ClinicVisit;

class ClinicVisitNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ClinicVisit $visit) {}

    public function via($notifiable): array
    {
        $school = School::find($this->visit->school_id);
        if (! $school || ! app(TenantEmailConfigurationService::class)
            ->isUsable($school, EmailCategory::Communication)) {
            return [];
        }

        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Student Health & Wellness Update')
            ->line('Your child, '.$this->visit->student->first_name.' '.$this->visit->student->last_name.', was seen at the school clinic today.')
            ->line('Arrival Time: '.$this->visit->visit_time)
            ->line('Symptoms Reported: '.$this->visit->symptoms)
            ->line('Treatment Prescribed: '.($this->visit->treatment_given ?? 'Under Observation'))
            ->line('Discharge Status: '.ucfirst($this->visit->status))
            ->line('If you require additional medical details, please contact school health services.');

        $school = School::find($this->visit->school_id);
        if ($school) {
            return app(TenantEmailConfigurationService::class)
                ->configureNotificationMailMessage($message, EmailCategory::Communication, $school);
        }

        return $message;
    }
}
