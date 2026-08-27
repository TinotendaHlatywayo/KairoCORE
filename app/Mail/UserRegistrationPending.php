<?php

namespace App\Mail;

use App\Models\School;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Notifies an authorized approver that a new account registration awaits review. */
class UserRegistrationPending extends Mailable
{
    public function __construct(
        public User $pendingUser,
        public string $recipientEmail,
        public string $schoolName,
        public ?School $school = null,
    ) {}

    public function build(): self
    {
        $branding = email_branding($this->school);

        return $this->to($this->recipientEmail)
            ->subject(__('New registration awaiting approval: ').$this->pendingUser->name)
            ->view('emails.brand', brand_email_view_data([
                'logoUrl' => $branding['logo_url'],
                'companyName' => $branding['company_name'],
                'companyAddress' => $branding['company_address'],
                'companyPhone' => $branding['company_phone'],
                'companyEmail' => $branding['company_email'],
                'heading' => __('New account registration awaiting approval'),
                'greeting' => __('Hello approver,'),
                'introLines' => [
                    __('A new member has just registered at <strong>:school</strong> and is waiting for approval.', ['school' => e($this->schoolName)]),
                    '<strong>'.__('Name').':</strong> '.e($this->pendingUser->name).
                    '<br><strong>'.__('Email').':</strong> '.e($this->pendingUser->email).
                    ($this->pendingUser->phone ? '<br><strong>'.__('Phone').':</strong> '.e($this->pendingUser->phone) : '').
                    '<br><strong>'.__('Requested role').':</strong> '.e($this->pendingUser->requestedRoleLabel() ?? __('Generic')).
                    '<br><strong>'.__('Registered').':</strong> '.e($this->pendingUser->created_at?->format('d M Y H:i') ?? now()->format('d M Y H:i')),
                    __('The account is locked until it is approved and activated.'),
                ],
                'outroLines' => [
                    __('Please review this registration in the workspace (Accounts & Users) so the new member can sign in.'),
                ],
                'signature' => __('The :name Team', ['name' => $this->schoolName]),
                'footerNote' => __('You received this email because you are authorised to approve account registrations.'),
            ]));
    }
}
