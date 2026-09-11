<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\FeeWaiver;
use Modules\Finance\Models\Invoice;
use Modules\Students\Models\Student;

/**
 * Applies (or removes) a fee waiver across ALL of a student's open invoices,
 * so waivers set during enrolment or afterwards on the student directory
 * recalculate every open billing document — including total expected revenue.
 */
class FeeWaiverApplicationService
{
    public static function applyToStudent(Student $student, ?int $waiverId = null): void
    {
        if ($waiverId) {
            $student->waivers()->sync([$waiverId]);
            $waiver = FeeWaiver::find($waiverId);

            if ($waiver) {
                self::openInvoices($student)->each(fn (Invoice $v) => self::applyToInvoice($v, $waiver));
            }

            return;
        }

        $student->waivers()->detach();
        self::openInvoices($student)->each(fn (Invoice $v) => self::clearFromInvoice($v));
    }

    public static function applyToInvoice(Invoice $invoice, FeeWaiver $waiver): void
    {
        $subtotal = (float) $invoice->subtotal_amount;

        if ($waiver->type === 'percentage') {
            $discount = round($subtotal * ((float) $waiver->value / 100), 2);
            $details = "{$waiver->name} ({$waiver->value}% - \$".number_format($discount, 2).')';
        } else {
            $discount = min($subtotal, (float) $waiver->value);
            $details = "{$waiver->name} (Fixed - \$".number_format($discount, 2).')';
        }

        $invoice->update([
            'fee_waiver_id' => $waiver->id,
            'discount_amount' => $discount,
            'waiver_details' => $details,
        ]);
    }

    public static function clearFromInvoice(Invoice $invoice): void
    {
        $invoice->update([
            'fee_waiver_id' => null,
            'discount_amount' => 0.00,
            'waiver_details' => null,
        ]);
    }

    protected static function openInvoices(Student $student)
    {
        return Invoice::where('student_id', $student->id)
            ->where('status', '!=', 'paid')
            ->get();
    }
}