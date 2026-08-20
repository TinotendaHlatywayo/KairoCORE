<?php

namespace Modules\SaaS\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\SaaS\Models\SaaSInvoice;
use Modules\SaaS\Models\SaaSInvoiceItem;
use Modules\SaaS\Models\SaaSSubscription;

class BillingService
{
    public function generateUpcomingInvoice(SaaSSubscription $subscription): SaaSInvoice
    {
        return DB::transaction(function () use ($subscription) {
            $plan = $subscription->plan;
            $billingPeriod = $subscription->billing_period;

            $unitPrice = match ($billingPeriod) {
                'quarterly' => $plan->price_quarterly,
                'yearly' => $plan->price_yearly,
                default => $plan->price_monthly,
            };

            $latestInvoice = SaaSInvoice::where('school_id', $subscription->school_id)
                ->orderBy('id', 'DESC')
                ->first();

            $nextSequence = 1;
            if ($latestInvoice) {
                preg_match('/INV-SAAS-\d+-(\d+)/', $latestInvoice->invoice_number, $matches);
                if (isset($matches[1])) {
                    $nextSequence = ((int) $matches[1]) + 1;
                }
            }

            $invoiceNumber = 'INV-SAAS-'.Carbon::now()->year.'-'.str_pad((string) $nextSequence, 5, '0', STR_PAD_LEFT);

            $invoice = SaaSInvoice::create([
                'school_id' => $subscription->school_id,
                'saas_subscription_id' => $subscription->id,
                'invoice_number' => $invoiceNumber,
                'issue_date' => Carbon::now()->toDateString(),
                'due_date' => Carbon::now()->addDays(5)->toDateString(),
                'subtotal' => $unitPrice,
                'discount' => 0.00,
                'tax_amount' => 0.00,
                'total' => $unitPrice,
                'currency' => $plan->currency,
                'status' => 'unpaid',
                'is_locked' => false,
                'payment_instructions' => 'Payment for SchoolCore ERP Subscriptions on plan: '.$plan->name,
            ]);

            SaaSInvoiceItem::create([
                'saas_invoice_id' => $invoice->id,
                'description' => __('Subscription for ').$plan->name.' ['.ucfirst($billingPeriod).' Billing]',
                'quantity' => 1,
                'unit_price' => $unitPrice,
                'total' => $unitPrice,
            ]);

            return $invoice;
        });
    }
}
