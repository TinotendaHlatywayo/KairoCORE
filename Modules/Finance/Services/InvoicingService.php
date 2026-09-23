<?php

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Academics\Models\Term;
use Modules\Finance\Models\FeeStructure;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceItem;
use Modules\Students\Models\Enrollment;

class InvoicingService
{
    /**
     * Highly flexible Billing Engine executing against multiple scopes
     * Scopes supported: 'school' (Whole School), 'form' (All Form 1s), 'stream' (Specific Form 1A)
     *
     * When billing frequency is 'monthly', the term total is split across the
     * calendar months within the term, producing one invoice per month with a
     * month-suffixed invoice number (e.g. INV-2026-00001-M03).
     *
     * @return array{
     *     generated: int,
     *     scanned: int,
     *     no_enrollment_match: int,
     *     already_billed: int,
     *     no_fee_structure: int,
     *     missing_data: int,
     * }
     */
    public function runInvoicingEngine($scope, $academicYearId, $termId, $dueDate, $courseId = null, $sectionId = null)
    {
        $schoolId = app('current_tenant')->id;
        $frequency = FinanceSettingsService::billingFrequency($schoolId);

        $result = [
            'generated' => 0,
            'scanned' => 0,
            'no_enrollment_match' => 0,
            'already_billed' => 0,
            'no_fee_structure' => 0,
            'missing_data' => 0,
        ];

        // Resolve term months for monthly billing
        $term = Term::withoutTenantScope()->find($termId);
        $monthlyBuckets = $frequency === 'monthly' && $term && $term->start_date && $term->end_date
            ? $this->resolveMonthlyBuckets($term)
            : [];

        // 1. Resolve student enrollments based on target scope
        $enrollmentQuery = Enrollment::where([
            'school_id' => $schoolId,
            'academic_year_id' => $academicYearId,
        ]);

        if ($scope === 'form') {
            $enrollmentQuery->where('course_id', $courseId);
        } elseif ($scope === 'stream') {
            $enrollmentQuery->where('section_id', $sectionId);
        }

        $enrollments = $enrollmentQuery->with(['student.waivers', 'course'])->get();

        DB::transaction(function () use ($enrollments, $academicYearId, $termId, $dueDate, $schoolId, $monthlyBuckets, $frequency, &$result) {
            foreach ($enrollments as $enrollment) {
                $result['scanned']++;

                $student = $enrollment->student;
                $course = $enrollment->course;

                if (! $student || ! $course) {
                    $result['missing_data']++;

                    continue;
                }

                // Prevent double-billing (termly: per year+term; monthly: per year+term+period)
                $alreadyBilled = $frequency === 'monthly'
                    ? $this->alreadyBilledMonthly($schoolId, $student->id, $academicYearId, $termId)
                    : $this->alreadyBilledTermly($schoolId, $student->id, $academicYearId, $termId);

                if ($alreadyBilled) {
                    $result['already_billed']++;

                    continue;
                }

                // Resolve applicable fee structures
                $feeStructures = $this->resolveFeeStructures($schoolId, $academicYearId, $termId, $course);

                if ($feeStructures->isEmpty()) {
                    $result['no_fee_structure']++;

                    continue;
                }

                // Compute term totals from fee structures
                $termSubtotal = 0;
                $structureLines = [];

                foreach ($feeStructures as $structure) {
                    $termSubtotal += (float) $structure->amount;
                    $structureLines[] = [
                        'fee_structure_id' => $structure->id,
                        'name' => $structure->feeCategory->name,
                        'amount' => (float) $structure->amount,
                    ];
                }

                // Apply individual waivers on the full term total
                $termDiscount = 0;
                $appliedWaiverId = null;
                $waiverDetailsString = null;

                $waiver = $student->waivers->first();
                if ($waiver) {
                    $appliedWaiverId = $waiver->id;
                    if ($waiver->type === 'percentage') {
                        $termDiscount = $termSubtotal * ($waiver->value / 100);
                        $waiverDetailsString = "{$waiver->name} ({$waiver->value}% - \$".number_format($termDiscount, 2).')';
                    } elseif ($waiver->type === 'fixed') {
                        $termDiscount = min((float) $waiver->value, $termSubtotal);
                        $waiverDetailsString = "{$waiver->name} (Fixed - \$".number_format($termDiscount, 2).')';
                    }
                }

                $termTotal = max(0, $termSubtotal - $termDiscount);

                if ($frequency === 'monthly' && count($monthlyBuckets) > 0) {
                    $this->createMonthlyInvoices(
                        $student,
                        $schoolId,
                        $academicYearId,
                        $termId,
                        $dueDate,
                        $appliedWaiverId,
                        $waiverDetailsString,
                        $termSubtotal,
                        $termDiscount,
                        $termTotal,
                        $structureLines,
                        $monthlyBuckets,
                        $result
                    );
                } else {
                    $this->createTermlyInvoice(
                        $student,
                        $schoolId,
                        $academicYearId,
                        $termId,
                        $dueDate,
                        $appliedWaiverId,
                        $waiverDetailsString,
                        $termSubtotal,
                        $termDiscount,
                        $termTotal,
                        $structureLines,
                        $result
                    );
                }
            }
        });

        return $result;
    }

    // ------------------------------------------------------------------
    //  Monthly billing helpers
    // ------------------------------------------------------------------

    /**
     * Resolve the calendar months spanning a term. Each bucket is
     * ['period' => '2026-03', 'label' => 'March', 'month_num' => 3].
     */
    protected function resolveMonthlyBuckets(Term $term): array
    {
        $start = $term->start_date instanceof Carbon ? $term->start_date->copy()->startOfMonth() : Carbon::parse($term->start_date)->startOfMonth();
        $end = $term->end_date instanceof Carbon ? $term->end_date->copy()->endOfMonth() : Carbon::parse($term->end_date)->endOfMonth();

        $buckets = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $buckets[] = [
                'period' => $cursor->format('Y-m'),
                'label' => $cursor->translatedFormat('F Y'),
                'month_num' => (int) $cursor->format('m'),
                'year_num' => (int) $cursor->format('Y'),
            ];
            $cursor->addMonth();
        }

        return $buckets;
    }

    protected function alreadyBilledTermly(int $schoolId, int $studentId, int $yearId, int $termId): bool
    {
        return Invoice::withoutTenantScope()->where([
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'academic_year_id' => $yearId,
            'term_id' => $termId,
        ])->where('invoice_number', 'not like', 'CF-%')->whereNull('billing_period')->exists();
    }

    protected function alreadyBilledMonthly(int $schoolId, int $studentId, int $yearId, int $termId): bool
    {
        return Invoice::withoutTenantScope()->where([
            'school_id' => $schoolId,
            'student_id' => $studentId,
            'academic_year_id' => $yearId,
            'term_id' => $termId,
        ])->where('invoice_number', 'not like', 'CF-%')->whereNotNull('billing_period')->exists();
    }

    /**
     * Create one invoice per month, splitting amounts evenly across the
     * buckets with the remainder assigned to the last month.
     */
    protected function createMonthlyInvoices(
        $student,
        int $schoolId,
        int $yearId,
        int $termId,
        $dueDate,
        ?int $appliedWaiverId,
        ?string $waiverDetailsString,
        float $termSubtotal,
        float $termDiscount,
        float $termTotal,
        array $structureLines,
        array $buckets,
        array &$result
    ): void {
        $monthCount = count($buckets);

        // Split discount proportionally across months
        $discountParts = $this->splitAmount($termDiscount, $monthCount);
        // Split total across months to keep book balance exact
        $totalParts = $this->splitAmount($termTotal, $monthCount);

        foreach ($buckets as $i => $bucket) {
            $monthlyDiscount = $discountParts[$i];
            $monthlyTotal = $totalParts[$i];

            // Determine the billing date: last day of the month, or use dueDate for the first bucket
            $billingDate = Carbon::parse($bucket['period'])->endOfMonth();
            $monthDueDate = $i === 0 ? $dueDate : Carbon::parse($bucket['period'])->endOfMonth();

            // Invoice number: INV-YYYY-NNNNN-MMM
            $invoiceCount = Invoice::where('school_id', $schoolId)->count() + 1 + $i;
            $monthTag = 'M'.str_pad((string) $bucket['month_num'], 2, '0', STR_PAD_LEFT);
            $invoiceNumber = 'INV-'.Carbon::parse($dueDate)->format('Y').'-'.str_pad($invoiceCount, 5, '0', STR_PAD_LEFT).'-'.$monthTag;

            // Line items: split each structure amount proportionally
            $monthlyLineItems = [];
            foreach ($structureLines as $line) {
                $monthlyLineItems[] = [
                    'fee_structure_id' => $line['fee_structure_id'],
                    'name' => $line['name'].' — '.$bucket['label'],
                    'amount' => $this->splitAmount($line['amount'], $monthCount)[$i],
                ];
            }

            $monthlySubtotal = array_sum(array_column($monthlyLineItems, 'amount'));

            $invoice = Invoice::withoutTenantScope()->create([
                'school_id' => $schoolId,
                'student_id' => $student->id,
                'academic_year_id' => $yearId,
                'term_id' => $termId,
                'fee_waiver_id' => $appliedWaiverId,
                'invoice_number' => $invoiceNumber,
                'billing_period' => $bucket['period'],
                'currency' => 'USD',
                'subtotal_amount' => round($monthlySubtotal, 2),
                'discount_amount' => round($monthlyDiscount, 2),
                'waiver_details' => $waiverDetailsString,
                'total_amount' => round($monthlyTotal, 2),
                'paid_amount' => 0.00,
                'balance_amount' => round($monthlyTotal, 2),
                'status' => $monthlyTotal > 0 ? 'unpaid' : 'paid',
                'due_date' => $monthDueDate,
            ]);

            // Set the billing month as the invoice ledger date (not the DB insert timestamp)
            $invoice->created_at = $billingDate;
            $invoice->saveQuietly();

            foreach ($monthlyLineItems as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'fee_structure_id' => $item['fee_structure_id'],
                    'name' => $item['name'],
                    'amount' => round($item['amount'], 2),
                ]);
            }

            // Apply carried-forward credit only on the first monthly invoice
            if ($i === 0 && (float) $student->credit_balance > 0 && $monthlyTotal > 0) {
                PaymentSettlementService::applyCredit($invoice, (float) $student->credit_balance);
            }

            $result['generated']++;
        }
    }

    // ------------------------------------------------------------------
    //  Termly (single invoice per term) — original behaviour
    // ------------------------------------------------------------------

    protected function createTermlyInvoice(
        $student,
        int $schoolId,
        int $yearId,
        int $termId,
        $dueDate,
        ?int $appliedWaiverId,
        ?string $waiverDetailsString,
        float $termSubtotal,
        float $termDiscount,
        float $termTotal,
        array $structureLines,
        array &$result
    ): void {
        $invoiceCount = Invoice::where('school_id', $schoolId)->count() + 1;
        $invoiceNumber = 'INV-'.Carbon::parse($dueDate)->format('Y').'-'.str_pad($invoiceCount, 5, '0', STR_PAD_LEFT);

        $invoice = Invoice::create([
            'school_id' => $schoolId,
            'student_id' => $student->id,
            'academic_year_id' => $yearId,
            'term_id' => $termId,
            'fee_waiver_id' => $appliedWaiverId,
            'invoice_number' => $invoiceNumber,
            'currency' => 'USD',
            'subtotal_amount' => $termSubtotal,
            'discount_amount' => $termDiscount,
            'waiver_details' => $waiverDetailsString,
            'total_amount' => $termTotal,
            'paid_amount' => 0.00,
            'balance_amount' => $termTotal,
            'status' => $termTotal > 0 ? 'unpaid' : 'paid',
            'due_date' => $dueDate,
        ]);

        foreach ($structureLines as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'fee_structure_id' => $item['fee_structure_id'],
                'name' => $item['name'],
                'amount' => $item['amount'],
            ]);
        }

        if ((float) $student->credit_balance > 0 && $termTotal > 0) {
            PaymentSettlementService::applyCredit($invoice, (float) $student->credit_balance);
        }

        $result['generated']++;
    }

    // ------------------------------------------------------------------
    //  Shared helpers
    // ------------------------------------------------------------------

    protected function resolveFeeStructures(int $schoolId, int $yearId, int $termId, $course): Collection
    {
        $courseName = strtolower($course->name);
        $applicableScopes = ['all', 'single'];

        if (preg_match('/form\s*[1-4]/i', $courseName) || preg_match('/grade\s*[8-9]/i', $courseName)) {
            $applicableScopes[] = 'form_1_4';
        } elseif (preg_match('/form\s*[5-6]/i', $courseName) || preg_match('/six/i', $courseName)) {
            $applicableScopes[] = 'form_5_6';
        } elseif (preg_match('/ecd/i', $courseName) || preg_match('/infant/i', $courseName)) {
            $applicableScopes[] = 'ecd';
        } elseif (preg_match('/grade\s*[1-7]/i', $courseName)) {
            $applicableScopes[] = 'grade_1_7';
        }

        return FeeStructure::with('feeCategory')
            ->where('school_id', $schoolId)
            ->where(function ($q) use ($yearId) {
                $q->where('academic_year_id', $yearId)
                    ->orWhereNull('academic_year_id');
            })
            ->where(function ($q) use ($termId) {
                $q->where('term_id', $termId)
                    ->orWhereNull('term_id');
            })
            ->where(function ($q) use ($applicableScopes, $course) {
                $q->whereIn('scope_type', $applicableScopes)
                    ->where(function ($sub) use ($course) {
                        $sub->where('scope_type', '!=', 'single')
                            ->orWhere('course_id', $course->id);
                    });
            })->get();
    }

    /**
     * Split a total amount into N equal parts with the remainder on the
     * last part so that sum(parts) === $total exactly.
     *
     * @return float[]
     */
    protected function splitAmount(float $total, int $parts): array
    {
        if ($parts <= 1) {
            return [round($total, 2)];
        }

        $base = round($total / $parts, 2);
        $result = array_fill(0, $parts, $base);
        $result[$parts - 1] = round($total - $base * ($parts - 1), 2);

        return $result;
    }
}
