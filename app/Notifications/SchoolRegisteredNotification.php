<?php

namespace App\Notifications;

use App\Filament\Admin\Resources\SchoolResource;
use App\Models\School;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerts the platform super administrators that a new school has applied and is
 * awaiting approval. Delivered both as an in-app database notification (visible
 * in the platform admin panel) and as an email.
 *
 * Never contains passwords or auto-generated credentials.
 */
class SchoolRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public School $school,
        public User $contact,
        public bool $sendMail = true,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->sendMail ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('New School Registration: ').$this->school->name)
            ->greeting(__('Hello '.($notifiable->name ?? 'Platform Administrator').','))
            ->line(__('A new institution has registered on SchoolCore and is awaiting approval.'))
            ->line(__('Institution: ').$this->school->name)
            ->line(__('Subdomain: ').($this->school->subdomain ?? '—'))
            ->line(__('Country: ').($this->school->country ?? '—'))
            ->line(__('Physical Address: ').($this->school->physical_address ?? '—'))
            ->line(__('Phone: ').($this->school->phone ?? '—'))
            ->line(__('Language: ').($this->school->language ?? '—'))
            ->line(__('Institution Type: ').($this->school->institution_type ?? '—'))
            ->when($this->school->other_institution_type, fn (MailMessage $m) => $m->line(__('Other Institution Type: ').$this->school->other_institution_type))
            ->line(__('Contact Person: ').$this->contact->name)
            ->line(__('Contact Email: ').$this->contact->email)
            ->action(__('Review & Approve Institution'), $this->reviewUrl())
            ->line(__('Approving the institution emails an activation link to the contact so they can set up their administrator account.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'format' => 'approval',
            'action_required' => true,
            'school_id' => $this->school->id,
            'school_name' => $this->school->name,
            'subdomain' => $this->school->subdomain,
            'country' => $this->school->country,
            'address' => $this->school->physical_address,
            'phone' => $this->school->phone,
            'language' => $this->school->language,
            'institution_type' => $this->school->institution_type,
            'contact_name' => $this->contact->name,
            'contact_email' => $this->contact->email,
            'url' => $this->reviewUrl(),
        ];
    }

    protected function reviewUrl(): string
    {
        try {
            return SchoolResource::getUrl('edit', ['record' => $this->school]);
        } catch (\Throwable $e) {
            return route('marketing.home');
        }
    }
}
