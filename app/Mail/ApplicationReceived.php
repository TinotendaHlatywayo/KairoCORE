<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Admissions\Models\Application;

/** Notifies the admissions office that a new online application has been received. */
class ApplicationReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Application $application,
        public string $recipientEmail,
        public string $schoolName,
    ) {}

    public function build(): self
    {
        return $this->to($this->recipientEmail)
            ->subject('New Admission Application: '.$this->application->application_number)
            ->view('emails.application-received');
    }
}
