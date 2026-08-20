<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** A tenant contact enquiry delivered to that tenant's configured school email. */
class ContactFormMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $schoolEmail,
        public array $contact,
        public string $schoolName,
    ) {}

    public function build(): self
    {
        return $this->to($this->schoolEmail)
            ->replyTo($this->contact['email'], $this->contact['first_name'].' '.$this->contact['last_name'])
            ->subject('New website enquiry — '.$this->schoolName)
            ->view('emails.contact-form-message');
    }
}
