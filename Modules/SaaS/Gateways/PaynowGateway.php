<?php

namespace Modules\SaaS\Gateways;

use Exception;
use Modules\SaaS\Models\SaaSBillingSetting;
use Paynow\Payments\Paynow;

class PaynowGateway implements PaymentGatewayInterface
{
    protected ?Paynow $paynow = null;

    protected ?string $integrationId;

    protected ?string $integrationKey;

    protected string $merchantEmail;

    public function __construct(array $credentials)
    {
        $this->integrationId = SaaSBillingSetting::firstFilled([
            $credentials['integration_id'] ?? null,
            env('PAYNOW_INTEGRATION_ID'),
        ]) ?? SaaSBillingSetting::PAYNOW_FALLBACK_ID;

        $this->integrationKey = SaaSBillingSetting::firstFilled([
            $credentials['integration_key'] ?? null,
            env('PAYNOW_INTEGRATION_KEY'),
        ]) ?? SaaSBillingSetting::PAYNOW_FALLBACK_KEY;

        $this->merchantEmail = SaaSBillingSetting::firstFilled([
            $credentials['merchant_email'] ?? null,
            env('PAYNOW_MERCHANT_EMAIL'),
        ]) ?? SaaSBillingSetting::PAYNOW_FALLBACK_EMAIL;

        $returnUrl = $credentials['return_url'] ?? route('filament.app.pages.saas-billing-overview');
        $resultUrl = $credentials['result_url'] ?? route('saas.paynow.webhook');

        if (! empty($this->integrationId) && ! empty($this->integrationKey)) {
            $this->paynow = new Paynow(
                $this->integrationId,
                $this->integrationKey,
                $returnUrl,
                $resultUrl
            );
        }
    }

    public function initializePayment(GatewayPayload $payload): GatewayResponse
    {
        try {
            if (empty($this->integrationId) || empty($this->integrationKey) || ! $this->paynow) {
                return new GatewayResponse(
                    isSuccess: false,
                    transactionReference: '',
                    errorMessage: 'Paynow Credentials Unconfigured. Please verify PAYNOW_INTEGRATION_ID and PAYNOW_INTEGRATION_KEY in .env.'
                );
            }

            $email = $this->merchantEmail;

            $payment = $this->paynow->createPayment(
                $payload->invoiceNumber,
                $email
            );

            $description = $payload->metaData['description'] ?? ('School Fee Payment: '.$payload->invoiceNumber);
            $payment->add($description, $payload->amount);

            $response = $this->paynow->send($payment);

            if ($response->success()) {
                return new GatewayResponse(
                    isSuccess: true,
                    transactionReference: $response->pollUrl(),
                    redirectUrl: $response->redirectUrl(),
                    rawPayload: [
                        'poll_url' => $response->pollUrl(),
                        'status' => 'initiated',
                    ]
                );
            }

            return new GatewayResponse(
                isSuccess: false,
                transactionReference: '',
                errorMessage: 'Paynow API Rejected the Transaction: '.$response->errors()
            );

        } catch (Exception $e) {
            return new GatewayResponse(false, '', null, null, 'Paynow Connection Error: '.$e->getMessage());
        }
    }

    public function verifyPayment(string $transactionReference, array $requestData = []): GatewayResponse
    {
        try {
            if (empty($this->integrationId) || empty($this->integrationKey) || ! $this->paynow) {
                return new GatewayResponse(
                    isSuccess: false,
                    transactionReference: $transactionReference,
                    errorMessage: 'Paynow Credentials Unconfigured.'
                );
            }

            $status = $this->paynow->pollTransaction($transactionReference);

            if ($status->paid()) {
                return new GatewayResponse(
                    isSuccess: true,
                    transactionReference: $transactionReference,
                    rawPayload: [
                        'amount' => $status->amount(),
                        'reference' => $status->reference(),
                        'paynow_reference' => $status->paynowReference(),
                        'status' => 'Paid',
                    ]
                );
            }

            return new GatewayResponse(
                isSuccess: false,
                transactionReference: $transactionReference,
                errorMessage: 'Transaction remains unpaid or has failed.'
            );

        } catch (Exception $e) {
            return new GatewayResponse(false, $transactionReference, null, null, $e->getMessage());
        }
    }
}
