<?php

namespace Modules\SaaS\Gateways;

use Exception;
use Illuminate\Support\Facades\Http;

class PayPalGateway implements PaymentGatewayInterface
{
    protected string $clientId;

    protected string $clientSecret;

    protected string $baseUrl;

    public function __construct(array $credentials)
    {
        $this->clientId = $credentials['client_id'] ?? '';
        $this->clientSecret = $credentials['client_secret'] ?? '';
        $this->baseUrl = ($credentials['sandbox'] ?? true)
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    protected function getAccessToken(): string
    {
        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post("{$this->baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->failed()) {
            throw new Exception('PayPal Access Token Generation Failed');
        }

        return $response->json()['access_token'];
    }

    public function initializePayment(GatewayPayload $payload): GatewayResponse
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)->post("{$this->baseUrl}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id' => $payload->invoiceNumber,
                    'amount' => [
                        'currency_code' => strtoupper($payload->currency),
                        'value' => number_format($payload->amount, 2, '.', ''),
                    ],
                ]],
                'application_context' => [
                    'return_url' => $payload->successUrl,
                    'cancel_url' => $payload->cancelUrl,
                ],
            ]);

            if ($response->failed()) {
                return new GatewayResponse(false, '', null, null, 'PayPal order initialization failed');
            }

            $order = $response->json();
            $redirectUrl = collect($order['links'])->firstWhere('rel', 'approve')['href'] ?? null;

            return new GatewayResponse(
                isSuccess: true,
                transactionReference: $order['id'],
                redirectUrl: $redirectUrl,
                rawPayload: $order
            );
        } catch (Exception $e) {
            return new GatewayResponse(false, '', null, null, $e->getMessage());
        }
    }

    public function verifyPayment(string $transactionReference, array $requestData): GatewayResponse
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)->post("{$this->baseUrl}/v2/checkout/orders/{$transactionReference}/capture");

            if ($response->failed()) {
                return new GatewayResponse(false, $transactionReference, null, null, 'PayPal capture execution failed');
            }

            $capture = $response->json();

            if ($capture['status'] === 'COMPLETED') {
                return new GatewayResponse(true, $transactionReference, null, $capture);
            }

            return new GatewayResponse(false, $transactionReference, null, $capture, 'Capture status not complete');
        } catch (Exception $e) {
            return new GatewayResponse(false, $transactionReference, null, null, $e->getMessage());
        }
    }
}
