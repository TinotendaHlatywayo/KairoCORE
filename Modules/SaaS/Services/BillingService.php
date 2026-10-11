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
            $months = $subscription->billingMonths();

            $unitPrice = $subscription->getBillingAmount();

            // The period the invoice covers is the tenant's current billing
            // month (its billing day in the present month), or the first billing
            // date while the tenant is still inside its free period.
            $anchor = $subscription->billing_start_date
                ? $subscription->billing_start_date->copy()->startOfDay()
                : Carbon::now()->startOfDay();

            $billingDay = $subscription->billingDay();
            $periodStart = Carbon::now()->startOfDay()->startOfMonth()->day($billingDay);

            if ($periodStart->lt($anchor)) {
                $periodStart = $anchor->copy();
            }

            $periodEnd = $periodStart->copy()->addMonthsNoOverflow($months)->subDay();
            $dueDate = $periodStart->copy();

            // Invoice numbers must be globally unique (there is a unique index
            // on the column), so the tenant id and year are embedded and the
            // per-tenant sequence is read back from its own latest invoice.
            $latestInvoice = SaaSInvoice::where('school_id', $subscription->school_id)
                ->orderBy('id', 'DESC')
                ->first();

            $nextSequence = 1;
            if ($latestInvoice) {
                $parts = explode('-', (string) $latestInvoice->invoice_number);
                $last = (int) end($parts);
                if ($last > 0) {
                    $nextSequence = $last + 1;
                }
            }

            do {
                $invoiceNumber = 'INV-SAAS-'.$subscription->school_id.'-'.Carbon::now()->year.'-'.str_pad((string) $nextSequence, 5, '0', STR_PAD_LEFT);
                $nextSequence++;
            } while (SaaSInvoice::where('invoice_number', $invoiceNumber)->exists());

            $invoice = SaaSInvoice::create([
                'school_id' => $subscription->school_id,
                'saas_subscription_id' => $subscription->id,
                'invoice_number' => $invoiceNumber,
                'issue_date' => Carbon::now()->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'months_covered' => $months,
                'subtotal' => $unitPrice,
                'discount' => 0.00,
                'tax_amount' => 0.00,
                'total' => $unitPrice,
                'currency' => $plan->currency ?? 'USD',
                'status' => 'unpaid',
                'is_locked' => false,
                'payment_instructions' => 'Payment for Kairo CORE Subscriptions on plan: '.($plan->name ?? ''),
            ]);

            SaaSInvoiceItem::create([
                'saas_invoice_id' => $invoice->id,
                'description' => __('Subscription for ').($plan->name ?? '').' ['.ucfirst((string) $billingPeriod).' Billing - '.$months.' month(s)]',
                'quantity' => $months,
                'unit_price' => $unitPrice / max(1, $months),
                'total' => $unitPrice,
            ]);

            return $invoice;
        });
    }

    /**
     * Raises the upcoming invoice for every billable subscription (one that has
     * reached its first billing date and is not cancelled), skipping any that
     * still have an open invoice so nothing is double-billed.
     *
     * @return array{processed:int,generated:int,skipped:int,failed:int}
     */
    public function generateInvoicesForAllSchools(?Carbon $onDate = null): array
    {
        $today = ($onDate ? $onDate->copy() : Carbon::today())->startOfDay();

        $summary = ['processed' => 0, 'generated' => 0, 'skipped' => 0, 'failed' => 0];

        $subscriptions = SaaSSubscription::query()
            ->with('plan')
            ->whereNotIn('status', ['cancelled'])
            ->whereNotNull('billing_start_date')
            ->whereDate('billing_start_date', '<=', $today)
            ->get();

        foreach ($subscriptions as $subscription) {
            $summary['processed']++;

            if (! $subscription->plan) {
                $summary['skipped']++;

                continue;
            }

            $hasOpenInvoice = SaaSInvoice::query()
                ->where('saas_subscription_id', $subscription->id)
                ->whereIn('status', ['unpaid', 'partially_paid'])
                ->exists();

            if ($hasOpenInvoice) {
                $summary['skipped']++;

                continue;
            }

            try {
                $this->generateUpcomingInvoice($subscription);
                $summary['generated']++;
            } catch (\Throwable $e) {
                $summary['failed']++;
                report($e);
            }
        }

        return $summary;
    }
}
