<?php

namespace Modules\SaaS\Gateways;

use Exception;
use Illuminate\Support\Facades\Http;

class StripeGateway implements PaymentGatewayInterface
{
    protected string $secretKey;

    public function __construct(array $credentials)
    {
        $this->secretKey = $credentials['secret_key'] ?? '';
    }

    public function initializePayment(GatewayPayload $payload): GatewayResponse
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->asForm()->post('https://api.stripe.com/v1/checkout/sessions', [
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($payload->currency),
                        'product_data' => [
                            'name' => 'SaaS Subscription Invoice: '.$payload->invoiceNumber,
                        ],
                        'unit_amount' => (int) ($payload->amount * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $payload->successUrl.'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $payload->cancelUrl,
                'metadata' => $payload->metaData,
            ]);

            if ($response->failed()) {
                return new GatewayResponse(
                    isSuccess: false,
                    transactionReference: '',
                    errorMessage: $response->json()['error']['message'] ?? 'Stripe Initialization Error'
                );
            }

            $session = $response->json();

            return new GatewayResponse(
                isSuccess: true,
                transactionReference: $session['id'],
                redirectUrl: $session['url'],
                rawPayload: $session
            );
        } catch (Exception $e) {
            return new GatewayResponse(false, '', null, null, $e->getMessage());
        }
    }

    public function verifyPayment(string $transactionReference, array $requestData): GatewayResponse
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->secretKey,
            ])->get("https://api.stripe.com/v1/checkout/sessions/{$transactionReference}");

            if ($response->failed()) {
                return new GatewayResponse(false, $transactionReference, null, null, 'Verification failed');
            }

            $session = $response->json();

            if ($session['payment_status'] === 'paid') {
                return new GatewayResponse(
                    isSuccess: true,
                    transactionReference: $transactionReference,
                    rawPayload: $session
                );
            }

            return new GatewayResponse(false, $transactionReference, null, $session, 'Session unpaid');
        } catch (Exception $e) {
            return new GatewayResponse(false, $transactionReference, null, null, $e->getMessage());
        }
    }
}
