<?php

namespace App\Notifications;

use App\Filament\Admin\Resources\SchoolResource;
use App\Models\School;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerts the platform super administrators that a new school has applied and is
 * awaiting approval. Delivered both as an in-app database notification (visible
 * in the platform admin panel) and as an email.
 *
 * Never contains passwords or auto-generated credentials.
 */
class SchoolRegisteredNotification extends Notification
{
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
        $branding = email_branding(); // platform identity

        $workspaceUrl = $this->workspaceUrl();

        $rows = [
            __('Institution') => $this->school->name,
            __('Workspace') => $workspaceUrl !== null
                ? '<a href="'.e($workspaceUrl).'" style="color:#059669;text-decoration:none;">'.e((string) ($this->school->subdomain ?? '—')).'</a>'
                : ($this->school->subdomain ?? '—'),
            __('Country') => ($this->school->country ?? '—'),
            __('Address') => ($this->school->physical_address ?? '—'),
            __('Phone') => ($this->school->phone ?? '—'),
            __('Language') => strtoupper($this->school->language ?? '—'),
            __('Institution Type') => $this->school->other_institution_type
                ?: ($this->school->institution_type ?? '—'),
            __('Contact Person') => $this->contact->name,
            __('Contact Email') => $this->contact->email,
        ];

        return (new MailMessage)
            ->subject(__('New school registration on Kairo CORE: ').$this->school->name)
            ->view('emails.brand', brand_email_view_data([
                'logoUrl' => $branding['logo_url'],
                'companyName' => $branding['company_name'],
                'companyAddress' => $branding['company_address'],
                'companyPhone' => $branding['company_phone'],
                'companyEmail' => $branding['company_email'],
                'heading' => __('New school awaiting approval'),
                'greeting' => __('Hello :name,', ['name' => $notifiable->name ?? __('Platform Administrator')]),
                'introLines' => [
                    __('A new institution has just registered on Kairo CORE and is waiting for your review.'),
                    collect($rows)
                        ->map(fn ($v, $k) => '<strong>'.e($k).':</strong> '.$v)
                        ->implode('<br>'),
                ],
                'actionUrl' => $this->reviewUrl(),
                'actionText' => __('Review & approve institution'),
                'outroLines' => array_filter([
                    $workspaceUrl !== null
                        ? __('You can visit the institution workspace at: <a href=":url" style="color:#059669;text-decoration:none;">:url</a>.', ['url' => e($workspaceUrl)])
                        : null,
                    __('Approving the institution emails the contact an activation link so they can set up their administrator account.'),
                ]),
                'signature' => __('The ').platform_name().__(' Platform Team'),
                'footerNote' => __('You received this alert because you are a platform administrator.'),
            ]));
    }

    /**
     * The public workspace URL for the registered school's subdomain.
     */
    protected function workspaceUrl(): ?string
    {
        if (blank($this->school->subdomain)) {
            return null;
        }

        $host = parse_url(config('app.url'), PHP_URL_HOST);
        if (blank($host)) {
            return null;
        }

        $scheme = request()->secure() ? 'https' : (parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'https');

        return $scheme.'://'.$this->school->subdomain.'.'.$host;
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
            return SchoolResource::getUrl('edit', ['record' => $this->school], true, 'admin');
        } catch (\Throwable $e) {
            return route('marketing.home');
        }
    }
}
