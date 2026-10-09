<?php

namespace App\Mail\SaaS;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Delivers a platform (super admin) message to a tenant by email, sent to the
 * address that registered the school. Used when the super admin picks the
 * "email" or "both" delivery channel on a platform communication.
 */
class PlatformMessageMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $messageBody,
        public string $schoolName,
    ) {}

    public function build(): self
    {
        return $this->subject($this->subjectLine)
            ->from(platform_system_from_email(), platform_email_name())
            ->view('modules.saas.emails.platform-message')
            ->with([
                'subjectLine' => $this->subjectLine,
                'messageBody' => $this->messageBody,
                'schoolName' => $this->schoolName,
            ]);
    }
}
