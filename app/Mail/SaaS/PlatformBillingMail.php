<?php

namespace App\Mail\SaaS;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Generic platform billing email. The body is authored by the super admin in
 * the billing message templates, with placeholders already resolved by the
 * BillingNotificationService, so this mailable just renders the plain text
 * inside the branded shell.
 */
class PlatformBillingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $mailBody,
        public string $schoolName = '',
    ) {}

    public function build(): self
    {
        return $this
            ->subject($this->mailSubject)
            ->view('modules.saas.emails.billing-notification')
            ->with([
                'body' => $this->mailBody,
                'schoolName' => $this->schoolName,
            ]);
    }
}
