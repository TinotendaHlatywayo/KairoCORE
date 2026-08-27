<?php

namespace App\Mail\SaaS;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\SaaS\Models\SaaSReceipt;

class SaaSReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public SaaSReceipt $receipt) {}

    public function build(): self
    {
        // Apply the customer school's locale so the receipt PDF renders in the
        // school's chosen language instead of the default 'en'.
        $locale = $this->receipt->school?->locale;
        if (in_array($locale, ['en', 'sn', 'sw', 'fr', 'pt', 'es'], true)) {
            app()->setLocale($locale);
        }

        $pdf = Pdf::loadView('modules.saas.pdf.receipt', ['receipt' => $this->receipt]);

        return $this->subject('Payment Verified - Kairo CORE SaaS Receipt: '.$this->receipt->receipt_number)
            ->view('modules.saas.emails.receipt')
            ->attachData($pdf->output(), $this->receipt->receipt_number.'.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
