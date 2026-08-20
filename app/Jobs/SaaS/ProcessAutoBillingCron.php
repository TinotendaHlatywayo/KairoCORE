<?php

namespace App\Jobs\SaaS;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Modules\SaaS\Gateways\GatewayPayload;
use Modules\SaaS\Models\SaaSSubscription;
use Modules\SaaS\Models\SaaSTransaction;
use Modules\SaaS\Services\BillingService;
use Modules\SaaS\Services\GatewayResolver;
use Modules\SaaS\Services\SubscriptionManager;

class ProcessAutoBillingCron implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = Carbon::today()->toDateString();

        // Query subscriptions with auto-billing enabled and a valid customer payment token
        $dueSubscriptions = SaaSSubscription::where('is_auto_billing_enabled', true)
            ->where('next_payment_date', '<=', $today)
            ->whereIn('status', ['active', 'grace_period'])
            ->whereNotNull('gateway_customer_token')
            ->get();

        foreach ($dueSubscriptions as $sub) {
            DB::transaction(function () use ($sub) {
                $billingService = app(BillingService::class);
                $invoice = $billingService->generateUpcomingInvoice($sub);

                try {
                    $resolver = app(GatewayResolver::class);
                    // Standard recurring processing (usually routed via Stripe or similar tokenized card processor)
                    $gateway = $resolver->resolve('stripe');

                    $payload = new GatewayPayload(
                        amount: $invoice->total,
                        currency: $invoice->currency,
                        invoiceNumber: $invoice->invoice_number,
                        successUrl: route('filament.app.pages.saas-billing-overview'),
                        cancelUrl: route('filament.app.pages.saas-billing-overview'),
                        metaData: ['subscription_id' => $sub->id]
                    );

                    // Execute payment initialization against token
                    $response = $gateway->initializePayment($payload);

                    if ($response->isSuccess) {
                        $transaction = SaaSTransaction::create([
                            'school_id' => $sub->school_id,
                            'saas_invoice_id' => $invoice->id,
                            'payment_gateway_key' => 'stripe',
                            'transaction_reference' => $response->transactionReference,
                            'amount' => $invoice->total,
                            'currency' => $invoice->currency,
                            'status' => 'completed',
                            'processed_at' => now(),
                        ]);

                        app(SubscriptionManager::class)->processTransactionVerification($transaction);
                    } else {
                        throw new \Exception('Auto-billing payment attempt returned a negative response.');
                    }
                } catch (\Exception $e) {
                    // Fail over to grace period states and trigger dunning alerts
                    $sub->update([
                        'status' => 'grace_period',
                        'grace_ends_at' => Carbon::now()->addDays($sub->plan->grace_days),
                    ]);
                }
            });
        }
    }
}
