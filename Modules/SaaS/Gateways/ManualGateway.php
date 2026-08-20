<?php

namespace Modules\SaaS\Gateways;

class ManualGateway implements PaymentGatewayInterface
{
    public function initializePayment(GatewayPayload $payload): GatewayResponse
    {
        return new GatewayResponse(
            isSuccess: true,
            transactionReference: 'MAN-'.strtoupper(uniqid()),
            redirectUrl: route('filament.app.pages.saas-billing-overview'),
            rawPayload: ['method' => 'manual']
        );
    }

    public function verifyPayment(string $transactionReference, array $requestData): GatewayResponse
    {
        return new GatewayResponse(
            isSuccess: false,
            transactionReference: $transactionReference,
            errorMessage: 'Manual payments must be validated by an administrator.'
        );
    }
}
