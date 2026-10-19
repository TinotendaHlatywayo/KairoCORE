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
        $school = School::find($this->visit->school_id);

        $student = $this->visit->student->first_name.' '.$this->visit->student->last_name;

        $message = (new MailMessage)
            ->subject('Student Health & Wellness Update')
            ->view('emails.clinic-visit', [
                'school' => $school,
                'lines' => [
                    __('Your child, :student, was seen at the school clinic today.', ['student' => $student]),
                    __('Arrival Time: :time', ['time' => $this->visit->visit_time]),
                    __('Symptoms Reported: :symptoms', ['symptoms' => $this->visit->symptoms]),
                    __('Treatment Prescribed: :treatment', ['treatment' => $this->visit->treatment_given ?? __('Under Observation')]),
                    __('Discharge Status: :status', ['status' => ucfirst($this->visit->status)]),
                    __('If you require additional medical details, please contact school health services.'),
                ],
            ]);

        if ($school) {
            return app(TenantEmailConfigurationService::class)
                ->configureNotificationMailMessage($message, EmailCategory::Communication, $school);
        }

        return $message;
    }
}
