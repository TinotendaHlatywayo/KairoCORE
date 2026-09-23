<?php

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Students\Models\Student;

/**
 * Builds a chronological per-student financial ledger (invoices billed,
 * waivers applied, payments received, refunds issued and carry-forward
 * credits) optionally scoped to a date range, academic year or term.
 */
class StudentFinancialHistoryService
{
    public const TYPE_BILLED = 'invoice_billed';
    public const TYPE_WAIVER = 'waiver';
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_REFUND = 'refund';
    public const TYPE_CREDIT = 'credit';

    /**
     * Resolve an inclusive [start, end] date range from a year, term or a pair
     * of custom dates. Returns null boundaries when "entire history" is wanted,
     * i.e. from the student's enrolment to today.
     *
     * @return array{start: ?Carbon, end: ?Carbon, label: string}
     */
    public static function scopeDates(int $schoolId, ?int $yearId = null, ?int $termId = null, ?string $start = null, ?string $end = null): array
    {
        if ($start && $end) {
            return [
                'start' => Carbon::parse($start)->startOfDay(),
                'end' => Carbon::parse($end)->endOfDay(),
                'label' => 'Custom range ('.Carbon::parse($start)->toDateString().' to '.Carbon::parse($end)->toDateString().')',
            ];
        }

        if ($termId) {
            $term = \Modules\Academics\Models\Term::withoutTenantScope()->with('academicYear')->find($termId);
            if ($term) {
                return [
                    'start' => $term->start_date?->copy()?->startOfDay(),
                    'end' => $term->end_date?->copy()?->endOfDay(),
                    'label' => ucwords(strtolower((string) $term->name)).' — '.($term->academicYear?->name ?? $term->academic_year_id),
                ];
            }
        }

        if ($yearId) {
            $year = \Modules\Academics\Models\AcademicYear::withoutTenantScope()->find($yearId);
            if ($year) {
                return [
                    'start' => $year->start_date?->copy()?->startOfDay(),
                    'end' => $year->end_date?->copy()?->endOfDay(),
                    'label' => $year->name,
                ];
            }
        }

        return ['start' => null, 'end' => null, 'label' => 'Entire history (from enrolment)'];
    }

    /**
     * Build the full ledger for a student, optionally narrowed to a range.
     *
     * @return array{student: Student, rows: array, opening_balance: float, closing_balance: float, total_billed: float, total_paid: float, total_refunded: float, start: ?Carbon, end: ?Carbon, is_filtered: bool}
     */
    public static function buildLedger(Student $student, ?Carbon $start = null, ?Carbon $end = null): array
    {
        $invoices = Invoice::withoutTenantScope()
            ->where('student_id', $student->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $payments = Payment::withoutTenantScope()
            ->where('school_id', $student->school_id)
            ->whereIn('invoice_id', $invoices->pluck('id'))
            ->where('is_reversed', false)
            ->with('receivedBy')
            ->orderBy('payment_date', 'asc')
            ->get();

        $raw = [];

        foreach ($invoices as $inv) {
            $isCarryForward = str_starts_with((string) $inv->invoice_number, 'CF-');
            $raw[] = [
                'date' => $inv->created_at,
                'type' => self::TYPE_BILLED,
                'entity_type' => 'invoice',
                'entity_id' => $inv->id,
                'description' => $isCarryForward
                    ? 'Balance Brought Forward - Debit Carry Forward ('.$inv->invoice_number.')'
                    : 'Gross Fees Billed ('.$inv->invoice_number.')',
                'debit' => (float) $inv->subtotal_amount,
                'credit' => 0.00,
                'receipt' => null,
                'reference' => $inv->invoice_number,
                'method' => null,
                'received_by' => null,
                'invoice_number' => $inv->invoice_number,
                'invoice_paid' => (float) $inv->paid_amount,
                'invoice_discount' => (float) $inv->discount_amount,
            ];

            if ((float) $inv->discount_amount > 0) {
                $raw[] = [
                    'date' => $inv->created_at,
                    'type' => self::TYPE_WAIVER,
                    'entity_type' => 'invoice',
                    'entity_id' => $inv->id,
                    'description' => 'Waiver Applied: '.($inv->waiver_details ?? 'Scholarship / Discount'),
                    'debit' => 0.00,
                    'credit' => (float) $inv->discount_amount,
                    'receipt' => null,
                    'reference' => $inv->invoice_number,
                    'method' => null,
                    'received_by' => null,
                    'invoice_number' => $inv->invoice_number,
                    'invoice_paid' => (float) $inv->paid_amount,
                    'invoice_discount' => (float) $inv->discount_amount,
                ];
            }
        }

        foreach ($payments as $pay) {
            $isRefund = (bool) $pay->is_refund;
            $isCredit = (! $isRefund) && $pay->payment_method === 'credit';
            $raw[] = [
                'date' => $pay->payment_date,
                'type' => $isRefund
                    ? self::TYPE_REFUND
                    : ($isCredit ? self::TYPE_CREDIT : self::TYPE_PAYMENT),
                'entity_type' => 'payment',
                'entity_id' => $pay->id,
                'description' => $isRefund
                    ? 'Refund Issued (Receipt: '.$pay->receipt_number.')'
                    : ($isCredit
                        ? 'Credit Applied (Carry Forward)'
                        : 'Payment Received (Receipt: '.$pay->receipt_number.')'),
                'debit' => 0.00,
                'credit' => (float) $pay->amount,
                'receipt' => $pay->receipt_number,
                'reference' => $pay->reference_number,
                'method' => $pay->payment_method,
                'received_by' => $pay->receivedBy?->name,
            ];
        }

        usort($raw, fn ($a, $b) => ($a['date']->timestamp ?? 0) <=> ($b['date']->timestamp ?? 0)
            ?: strcmp((string) $a['type'], (string) $b['type']));

        $balance = 0.00;
        foreach ($raw as &$row) {
            $balance += (float) $row['debit'] - (float) $row['credit'];
            $row['running_balance'] = round($balance, 2);
        }
        unset($row);

        $isFiltered = $start !== null || $end !== null;
        $inRange = function ($row) use ($start, $end): bool {
            $date = $row['date'];
            if ($start && $date->lt($start->copy()->startOfDay())) {
                return false;
            }
            if ($end && $date->gt($end->copy()->endOfDay())) {
                return false;
            }

            return true;
        };

        $rows = array_values(array_filter($raw, $inRange));

        $opening = 0.00;
        if ($start) {
            foreach ($raw as $row) {
                if ($row['date']->lt($start->copy()->startOfDay())) {
                    $opening = $row['running_balance'];
                }
            }

            array_unshift($rows, [
                'date' => $start->copy()->subDay(),
                'type' => 'opening',
                'description' => $opening >= 0
                    ? 'Opening Balance (carried forward)'
                    : 'Opening Credit (carried forward)',
                'debit' => 0.00,
                'credit' => 0.00,
                'receipt' => null,
                'reference' => null,
                'method' => null,
                'received_by' => null,
                'running_balance' => round($opening, 2),
                'is_opening' => true,
            ]);
        }

        $closing = $rows ? (float) end($rows)['running_balance'] : round($opening, 2);

        $totalBilled = array_sum(array_map(fn ($row) => $row['type'] === self::TYPE_BILLED ? (float) $row['debit'] : 0, $rows));
        $totalPaid = array_sum(array_map(fn ($row) => in_array($row['type'], [self::TYPE_PAYMENT, self::TYPE_CREDIT], true) ? max(0, (float) $row['credit']) : 0, $rows));
        $totalRefunded = array_sum(array_map(fn ($row) => $row['type'] === self::TYPE_REFUND ? abs((float) $row['credit']) : 0, $rows));

        return [
            'student' => $student,
            'rows' => $rows,
            'opening_balance' => round($opening, 2),
            'closing_balance' => round($closing, 2),
            'total_billed' => round($totalBilled, 2),
            'total_paid' => round($totalPaid, 2),
            'total_refunded' => round($totalRefunded, 2),
            'monthly_summary' => self::monthlySummary($rows),
            'start' => $start,
            'end' => $end,
            'is_filtered' => $isFiltered,
        ];
    }

    /**
     * Build the chronological debit/credit ledger used by the "Official
     * Statement of Account" (single print, bulk print, template preview and QR
     * verification page).
     *
     * Rows are sorted by date FIRST and the running balance is computed AFTER
     * sorting, so same-day transactions keep mathematically correct balances
     * (unlike the legacy builders that summed before sorting). Refunds are
     * labelled "Refund Issued" and flagged so renderers can display their
     * amount even though the stored payment amount is negative.
     *
     * @return array{ledger: array, current_balance: float}
     */
    public static function buildStatementLedger(Student $student, int $schoolId): array
    {
        $invoices = Invoice::withoutTenantScope()
            ->where('student_id', $student->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $payments = Payment::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->whereIn('invoice_id', $invoices->pluck('id'))
            ->where('is_reversed', false)
            ->orderBy('payment_date', 'asc')
            ->get();

        $raw = [];

        foreach ($invoices as $inv) {
            $isCarryForward = str_starts_with((string) $inv->invoice_number, 'CF-');
            $raw[] = [
                'date' => $inv->created_at,
                'priority' => 0,
                'is_refund' => false,
                'type' => $isCarryForward
                    ? 'Balance Brought Forward - Debit Carry Forward ('.$inv->invoice_number.')'
                    : 'Gross Fees Billed ('.$inv->invoice_number.')',
                'debit' => (float) $inv->subtotal_amount,
                'credit' => 0.00,
            ];

            if ((float) $inv->discount_amount > 0) {
                $raw[] = [
                    'date' => $inv->created_at,
                    'priority' => 1,
                    'is_refund' => false,
                    'type' => 'Waiver Applied: '.($inv->waiver_details ?? 'Scholarship / Discount'),
                    'debit' => 0.00,
                    'credit' => (float) $inv->discount_amount,
                ];
            }
        }

        foreach ($payments as $pay) {
            $isRefund = (bool) $pay->is_refund;
            $isCredit = (! $isRefund) && $pay->payment_method === 'credit';
            $raw[] = [
                'date' => $pay->payment_date,
                'priority' => $isRefund ? 4 : ($isCredit ? 2 : 3),
                'is_refund' => $isRefund,
                'type' => $isRefund
                    ? 'Refund Issued (Receipt: '.$pay->receipt_number.')'
                    : ($isCredit
                        ? 'Credit Applied (Carry Forward)'
                        : 'Payment Received (Receipt: '.$pay->receipt_number.')'),
                'debit' => 0.00,
                'credit' => (float) $pay->amount,
            ];
        }

        // Sort by calendar day FIRST, then by priority, so a billing and a
        // payment recorded on the same day always print billed-then-paid
        // regardless of the stored time-of-day (payment_date is a date cast
        // and hydrates at midnight, while invoice created_at keeps its time).
        $day = fn ($r) => $r['date']->copy()->startOfDay()->timestamp;
        usort($raw, fn ($a, $b) => $day($a) <=> $day($b)
            ?: ($a['priority'] <=> $b['priority']));

        $balance = 0.00;
        foreach ($raw as &$row) {
            $balance += (float) $row['debit'] - (float) $row['credit'];
            $row['running_balance'] = round($balance, 2);
            unset($row['priority']);
        }
        unset($row);

        return ['ledger' => $raw, 'current_balance' => round($balance, 2)];
    }

    /**
     * Group ledger rows by calendar month (invoice billing date / payment date)
     * so the statement can summarise performance per month when the school
     * bills monthly.
     *
     * @return array<int, array{period: string, label: string, billed: float, paid: float, refunded: float, balance: float}>
     */
    public static function monthlySummary(array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            if ($row['is_opening'] ?? false) {
                continue;
            }

            $date = $row['date'];
            if (! $date) {
                continue;
            }

            $month = $date instanceof Carbon ? $date->copy()->startOfMonth() : Carbon::parse($date)->startOfMonth();
            $period = $month->format('Y-m');
            $grouped[$period] ??= [
                'period' => $period,
                'label' => $month->translatedFormat('F Y'),
                'billed' => 0.0,
                'paid' => 0.0,
                'refunded' => 0.0,
            ];

            if ($row['type'] === self::TYPE_BILLED) {
                $grouped[$period]['billed'] += (float) $row['debit'];
            } elseif (in_array($row['type'], [self::TYPE_PAYMENT, self::TYPE_CREDIT], true)) {
                $grouped[$period]['paid'] += max(0, (float) $row['credit']);
            } elseif ($row['type'] === self::TYPE_REFUND) {
                $grouped[$period]['refunded'] += abs((float) $row['credit']);
            }
        }

        ksort($grouped);

        $balance = 0.00;
        foreach ($grouped as &$g) {
            $balance += $g['billed'] - $g['paid'] - $g['refunded'];
            $g['balance'] = round($balance, 2);
            $g['billed'] = round($g['billed'], 2);
            $g['paid'] = round($g['paid'], 2);
            $g['refunded'] = round($g['refunded'], 2);
        }
        unset($g);

        return array_values($grouped);
    }

    /**
     * Per-student collection summary for a date range: who paid, how much, and
     * the overall total received in that period (used by the collections view).
     *
     * @return array{total: float, refunds: float, payments_count: int, payments: \Illuminate\Support\Collection, students: \Illuminate\Support\Collection}
     */
    public static function collectionsForRange(int $schoolId, ?Carbon $start = null, ?Carbon $end = null): array
    {
        $rangeQuery = function ($q) use ($start, $end) {
            if ($start) {
                $q->whereDate('payment_date', '>=', $start->toDateString());
            }
            if ($end) {
                $q->whereDate('payment_date', '<=', $end->toDateString());
            }
        };

        $query = Payment::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('is_reversed', false)
            ->where(fn ($q) => $q->where('is_refund', false)->orWhereNull('is_refund'))
            ->with(['invoice.student.currentEnrollment.course', 'invoice.student.currentEnrollment.section', 'receivedBy']);

        $rangeQuery($query);

        $payments = $query->orderBy('payment_date', 'desc')->get();

        $total = $payments->sum(fn ($p) => max(0, (float) $p->amount));

        $refundQuery = Payment::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('is_reversed', false)
            ->where('is_refund', true);
        $rangeQuery($refundQuery);

        $refunds = abs((float) $refundQuery->sum('amount'));

        $students = $payments->groupBy(fn ($p) => $p->invoice_id)->map(function ($group) {
            $first = $group->first()->invoice;

            return (object) [
                'student' => $first?->student,
                'total' => $group->sum(fn ($p) => max(0, (float) $p->amount)),
                'count' => $group->count(),
                'last_date' => $group->max(fn ($p) => $p->payment_date?->toDateString()),
            ];
        })->values()->sortByDesc('total');

        return [
            'total' => round($total, 2),
            'refunds' => round($refunds, 2),
            'payments_count' => $payments->count(),
            'payments' => $payments,
            'students' => $students,
        ];
    }
}