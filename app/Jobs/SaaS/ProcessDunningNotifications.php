<?php

namespace App\Jobs\SaaS;

use App\Mail\SaaS\SaaSDunningMail;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Modules\SaaS\Models\SaaSSubscription;

class ProcessDunningNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = Carbon::today();

        // Retrieve all subscriptions that are trialing, active, or in a grace period
        $subscriptions = SaaSSubscription::whereIn('status', ['active', 'trialing', 'grace_period'])->get();

        foreach ($subscriptions as $sub) {
            $nextPayDate = Carbon::parse($sub->next_payment_date);
            $daysDifference = $today->diffInDays($nextPayDate, false);

            // 5 Days Before Payment is Due
            if ($daysDifference === 5) {
                $this->sendEmail($sub, 'upcoming_5_days', 'Payment Reminder: 5 Days Remaining');

                continue;
            }

            // 2 Days Before Payment is Due
            if ($daysDifference === 2) {
                $this->sendEmail($sub, 'upcoming_2_days', 'Urgent Payment Reminder: 2 Days Remaining');

                continue;
            }

            // On the Due Date
            if ($daysDifference === 0) {
                $this->sendEmail($sub, 'due_today', 'Payment Due Today');

                continue;
            }

            // Overdue Dunning Cycle (Every day overdue until account deactivation occurs)
            if ($daysDifference < 0) {
                $this->sendEmail($sub, 'overdue_daily', 'Overdue Notice: Subscription Grace Period Active');
            }
        }
    }

    protected function sendEmail(SaaSSubscription $sub, string $type, string $subject): void
    {
        try {
            $recipient = $sub->school->email_address ?? 'billing@'.$sub->school->subdomain.'.schoolcore.test';
            Mail::to($recipient)->send(new SaaSDunningMail($sub, $type, $subject));
        } catch (\Exception $e) {
            // Log issues cleanly to helpdesk/debug logs
        }
    }
}
