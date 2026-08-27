<?php

namespace Modules\DigitalAssessment\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\DigitalAssessment\Models\GamificationBadge;
use Modules\DigitalAssessment\Models\LearnerBadge;

class BadgeEarnedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected LearnerBadge $learnerBadge,
        protected GamificationBadge $badge,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $school = \App\Models\School::find($this->learnerBadge->school_id);
        $branding = email_branding($school);

        return (new MailMessage)
            ->subject('You earned a badge: ' . $this->badge->name)
            ->view('emails.brand', brand_email_view_data([
                'logoUrl' => $branding['logo_url'],
                'companyName' => $branding['company_name'],
                'companyAddress' => $branding['company_address'],
                'companyPhone' => $branding['company_phone'],
                'companyEmail' => $branding['company_email'],
                'heading' => 'Badge Earned!',
                'greeting' => 'Hello ' . $notifiable->name . ',',
                'introLines' => [
                    'Congratulations! You have earned the <strong>' . e($this->badge->name) . '</strong> badge.',
                    e($this->badge->description),
                ],
                'actionUrl' => route('filament.student.pages.gamification-profile'),
                'actionText' => 'View Your Profile',
                'outroLines' => [
                    'Keep up the great work!',
                ],
                'signature' => 'The ' . $branding['company_name'] . ' Team',
            ]));
    }
}
