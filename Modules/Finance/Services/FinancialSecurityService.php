<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;

class FinancialSecurityService
{
    /**
     * Log a secure audit trail entry
     */
    public function logTransaction($action, $model, ?array $before = null, ?array $after = null)
    {
        DB::table('finance_auditing_trails')->insert([
            'school_id' => $model->school_id ?? app('current_tenant')->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'payload_before' => $before ? json_encode($before) : null,
            'payload_after' => $after ? json_encode($after) : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Blocks transaction reference duplicates per school.
     */
    public function detectDuplicatePaymentReference($schoolId, $refNo)
    {
        if (empty($refNo)) {
            return false;
        }

        return Payment::where('school_id', $schoolId)
            ->where('reference_number', $refNo)
            ->where('is_reversed', false)
            ->exists();
    }

    /**
     * Safely reverses a payment and returns the invoice to its correct balance.
     */
    public function reversePayment(Payment $payment, $reason)
    {
        if ($payment->is_reversed) {
            throw new \Exception('This payment receipt has already been reversed.');
        }

        DB::transaction(function () use ($payment, $reason) {
            $invoice = $payment->invoice;

            if ($invoice->is_locked) {
                throw new \Exception('The associated invoice is locked for historical auditing. Reversal blocked.');
            }

            $before = $payment->toArray();

            // 1. Mark payment as reversed
            $payment->update([
                'is_reversed' => true,
                'reversal_reason' => $reason,
                'reversed_by_id' => Auth::id(),
            ]);

            // 2. Rollback the invoice totals
            $invoice->paid_amount = max(0, $invoice->paid_amount - $payment->amount);
            $invoice->balance_amount = $invoice->total_amount - $invoice->paid_amount;

            // Recalculate status
            if ($invoice->paid_amount <= 0) {
                $invoice->status = 'unpaid';
            } else {
                $invoice->status = 'partially_paid';
            }

            $invoice->save();

            // 3. Log the audit trail
            $this->logTransaction('payment_reversed', $payment, $before, $payment->toArray());
        });
    }

    /**
     * Locks an invoice from any further edits or payments.
     */
    public function lockInvoice(Invoice $invoice)
    {
        $before = $invoice->toArray();
        $invoice->update(['is_locked' => true]);
        $this->logTransaction('invoice_locked', $invoice, $before, $invoice->toArray());
    }
}
