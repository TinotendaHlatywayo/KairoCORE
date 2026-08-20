<?php

namespace Modules\SaaS\Gateways;

interface PaymentGatewayInterface
{
    public function initializePayment(GatewayPayload $payload): GatewayResponse;

    public function verifyPayment(string $transactionReference, array $requestData): GatewayResponse;
}
