<?php

namespace Modules\SaaS\Gateways;

use Exception;
use Paynow\Payments\Paynow;

class PaynowGateway implements PaymentGatewayInterface
{
    protected ?Paynow $paynow = null;

    protected ?string $integrationId;

    protected ?string $integrationKey;

    public function __construct(array $credentials)
    {
        $this->integrationId = $credentials['integration_id'] ?? env('PAYNOW_INTEGRATION_ID', '25965');
        if (empty($this->integrationId) || $this->integrationId === '12345') {
            $this->integrationId = env('PAYNOW_INTEGRATION_ID', '25965');
        }

        $this->integrationKey = $credentials['integration_key'] ?? env('PAYNOW_INTEGRATION_KEY', '669ac21f-1216-40b0-9623-91c489caca35');
        if (empty($this->integrationKey)) {
            $this->integrationKey = env('PAYNOW_INTEGRATION_KEY', '669ac21f-1216-40b0-9623-91c489caca35');
        }

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

            $email = env('PAYNOW_MERCHANT_EMAIL', 'twaynehlatywayo09@gmail.com');

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
