<?php

namespace Modules\SaaS\Services;

use App\Models\School;
use Exception;
use Modules\SaaS\Gateways\ManualGateway;
use Modules\SaaS\Gateways\PaymentGatewayInterface;
use Modules\SaaS\Gateways\PaynowGateway;
use Modules\SaaS\Gateways\PayPalGateway;
use Modules\SaaS\Models\SaaSBillingSetting;

class GatewayResolver
{
    public function resolve(string $gatewayKey, array $options = []): PaymentGatewayInterface
    {
        $settings = SaaSBillingSetting::getActiveSettings();

        return match ($gatewayKey) {
            'paynow' => new PaynowGateway(array_merge([
                'integration_id' => $settings->resolvedPaynowIntegrationId(),
                'integration_key' => $settings->resolvedPaynowIntegrationKey(),
                'merchant_email' => $settings->resolvedPaynowMerchantEmail(),
            ], $options)),
            'paypal' => new PayPalGateway([
                'client_id' => $settings->paypal_client_id,
                'client_secret' => $settings->paypal_client_secret,
                'sandbox' => $settings->paypal_sandbox_mode,
            ]),
            'manual_bank' => new ManualGateway,
            default => throw new Exception("The gateway driver key [{$gatewayKey}] is not supported on this platform.")
        };
    }

    public function getRecommendedGatewayForSchool(School $school): string
    {
        if (strtolower($school->region) === 'zimbabwe') {
            return 'paynow';
        }

        return 'paypal';
    }
}
