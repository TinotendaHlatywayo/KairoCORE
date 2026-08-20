<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Notifies an authorized approver that a new account registration awaits review. */
class UserRegistrationPending extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $pendingUser,
        public string $recipientEmail,
        public string $schoolName,
    ) {}

    public function build(): self
    {
        return $this->to($this->recipientEmail)
            ->subject('New Registration Awaiting Approval: '.$this->pendingUser->name)
            ->view('emails.user-registration-pending');
    }
}
