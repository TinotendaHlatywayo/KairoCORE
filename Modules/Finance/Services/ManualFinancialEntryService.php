<?php

namespace Modules\Finance\Services;

use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Term;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Students\Models\Student;

/**
 * Point-and-click entry, correction and reversal of a student's financial
 * history from the ledger page. Every handler builds the same normalised row
 * that the file importer uses and pushes it through the shared persistence
 * handlers, so the money rules, ledger labels and finance audit trail behave
 * identically whether a record came from an Excel upload or a form.
 *
 * Audit actions are prefixed "manual." so the source of each entry stays
 * distinguishable from bulk imports ("import.").
 */
class ManualFinancialEntryService
{
    /**
     * Build a normalised row from form input (mirrors the file importer's
     * validateAndNormalize output) for any transaction type.
     *
     * @return array<string, mixed>
     */
    public static function row(Student $student, string $type, array $input): array
    {
        $schoolId = (int) $student->school_id;

        $invoiceNumber = trim((string) ($input['invoice_number'] ?? ''));
        if ($invoiceNumber === '' && filled($input['invoice_id'] ?? null)) {
            $invoiceNumber = (string) (Invoice::withoutTenantScope()
                ->where('student_id', $student->id)
                ->where('id', (int) $input['invoice_id'])
                ->value('invoice_number') ?? '');
        }

        return [
            'student_id' => $student->student_id_number,
            'type' => $type,
            'date' => $input['date'] ?? now()->toDateString(),
            'description' => trim((string) ($input['description'] ?? '')),
            'amount' => (float) ($input['amount'] ?? 0),
            'receipt_number' => trim((string) ($input['receipt_number'] ?? '')),
            'reference_number' => trim((string) ($input['reference_number'] ?? '')),
            'payment_method' => strtolower(trim((string) ($input['payment_method'] ?? ''))),
            'invoice_number' => $invoiceNumber,
            'academic_year' => '',
            'term' => '',
            'notes' => trim((string) ($input['notes'] ?? '')),
            '_student' => $student,
            '_academic_year' => self::academicYear($schoolId, $input['academic_year_id'] ?? null),
            '_term' => self::term($input['term_id'] ?? null),
        ];
    }

    public static function persist(Student $student, array $row, ?int $requesterId = null): void
    {
        FinancialHistoryCsvService::persistRow(
            $row,
            (int) $student->school_id,
            $requesterId ?? auth()->id(),
            'manual'
        );
    }

    /**
     * Reverse a payment, refund or carry-forward credit. Undoes the money
     * movement (invoice paid amount + school bank balance for real cash,
     * student credit balance for carry-forward credits) and flags the row as
     * reversed so the ledger and reports stop counting it.
     */
    public static function reversePayment(Student $student, int $paymentId, ?string $notes = null): void
    {
        $payment = Payment::withoutTenantScope()
            ->where('school_id', $student->school_id)
            ->find($paymentId);

        if (! $payment) {
            throw new \RuntimeException('Payment could not be found.');
        }

        if ($payment->is_reversed) {
            throw new \RuntimeException('This payment has already been reversed.');
        }

        $invoice = $payment->invoice;
        $amount = (float) $payment->amount;
        $abs = abs($amount);
        $isRefund = (bool) $payment->is_refund;
        $isCredit = (! $isRefund) && $payment->payment_method === 'credit';

        if ($invoice) {
            // A payment/credit added to paid_amount; a refund subtracted from it.
            $invoice->paid_amount = round(max(0, (float) $invoice->paid_amount - ($isRefund ? -$abs : $abs)), 2);
            $invoice->save();
        }

        $bank = FinancialHistoryCsvService::defaultBank((int) $student->school_id);
        if ($bank && $abs > 0 && ! $isCredit) {
            // Real cash in the bank: payments put it in, refunds took it out.
            // Reversals do the opposite of the original movement.
            if ($isRefund) {
                $bank->increment('balance', $abs);
            } else {
                $bank->decrement('balance', $abs);
            }
        }

        if ($isCredit) {
            // The part that was parked on the student credit balance is clawed
            // back: applied portion was credited to the invoice, the remainder
            // sits on credit_balance. Reversal undoes both.
            $student->decrement('credit_balance', max(0, $amount));
            $student->refresh();
            $student->credit_balance = max(0, (float) $student->credit_balance);
            $student->save();
        }

        $payment->is_reversed = true;
        $payment->save();

        FinanceAuditService::record(
            $student->id,
            'manual.reverse_payment',
            $payment,
            ['amount' => $amount, 'invoice_number' => $invoice?->invoice_number, 'method' => $payment->payment_method, 'is_refund' => $isRefund],
            ['is_reversed' => true],
            $notes ?: null
        );
    }

    /**
     * Remove a waiver by zeroing the invoice's discount. The invoice itself
     * (and any payments) are left untouched.
     */
    public static function reverseWaiver(Student $student, int $invoiceId, ?string $notes = null): void
    {
        $invoice = Invoice::withoutTenantScope()
            ->where('student_id', $student->id)
            ->find($invoiceId);

        if (! $invoice) {
            throw new \RuntimeException('Invoice could not be found.');
        }

        $before = FinancialHistoryCsvService::auditSlice($invoice->refresh()->toArray());

        $invoice->discount_amount = 0;
        $invoice->waiver_details = null;
        $invoice->save();

        FinanceAuditService::record(
            $student->id,
            'manual.remove_waiver',
            $invoice,
            $before,
            FinancialHistoryCsvService::auditSlice($invoice->refresh()->toArray()),
            $notes ?: null
        );
    }

    /**
     * Delete an invoice that has no live payments. Reversed payments are
     * tolerated; any active payment blocks deletion so the ledger never
     * drops money it already counted. The invoice's line items cascade.
     */
    public static function deleteInvoice(Student $student, int $invoiceId, ?string $notes = null): void
    {
        $invoice = Invoice::withoutTenantScope()
            ->where('student_id', $student->id)
            ->find($invoiceId);

        if (! $invoice) {
            throw new \RuntimeException('Invoice could not be found.');
        }

        $hasActivePayments = Payment::withoutTenantScope()
            ->where('invoice_id', $invoice->id)
            ->where('is_reversed', false)
            ->exists();

        if ($hasActivePayments) {
            throw new \RuntimeException('This invoice already has recorded payments and cannot be deleted. Reverse the payments first, then re-record or edit the invoice.');
        }

        $before = FinancialHistoryCsvService::auditSlice($invoice->refresh()->toArray());

        $invoice->items()->delete();
        $invoice->delete();

        FinanceAuditService::record(
            $student->id,
            'manual.delete_invoice',
            null,
            $before,
            null,
            $notes ?: null
        );
    }

    /**
     * Update the descriptive fields of an existing invoice (date, description
     * label, notes, academic year/term) without re-touching amounts once money
     * has moved. Amount edits are only honoured on invoices with no payments
     * and no discount, where they cannot corrupt the ledger.
     */
    public static function updateInvoice(Student $student, int $invoiceId, array $input, ?string $notes = null): void
    {
        $invoice = Invoice::withoutTenantScope()
            ->where('student_id', $student->id)
            ->find($invoiceId);

        if (! $invoice) {
            throw new \RuntimeException('Invoice could not be found.');
        }

        $before = FinancialHistoryCsvService::auditSlice($invoice->refresh()->toArray());
        $before['created_at'] = $invoice->created_at?->toDateTimeString();
        $before['description'] = $invoice->items()->first()?->name;

        $hasMoney = Payment::withoutTenantScope()->where('invoice_id', $invoice->id)->where('is_reversed', false)->exists()
            || (float) $invoice->discount_amount > 0
            || (float) $invoice->paid_amount > 0;

        if (array_key_exists('date', $input) && filled($input['date'])) {
            $invoice->created_at = \Carbon\Carbon::parse($input['date']);
            $invoice->due_date = \Carbon\Carbon::parse($input['date']);
        }

        if (filled($input['description'] ?? null)) {
            $item = $invoice->items()->first();
            if ($item) {
                $item->name = trim($input['description']);
                $item->save();
            } else {
                $invoice->items()->create([
                    'fee_structure_id' => null,
                    'name' => trim($input['description']),
                    'amount' => (float) ($input['amount'] ?? $invoice->subtotal_amount) ?: (float) $invoice->subtotal_amount,
                ]);
            }
        }

        if (! $hasMoney && array_key_exists('amount', $input) && filled($input['amount'])) {
            $newAmount = round((float) $input['amount'], 2);
            $invoice->subtotal_amount = $newAmount;
            $invoice->total_amount = $newAmount;
            $invoice->balance_amount = $newAmount;

            $item = $invoice->items()->first();
            if ($item) {
                $item->amount = $newAmount;
                $item->save();
            }
        }

        $invoice->save();

        FinanceAuditService::record(
            $student->id,
            'manual.edit_invoice',
            $invoice,
            $before,
            array_merge(
                FinancialHistoryCsvService::auditSlice($invoice->refresh()->toArray()),
                ['created_at' => $invoice->created_at?->toDateTimeString(), 'description' => $invoice->items()->first()?->name]
            ),
            $notes ?: null
        );
    }

    protected static function academicYear(int $schoolId, ?int $yearId): ?AcademicYear
    {
        if ($yearId) {
            return AcademicYear::withoutTenantScope()->where('school_id', $schoolId)->find($yearId);
        }

        return AcademicYear::withoutTenantScope()->where('school_id', $schoolId)->where('is_active', true)->first();
    }

    protected static function term(?int $termId): ?Term
    {
        return $termId ? Term::withoutTenantScope()->find($termId) : null;
    }
}