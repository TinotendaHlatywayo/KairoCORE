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
        $pdf = Pdf::loadView('modules.saas.pdf.receipt', ['receipt' => $this->receipt]);

        return $this->subject('Payment Verified - SchoolCore SaaS Receipt: '.$this->receipt->receipt_number)
            ->view('modules.saas.emails.receipt')
            ->attachData($pdf->output(), $this->receipt->receipt_number.'.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
