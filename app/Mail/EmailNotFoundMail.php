<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when someone asks for an activation link for an address that has no
 * matching account. Delivered to the address itself so the mailbox owner
 * learns what happened; the on-screen flow stays generic (no enumeration).
 */
class EmailNotFoundMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $submittedEmail,
    ) {}

    public function build(): self
    {
        $branding = email_branding(null);

        return $this->from(platform_noreply_email(), platform_email_name())
            ->replyTo(platform_support_email())
            ->subject(__('No account found for this email address'))
            ->view('emails.brand', brand_email_view_data([
                'logoUrl' => $branding['logo_url'],
                'companyName' => $branding['company_name'],
                'companyAddress' => $branding['company_address'],
                'companyPhone' => $branding['company_phone'],
                'companyEmail' => $branding['company_email'],
                'heading' => __('We could not find an account'),
                'greeting' => __('Hello,'),
                'introLines' => [
                    __('You recently asked us to send an activation link to <strong>:email</strong>, but no account on :platform is registered with that address.', ['email' => e($this->submittedEmail), 'platform' => e($branding['company_name'])]),
                    __('This usually means one of three things:'),
                    __('• The address was typed slightly differently when the account was created — try the spelling your school used.'),
                    __('• Your school registered a different contact for your account — try that address instead.'),
                    __('• No account was ever created for you — you can register your school below, or ask your administrator to invite you.'),
                ],
                'actionUrl' => route('register'),
                'actionText' => __('Register your school'),
                'outroLines' => [
                    __('If you believe this is a mistake, reply to this email or contact our support team and we will look into it for you.'),
                ],
                'signature' => __('The :name Team', ['name' => $branding['company_name']]),
                'footerNote' => __('You received this email because an activation was requested for :email on :platform.', ['email' => $this->submittedEmail, 'platform' => $branding['company_name']]),
            ]));
    }
}
