<?php

namespace App\Mail\SaaS;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Modules\SaaS\Models\SaaSReceipt;

/**
 * Receipt email used when a super-admin approves a manual payment with a
 * custom uploaded receipt. Mirrors SaaSReceiptMail but attaches the uploaded
 * file instead of the generated PDF.
 */
class SaaSCustomReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SaaSReceipt $receipt,
        public string $attachmentDisk,
        public string $attachmentPath,
        public string $attachmentName,
    ) {}

    public function build(): self
    {
        $locale = $this->receipt->school?->locale;
        if (in_array($locale, ['en', 'sn', 'sw', 'fr', 'pt', 'es'], true)) {
            app()->setLocale($locale);
        }

        $message = $this->subject('Payment Verified - '.platform_name().' Receipt: '.$this->receipt->receipt_number)
            ->from(platform_system_from_email(), platform_email_name())
            ->view('modules.saas.emails.receipt');

        try {
            $absolutePath = Storage::disk($this->attachmentDisk)->path($this->attachmentPath);
            if (is_file($absolutePath)) {
                $message->attach($absolutePath, ['as' => $this->attachmentName]);
            }
        } catch (\Throwable $e) {
            // Attach only when the stored file is still available.
        }

        return $message;
    }
}
