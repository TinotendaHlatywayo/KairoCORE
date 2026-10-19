<?php

namespace App\Mail;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\Students\Models\Student;

/** Admission confirmation delivered to the email address registered on the online application. */
class AdmissionConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Student $student,
        public string $recipientEmail,
        public string $emailSubject,
        public string $emailBody,
        public string $schoolName,
        public ?string $fromEmail = null,
        public ?string $activationUrl = null,
    ) {}

    public function build(): self
    {
        $mail = $this->to($this->recipientEmail)
            ->subject($this->emailSubject)
            ->view('emails.admission-confirmation', [
                'school' => School::find($this->student->school_id),
            ]);

        if (! empty($this->fromEmail)) {
            $mail->from($this->fromEmail, $this->schoolName);
        }

        return $mail;
    }
}
