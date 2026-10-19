<?php

namespace Modules\SaaS\Services;

use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\SaaS\Models\SaaSInvoice;
use Modules\SaaS\Models\SaaSInvoiceItem;
use Modules\SaaS\Models\SaaSSubscription;

class BillingService
{
    /**
     * Invoice numbers are unique at the database level. Two races can break
     * that: soft-deleted invoices (invisible to Eloquent but still holding
     * their number in the unique index) and two concurrent generates both
     * reading the same latest sequence. Allocate numbers with trashed rows
     * and a row lock, and retry once if the database still rejects us.
     */
    public function generateUpcomingInvoice(SaaSSubscription $subscription): SaaSInvoice
    {
        $attempts = 0;

        while (true) {
            try {
                return $this->allocateInvoice($subscription);
            } catch (QueryException $e) {
                // 1062 / 23000 = duplicate key (invoice_number race we lost).
                if ($attempts++ < 3 && ((int) ($e->errorInfo[1] ?? 0)) === 1062) {
                    continue;
                }

                throw $e;
            }
        }
    }

    private function allocateInvoice(SaaSSubscription $subscription): SaaSInvoice
    {
        return DB::transaction(function () use ($subscription) {
            $existingUnpaid = SaaSInvoice::where('school_id', $subscription->school_id)
                ->where('status', 'unpaid')
                ->first();

            if ($existingUnpaid) {
                return $existingUnpaid;
            }

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
            $dueDate = $subscription->next_payment_date
                ? Carbon::parse($subscription->next_payment_date)->startOfDay()
                : $periodStart->copy();

            // Invoice numbers must be globally unique (unique index), so the
            // tenant id and year are embedded and the per-tenant sequence is
            // read from the latest invoice INCLUDING trashed ones (their
            // numbers are still taken in the index). The row lock serialises
            // concurrent generators on the same school.
            $latestInvoice = SaaSInvoice::withTrashed()
                ->where('school_id', $subscription->school_id)
                ->orderBy('id', 'DESC')
                ->lockForUpdate()
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
            } while (SaaSInvoice::withTrashed()->where('invoice_number', $invoiceNumber)->exists());

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
