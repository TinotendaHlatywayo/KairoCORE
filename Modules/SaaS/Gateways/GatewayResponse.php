<?php

namespace Modules\SaaS\Gateways;

class GatewayResponse
{
    public function __construct(
        public readonly bool $isSuccess,
        public readonly string $transactionReference,
        public readonly ?string $redirectUrl = null,
        public readonly ?array $rawPayload = null,
        public readonly ?string $errorMessage = null
    ) {}
}
