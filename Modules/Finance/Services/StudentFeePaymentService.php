<?php

namespace Modules\Finance\Services;

use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\StudentPaymentSubmission;

class StudentFeePaymentService
{
    /**
     * Credits an approved student payment submission against its invoice.
     */
    public static function creditSubmission(StudentPaymentSubmission $submission): bool
    {
        if ($submission->status !== StudentPaymentSubmission::STATUS_PENDING) {
            return false;
        }

        $invoice = $submission->invoice;
        if (! $invoice) {
            return false;
        }

        Payment::create([
            'school_id' => $submission->school_id,
            'invoice_id' => $invoice->id,
            'receipt_number' => 'RCP-'.strtoupper(substr(uniqid(), -6)),
            'reference_number' => $submission->reference_number ?? ('PAYNOW-'.$submission->id),
            'amount' => $submission->amount,
            'currency' => $submission->currency ?: 'USD',
            'payment_method' => $submission->gateway === 'paynow' ? 'Ecocash' : 'bank_transfer',
            'payment_date' => $submission->payment_date ?? now(),
        ]);

        $invoice->paid_amount = (float) $invoice->paid_amount + (float) $submission->amount;
        $invoice->save();

        $submission->forceFill([
            'status' => StudentPaymentSubmission::STATUS_APPROVED,
            'reviewed_at' => now(),
        ])->save();

        // Notify the student that their payment was credited
        self::notifyStudentPaymentApproved($submission);

        return true;
    }

    /**
     * Approves a manual bank deposit submission.
     */
    public static function approve(StudentPaymentSubmission $submission, ?int $reviewerId = null): bool
    {
        if ($submission->status !== StudentPaymentSubmission::STATUS_PENDING) {
            return false;
        }

        $credited = self::creditSubmission($submission);
        if ($credited) {
            $submission->forceFill(['reviewed_by_id' => $reviewerId])->save();
        }

        return $credited;
    }

    /**
     * Rejects a pending manual submission.
     */
    public static function reject(StudentPaymentSubmission $submission, string $reason, ?int $reviewerId = null): bool
    {
        if ($submission->status !== StudentPaymentSubmission::STATUS_PENDING) {
            return false;
        }

        $submission->forceFill([
            'status' => StudentPaymentSubmission::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'reviewed_by_id' => $reviewerId,
            'reviewed_at' => now(),
        ])->save();

        self::notifyStudentPaymentRejected($submission, $reason);

        return true;
    }

    /**
     * Notifies school admin/accounting users when a student submits a payment proof.
     */
    public static function notifySchoolOfNewSubmission(StudentPaymentSubmission $submission): void
    {
        $student = $submission->student;
        $schoolId = $submission->school_id;

        // Find users with admin or accountant roles in this school
        $recipients = User::where('school_id', $schoolId)
            ->where('account_status', User::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->where('requested_role', 'admin')
                    ->orWhere('requested_role', 'bursar')
                    ->orWhere('requested_role', 'accountant');
            })
            ->get();

        foreach ($recipients as $user) {
            FilamentNotification::make()
                ->title(__('New Payment Submission'))
                ->body(__(':student has submitted a payment of $:amount via :method.', [
                    'student' => $student?->full_name ?? __('A student'),
                    'amount' => number_format((float) $submission->amount, 2),
                    'method' => $submission->gateway === 'manual' ? __('Bank Deposit') : 'PayNow',
                ]))
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->sendToDatabase($user);
        }
    }

    /**
     * Notifies the student that their payment was approved.
     */
    protected static function notifyStudentPaymentApproved(StudentPaymentSubmission $submission): void
    {
        $student = $submission->student;
        if (! $student || ! $student->user_id) {
            return;
        }

        $user = User::find($student->user_id);
        if (! $user) {
            return;
        }

        FilamentNotification::make()
            ->title(__('Payment Approved'))
            ->body(__('Your payment of $:amount has been approved and credited to your account.', [
                'amount' => number_format((float) $submission->amount, 2),
            ]))
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->sendToDatabase($user);
    }

    /**
     * Notifies the student that their payment was rejected.
     */
    protected static function notifyStudentPaymentRejected(StudentPaymentSubmission $submission, string $reason): void
    {
        $student = $submission->student;
        if (! $student || ! $student->user_id) {
            return;
        }

        $user = User::find($student->user_id);
        if (! $user) {
            return;
        }

        FilamentNotification::make()
            ->title(__('Payment Rejected'))
            ->body(__('Your payment of $:amount was not approved. Reason: :reason', [
                'amount' => number_format((float) $submission->amount, 2),
                'reason' => $reason,
            ]))
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->sendToDatabase($user);
    }
}
