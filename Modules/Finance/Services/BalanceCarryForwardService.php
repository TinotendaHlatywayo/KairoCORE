<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\Term;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceItem;
use Modules\Finance\Models\Payment;

/**
 * Rolls the unpaid balances of a closed active term into the newly-activated
 * active term as a "Balance Brought Forward" (CF-) debit invoice.
 *
 * The money already owed does not disappear: each outstanding source invoice is
 * settled internally with a `credit` payment (mirroring PaymentSettlementService
 * credit semantics, with no bank movement and no touch of the student's
 * overpayment credit_balance), so it stops counting as outstanding, and the CF-
 * invoice — which the parent now pays — carries the balance forward to the next
 * term where the per-student statement labels it clearly.
 */
class BalanceCarryForwardService
{
    /**
     * @param  int[]|null  $previousTermIds  ids of the term(s) that were active
     *                                       before the switch
     * @return array{students:int, invoices_created:int, source_invoices_marked:int, amount_carried:float}
     */
    public static function carryForward(?array $previousTermIds, int $newTermId): array
    {
        $summary = [
            'students' => 0,
            'invoices_created' => 0,
            'source_invoices_marked' => 0,
            'amount_carried' => 0.0,
        ];

        $previousTermIds = collect($previousTermIds ?? [])->filter(fn ($id) => (int) $id > 0)->values();
        if ($previousTermIds->isEmpty()) {
            return $summary;
        }

        $newTerm = Term::withoutTenantScope()->find($newTermId);
        if (! $newTerm || ! $newTerm->school_id) {
            return $summary;
        }

        $schoolId = (int) $newTerm->school_id;
        $previousTerms = Term::withoutTenantScope()
            ->whereIn('id', $previousTermIds->all())
            ->pluck('name');

        $sources = Invoice::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->whereIn('term_id', $previousTermIds->all())
            ->whereNull('carried_forward_at')
            ->where('balance_amount', '>', 0)
            ->where('status', '!=', 'paid')
            ->where('status', '!=', 'void')
            ->with('student')
            ->orderBy('id')
            ->get();

        if ($sources->isEmpty()) {
            return $summary;
        }

        DB::transaction(function () use ($sources, $newTerm, $previousTerms, $schoolId, &$summary) {
            $associatedTermLabel = $previousTerms->isNotEmpty()
                ? $previousTerms->implode(', ')
                : __('the previous term');

            foreach ($sources->groupBy('student_id') as $studentId => $studentInvoices) {
                /** @var Collection<int, Invoice> $studentInvoices */
                $first = $studentInvoices->first();
                $student = $first?->student;

                if (! $student) {
                    continue;
                }

                $total = round((float) $studentInvoices->sum('balance_amount'), 2);
                if ($total <= 0) {
                    continue;
                }

                // Idempotency: at most one carried-forward invoice per student
                // per activated term.
                $alreadyCarried = Invoice::withoutTenantScope()
                    ->where('school_id', $schoolId)
                    ->where('student_id', $studentId)
                    ->where('term_id', $newTerm->id)
                    ->where('invoice_number', 'like', 'CF-%')
                    ->exists();

                if ($alreadyCarried) {
                    continue;
                }

                $cfSequence = Invoice::withoutTenantScope()
                    ->where('school_id', $schoolId)
                    ->where('invoice_number', 'like', 'CF-%')
                    ->count() + 1;
                $yearPart = $newTerm->start_date?->format('Y') ?: now()->format('Y');
                $cfNumber = 'CF-'.$yearPart.'-'.str_pad((string) $cfSequence, 5, '0', STR_PAD_LEFT);

                $billingDate = $newTerm->start_date?->copy()->startOfDay() ?: now()->startOfDay();

                $cfInvoice = new Invoice([
                    'school_id' => $schoolId,
                    'student_id' => $studentId,
                    'academic_year_id' => $newTerm->academic_year_id,
                    'term_id' => $newTerm->id,
                    'invoice_number' => $cfNumber,
                    'currency' => 'USD',
                    'subtotal_amount' => $total,
                    'discount_amount' => 0,
                    'waiver_details' => null,
                    'total_amount' => $total,
                    'paid_amount' => 0,
                    'balance_amount' => $total,
                    'status' => 'unpaid',
                    'due_date' => $newTerm->end_date ?: now()->addMonth()->toDateString(),
                ]);
                $cfInvoice->created_at = $billingDate;
                $cfInvoice->save();

                InvoiceItem::create([
                    'invoice_id' => $cfInvoice->id,
                    'fee_structure_id' => null,
                    'name' => __('Balance Brought Forward — :term', ['term' => $associatedTermLabel]),
                    'amount' => $total,
                ]);

                // Settle every source invoice internally with a credit so its
                // balance zeroes out and it stops counting as outstanding.
                foreach ($studentInvoices as $source) {
                    $sourceBalance = round((float) $source->balance_amount, 2);
                    if ($sourceBalance <= 0) {
                        continue;
                    }

                    Payment::create([
                        'school_id' => $schoolId,
                        'invoice_id' => $source->id,
                        'receipt_number' => 'CREDIT-'.mt_rand(10000, 99999),
                        'reference_number' => 'CARRY-FWD-'.$cfNumber,
                        'amount' => $sourceBalance,
                        'currency' => 'USD',
                        'payment_method' => 'credit',
                        'payment_date' => $billingDate->toDateString(),
                        'is_refund' => false,
                        'excess_handling' => PaymentSettlementService::MODE_CREDIT,
                    ]);

                    $source->paid_amount = round((float) $source->paid_amount + $sourceBalance, 2);
                    $source->carried_forward_at = now();
                    $source->carried_forward_to_invoice_id = $cfInvoice->id;
                    $source->save();

                    $summary['source_invoices_marked']++;
                }

                $summary['students']++;
                $summary['invoices_created']++;
                $summary['amount_carried'] = round($summary['amount_carried'] + $total, 2);
            }
        });

        return $summary;
    }
}
