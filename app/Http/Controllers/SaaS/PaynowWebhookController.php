<?php

namespace App\Http\Controllers\SaaS;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\SaaS\Models\SaaSBillingSetting;
use Modules\SaaS\Models\SaaSInvoice;
use Modules\SaaS\Models\SaaSTransaction;
use Modules\SaaS\Services\SubscriptionManager;

class PaynowWebhookController extends Controller
{
    /**
     * Handles Paynow background webhook notifications securely.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        if (empty($payload) || ! isset($payload['reference'])) {
            return response('No payload received', 400);
        }

        Log::info('Incoming Paynow Billing Webhook Call: ', $payload);

        try {
            $settings = SaaSBillingSetting::getActiveSettings();
            $integrationKey = $settings->paynow_integration_key ?? env('PAYNOW_INTEGRATION_KEY', 'sample-key-uuid-9923');

            // Verify the authenticity of Paynow's background notification
            if (isset($payload['hash'])) {
                $receivedHash = $payload['hash'];

                // Reconstruct the expected hash using our self-contained helper
                $expectedHash = $this->generateHash($payload, $integrationKey);

                if ($receivedHash !== $expectedHash) {
                    Log::error('Paynow Security Mismatch: Signature hash does not match expected merchant key.');

                    return response('Security Signature Invalid', 400);
                }
            }

            $invoice = SaaSInvoice::where('invoice_number', $payload['reference'])->first();
            if (! $invoice) {
                Log::error("Paynow invoice context not found: {$payload['reference']}");

                return response('Invoice not found', 404);
            }

            $status = strtolower($payload['status'] ?? '');
            if ($status === 'paid' || $status === 'awaiting delivery') {

                // Check if this specific Paynow payment reference has already been credited
                $exists = SaaSTransaction::where('transaction_reference', $payload['paynowreference'])->exists();

                if (! $exists) {
                    $transaction = SaaSTransaction::create([
                        'school_id' => $invoice->school_id,
                        'saas_invoice_id' => $invoice->id,
                        'payment_gateway_key' => 'paynow',
                        'transaction_reference' => $payload['paynowreference'],
                        'amount' => $payload['amount'],
                        'currency' => $invoice->currency,
                        'status' => 'completed',
                        'processed_at' => now(),
                        'gateway_raw_response' => $payload,
                    ]);

                    app(SubscriptionManager::class)->processTransactionVerification($transaction);
                    Log::info("Successfully credited SaaS invoice: {$invoice->invoice_number}");
                }
            }

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('Failed to process Paynow webhook callback: '.$e->getMessage());

            return response('Internal Server Error', 500);
        }
    }

    /**
     * Self-contained, native SHA512 hashing algorithm matching Paynow's signature protocol.
     */
    private function generateHash(array $fields, string $key): string
    {
        // Ensure the hash signature itself is excluded from concatenation
        unset($fields['hash']);

        ksort($fields);
        $concat = '';
        foreach ($fields as $val) {
            $concat .= $val;
        }
        $concat .= $key;

        return strtoupper(hash('sha512', $concat));
    }
}
