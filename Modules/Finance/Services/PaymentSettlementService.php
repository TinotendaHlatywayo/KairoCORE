<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\SchoolBankAccount;

/**
 * Central money-in handler. Credits an invoice, decides what happens to any
 * overpayment (refund the excess, or carry it forward as a student credit for
 * the next term) and moves the cash into a school bank account.
 */
class PaymentSettlementService
{
    public const MODE_REFUND = 'refund';
    public const MODE_CREDIT = 'credit';

    /**
     * @param  float  $amount  amount in USD
     * @param  string  $handling  'refund' | 'credit' (ignored when no overpayment)
     * @return array{applied: float, excess: float, handling: ?string, refunded: float, credited: float}
     */
    public static function settle(Invoice $invoice, float $amount, array $attributes, ?string $handling = null, ?int $bankAccountId = null): array
    {
        $balance = max(0, (float) $invoice->balance_amount);
        $amount = round(max(0, $amount), 2);
        $applied = round(min($amount, $balance), 2);
        $excess = round($amount - $applied, 2);

        $handling = $excess > 0
            ? ($handling === self::MODE_CREDIT ? self::MODE_CREDIT : self::MODE_REFUND)
            : null;

        $refunded = 0.00;
        $credited = 0.00;

        DB::transaction(function () use ($invoice, $amount, $applied, $excess, $handling, $attributes, $bankAccountId, &$refunded, &$credited) {
            // 1. Credit the invoice with the portion it can absorb.
            if ($applied > 0) {
                Payment::create([
                    'school_id' => $invoice->school_id,
                    'bank_account_id' => $bankAccountId,
                    'invoice_id' => $invoice->id,
                    'receipt_number' => $attributes['receipt_number'] ?? 'RCP-'.mt_rand(10000, 99999),
                    'reference_number' => $attributes['reference_number'] ?? null,
                    'amount' => $applied,
                    'currency' => $attributes['currency'] ?? 'USD',
                    'payment_method' => $attributes['payment_method'] ?? 'cash',
                    'payment_date' => $attributes['payment_date'] ?? now(),
                    'is_refund' => false,
                    'excess_handling' => $handling,
                ]);

                $invoice->paid_amount = round((float) $invoice->paid_amount + $applied, 2);
                $invoice->save();
            }

            // 2. Resolve the bank account and move the real cash in.
            $bankAccount = $bankAccountId
                ? SchoolBankAccount::find($bankAccountId)
                : self::defaultBankAccount($invoice->school_id);

            if ($bankAccount) {
                // Refund: the school only keeps what it can absorb.
                // Credit: the school keeps the full amount and the student owes less next term.
                $bankIncrease = $handling === self::MODE_CREDIT ? $amount : $applied;

                if ($bankIncrease > 0) {
                    $bankAccount->increment('balance', $bankIncrease);
                }
            }

            // 3. Handle the overpayment.
            if ($excess > 0 && $handling === self::MODE_REFUND) {
                Payment::create([
                    'school_id' => $invoice->school_id,
                    'bank_account_id' => $bankAccount?->id,
                    'invoice_id' => $invoice->id,
                    'receipt_number' => 'REF-'.mt_rand(10000, 99999),
                    'reference_number' => ($attributes['reference_number'] ?? 'REFUND').'-REF',
                    'amount' => -$excess,
                    'currency' => 'USD',
                    'payment_method' => $attributes['payment_method'] ?? 'cash',
                    'payment_date' => now(),
                    'is_refund' => true,
                    'excess_handling' => self::MODE_REFUND,
                ]);

                if ($bankAccount) {
                    $bankAccount->decrement('balance', $excess);
                }

                $refunded = $excess;
            } elseif ($excess > 0 && $handling === self::MODE_CREDIT) {
                $student = $invoice->student;
                if ($student) {
                    $student->increment('credit_balance', $excess);
                }

                $credited = $excess;
            }
        });

        return [
            'applied' => $applied,
            'excess' => $excess,
            'handling' => $handling,
            'refunded' => $refunded,
            'credited' => $credited,
        ];
    }

    public static function defaultBankAccount(int $schoolId): ?SchoolBankAccount
    {
        return SchoolBankAccount::where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    /**
     * Apply a carried-forward student credit to an open invoice. The cash was
     * already deposited into the school bank account when the overpayment was
     * made, so no bank movement happens here — we simply reduce the receivable
     * and the student's remaining credit.
     *
     * @return float  the amount actually applied
     */
    public static function applyCredit(Invoice $invoice, float $credit): float
    {
        $applied = round(min(max(0, $credit), max(0, (float) $invoice->balance_amount)), 2);

        if ($applied <= 0) {
            return 0.0;
        }

        $student = $invoice->student;
        if (! $student) {
            return 0.0;
        }

        DB::transaction(function () use ($invoice, $student, $applied) {
            $payment = new Payment([
                'school_id' => $invoice->school_id,
                'invoice_id' => $invoice->id,
                'receipt_number' => 'CREDIT-'.mt_rand(10000, 99999),
                'reference_number' => 'CARRY-FWD-'.$invoice->invoice_number,
                'amount' => $applied,
                'currency' => 'USD',
                'payment_method' => 'credit',
                'payment_date' => now(),
                'is_refund' => false,
                'excess_handling' => self::MODE_CREDIT,
            ]);

            $payment->save();

            $invoice->paid_amount = round((float) $invoice->paid_amount + $applied, 2);
            $invoice->save();

            $student->decrement('credit_balance', $applied);
        });

        return $applied;
    }
}