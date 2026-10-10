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
        public array $attachmentPaths = [],
    ) {}

    public function build(): self
    {
        $mail = $this->subject('New Notice: '.$this->title)->view('emails.announcement-published');

        // Mailable owns `$attachments`; the parent class appends to it. Adding
        // our own typed `$attachments` would redeclare the inherited property
        // (a fatal error on PHP 8.4), so paths are held separately and handed
        // to the parent's attach() helper which does the bookkeeping.
        foreach ($this->attachmentPaths as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                $mail->attach(Storage::disk('public')->path($path));
            }
        }

        return $mail;
    }
}
