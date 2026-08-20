<?php

namespace App\Mail\SaaS;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\SaaS\Models\SaaSSubscription;

class SaaSDunningMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new mailable instance with typed parameter information.
     */
    public function __construct(
        public SaaSSubscription $subscription,
        public string $type,
        string $subject
    ) {
        $this->subject = $subject;
    }

    public function build(): self
    {
        return $this->view('modules.saas.emails.dunning')
            ->with([
                'schoolName' => $this->subscription->school->name,
                'nextDate' => $this->subscription->next_payment_date->format('M d, Y'),
                'amountDue' => number_format($this->subscription->getBillingAmount(), 2),
                'type' => $this->type,
            ]);
    }
}
