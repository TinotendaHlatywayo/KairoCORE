<?php

namespace Modules\SaaS\Gateways;

class GatewayPayload
{
    public function __construct(
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $invoiceNumber,
        public readonly string $successUrl,
        public readonly string $cancelUrl,
        public readonly array $metaData = []
    ) {}
}
