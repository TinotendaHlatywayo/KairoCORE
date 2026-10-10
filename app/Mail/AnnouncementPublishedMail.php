<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/** Emails a published notice/announcement to its targeted users. */
class AnnouncementPublishedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $title,
        public string $content,
        public string $schoolName,
        public array $attachments = [],
    ) {}

    public function build(): self
    {
        $mail = $this->subject('New Notice: '.$this->title)->view('emails.announcement-published');

        foreach ($this->attachments as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                $mail->attach(Storage::disk('public')->path($path));
            }
        }

        return $mail;
    }
}
