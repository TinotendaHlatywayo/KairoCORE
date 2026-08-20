<?php

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
     * Returns a diagnostic breakdown instead of a bare count so operators can
     * understand exactly why some students were skipped.
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

        $result = [
            'generated' => 0,
            'scanned' => 0,
            'no_enrollment_match' => 0,
            'already_billed' => 0,
            'no_fee_structure' => 0,
            'missing_data' => 0,
        ];

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

        DB::transaction(function () use ($enrollments, $academicYearId, $termId, $dueDate, $schoolId, &$result) {
            foreach ($enrollments as $enrollment) {
                $result['scanned']++;

                $student = $enrollment->student;
                $course = $enrollment->course;

                // FIX: Clean safety check. If the student has been soft-deleted or the course is missing, skip safely.
                if (! $student || ! $course) {
                    $result['missing_data']++;

                    continue;
                }

                // Prevent double-billing
                $exists = Invoice::where([
                    'school_id' => $schoolId,
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYearId,
                    'term_id' => $termId,
                ])->exists();

                if ($exists) {
                    $result['already_billed']++;

                    continue;
                }

                // Resolve applicable fee structures for this specific student's curriculum bracket
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

                // FIX: Standardized query builder calls on the active "scope_type" database column
                $feeStructures = FeeStructure::with('feeCategory')
                    ->where([
                        'school_id' => $schoolId,
                        'academic_year_id' => $academicYearId,
                        'term_id' => $termId,
                    ])
                    ->where(function ($q) use ($applicableScopes, $course) {
                        $q->whereIn('scope_type', $applicableScopes)
                            ->where(function ($sub) use ($course) {
                                $sub->where('scope_type', '!=', 'single')
                                    ->orWhere('course_id', $course->id);
                            });
                    })->get();

                if ($feeStructures->isEmpty()) {
                    $result['no_fee_structure']++;

                    continue;
                }

                $subtotal = 0;
                $lineItems = [];

                foreach ($feeStructures as $structure) {
                    $subtotal += $structure->amount;
                    $lineItems[] = [
                        'fee_structure_id' => $structure->id,
                        'name' => $structure->feeCategory->name,
                        'amount' => $structure->amount,
                    ];
                }

                // Apply individual waivers
                $discount = 0;
                $appliedWaiverId = null;
                $waiverDetailsString = null;

                $waiver = $student->waivers->first();
                if ($waiver) {
                    $appliedWaiverId = $waiver->id;
                    if ($waiver->type === 'percentage') {
                        $discount = ($subtotal * ($waiver->value / 100));
                        $waiverDetailsString = "{$waiver->name} ({$waiver->value}% - \$".number_format($discount, 2).')';
                    } elseif ($waiver->type === 'fixed') {
                        $discount = $waiver->value;
                        $waiverDetailsString = "{$waiver->name} (Fixed - \$".number_format($discount, 2).')';
                    }
                }

                $total = max(0, $subtotal - $discount);

                $invoiceCount = Invoice::where('school_id', $schoolId)->count() + 1;
                $invoiceNumber = 'INV-'.Carbon::parse($dueDate)->format('Y').'-'.str_pad($invoiceCount, 5, '0', STR_PAD_LEFT);

                $invoice = Invoice::create([
                    'school_id' => $schoolId,
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYearId,
                    'term_id' => $termId,
                    'fee_waiver_id' => $appliedWaiverId,
                    'invoice_number' => $invoiceNumber,
                    'currency' => 'USD',
                    'subtotal_amount' => $subtotal,
                    'discount_amount' => $discount,
                    'waiver_details' => $waiverDetailsString,
                    'total_amount' => $total,
                    'paid_amount' => 0.00,
                    'balance_amount' => $total,
                    'status' => $total > 0 ? 'unpaid' : 'paid',
                    'due_date' => $dueDate,
                ]);

                foreach ($lineItems as $item) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'fee_structure_id' => $item['fee_structure_id'],
                        'name' => $item['name'],
                        'amount' => $item['amount'],
                    ]);
                }

                $result['generated']++;
            }
        });

        return $result;
    }
}
