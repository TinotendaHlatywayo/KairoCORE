<?php

namespace Modules\SaaS\Services;

use App\Models\School;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\SaaS\Models\PlatformExpense;
use Modules\SaaS\Models\SaaSInvoice;
use Modules\SaaS\Models\SaaSReceipt;
use Modules\SaaS\Models\SaaSTransaction;

/**
 * Platform (KairoCORE) finance analytics: revenue, operating expenses and
 * net profit for a date range, plus a per-tenant billing statement combining
 * invoices, receipts and payments.
 *
 * Aggregation is done in PHP (not raw SQL) so the same logic runs identically
 * on MySQL and SQLite test databases.
 */
class PlatformFinanceService
{
    public function summary(CarbonInterface|string $start, CarbonInterface|string $end): array
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->endOfDay();
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $transactions = SaaSTransaction::with('school')
            ->where('status', 'completed')
            ->whereBetween('processed_at', [$start, $end])
            ->orderBy('processed_at')
            ->get();

        $revenue = round((float) $transactions->sum('amount'), 2);

        $expenseRows = PlatformExpense::query()
            ->whereDate('expense_date', '>=', $startDate)
            ->whereDate('expense_date', '<=', $endDate)
            ->orderBy('expense_date')
            ->get();

        $expenses = round((float) $expenseRows->sum('amount'), 2);
        $expensesPaid = round((float) $expenseRows->where('is_paid', true)->sum('amount'), 2);
        $expensesUnpaid = round($expenses - $expensesPaid, 2);

        $receiptsTotal = round((float) SaaSReceipt::query()
            ->whereBetween('issued_at', [$start, $end])
            ->sum('amount_paid'), 2);

        $invoicedTotal = round((float) SaaSInvoice::query()
            ->whereDate('issue_date', '>=', $startDate)
            ->whereDate('issue_date', '<=', $endDate)
            ->sum('total'), 2);

        $outstandingTotal = round((float) SaaSInvoice::query()
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->sum('total'), 2);

        $netProfit = round($revenue - $expenses, 2);
        $margin = $revenue > 0 ? round(($netProfit / $revenue) * 100, 1) : null;

        return [
            'start' => $start,
            'end' => $end,
            'currency' => 'USD',
            'revenue' => $revenue,
            'receipts_total' => $receiptsTotal,
            'invoiced_total' => $invoicedTotal,
            'outstanding_total' => $outstandingTotal,
            'expenses' => $expenses,
            'expenses_paid' => $expensesPaid,
            'expenses_unpaid' => $expensesUnpaid,
            'net_profit' => $netProfit,
            'margin' => $margin,
            'transaction_count' => $transactions->count(),
            'expense_count' => $expenseRows->count(),
            'months' => $this->monthBuckets($start, $end, $transactions, $expenseRows),
            'by_category' => $this->expensesByCategory($expenseRows),
            'by_tenant' => $this->revenueByTenant($transactions),
        ];
    }

    /**
     * @param  Collection<int, SaaSTransaction>  $transactions
     * @param  Collection<int, PlatformExpense>  $expenses
     */
    protected function monthBuckets(CarbonInterface $start, CarbonInterface $end, $transactions, $expenses): array
    {
        $months = [];
        $cursor = $start->copy()->startOfMonth();

        while ($cursor->lte($end)) {
            $months[$cursor->format('Y-m')] = [
                'label' => $cursor->format('M Y'),
                'revenue' => 0.0,
                'expenses' => 0.0,
            ];
            $cursor->addMonthNoOverflow();
        }

        foreach ($transactions as $transaction) {
            $key = optional($transaction->processed_at)->format('Y-m');
            if ($key && isset($months[$key])) {
                $months[$key]['revenue'] += (float) $transaction->amount;
            }
        }

        foreach ($expenses as $expense) {
            $key = $expense->expense_date?->format('Y-m');
            if ($key && isset($months[$key])) {
                $months[$key]['expenses'] += (float) $expense->amount;
            }
        }

        foreach ($months as &$month) {
            $month['revenue'] = round($month['revenue'], 2);
            $month['expenses'] = round($month['expenses'], 2);
            $month['net'] = round($month['revenue'] - $month['expenses'], 2);
        }
        unset($month);

        return array_values($months);
    }

    /**
     * Calendar years that have any recorded finance activity (plus the current
     * year), newest first, for the monthly-breakdown year selector.
     *
     * @return list<int>
     */
    public function availableYears(): array
    {
        $transactionYears = SaaSTransaction::query()
            ->whereNotNull('processed_at')
            ->pluck('processed_at')
            ->map(fn ($date) => (int) Carbon::parse($date)->year);

        $expenseYears = PlatformExpense::query()
            ->whereNotNull('expense_date')
            ->pluck('expense_date')
            ->map(fn ($date) => (int) Carbon::parse($date)->year);

        return $transactionYears
            ->merge($expenseYears)
            ->push((int) now()->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * Whole-year monthly breakdown. Every started month is returned (even with
     * no activity) so December shows January through November. Figures are
     * independent of the statement date range and cover the full calendar year.
     *
     * @return array{year: int, months: list<array{label: string, revenue: float, expenses: float, net: float}>}
     */
    public function yearBreakdown(int $year): array
    {
        $today = now();

        $lastMonth = 12;

        if ($year === (int) $today->year) {
            // The current month is still in progress, so only completed months
            // are shown (in December this runs up to November).
            $lastMonth = (int) $today->subMonthNoOverflow()->month;
        } elseif ($year > (int) $today->year) {
            $lastMonth = 0;
        }

        $months = [];

        for ($month = 1; $month <= $lastMonth; $month++) {
            $months[$month] = [
                'label' => Carbon::create($year, $month, 1)->format('M Y'),
                'revenue' => 0.0,
                'expenses' => 0.0,
                'net' => 0.0,
            ];
        }

        $transactions = SaaSTransaction::query()
            ->where('status', 'completed')
            ->whereYear('processed_at', $year)
            ->get();

        foreach ($transactions as $transaction) {
            $key = (int) Carbon::parse($transaction->processed_at)->month;

            if (isset($months[$key])) {
                $months[$key]['revenue'] += (float) $transaction->amount;
            }
        }

        $expenses = PlatformExpense::query()
            ->whereYear('expense_date', $year)
            ->get();

        foreach ($expenses as $expense) {
            $key = (int) $expense->expense_date->month;

            if (isset($months[$key])) {
                $months[$key]['expenses'] += (float) $expense->amount;
            }
        }

        foreach ($months as &$month) {
            $month['revenue'] = round($month['revenue'], 2);
            $month['expenses'] = round($month['expenses'], 2);
            $month['net'] = round($month['revenue'] - $month['expenses'], 2);
        }
        unset($month);

        return [
            'year' => $year,
            'months' => array_values($months),
        ];
    }

    protected function expensesByCategory($expenses): array
    {
        return $expenses
            ->groupBy(fn (PlatformExpense $expense) => $expense->category ?: __('Uncategorised'))
            ->map(fn ($group, $category) => [
                'category' => (string) $category,
                'total' => round((float) $group->sum('amount'), 2),
                'count' => $group->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    protected function revenueByTenant($transactions): array
    {
        return $transactions
            ->groupBy('school_id')
            ->map(function ($group) {
                return [
                    'school' => $group->first()->school?->name ?? __('Unknown tenant'),
                    'total' => round((float) $group->sum('amount'), 2),
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->take(10)
            ->all();
    }

    /**
     * Combined per-tenant billing statement for a period: invoices raised,
     * receipts issued and payments received.
     */
    public function tenantStatement(int $schoolId, CarbonInterface|string $start, CarbonInterface|string $end): array
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->endOfDay();
        $startDate = $start->toDateString();
        $endDate = $end->toDateString();

        $school = School::query()->find($schoolId);

        $invoices = SaaSInvoice::with('items')
            ->where('school_id', $schoolId)
            ->whereDate('issue_date', '>=', $startDate)
            ->whereDate('issue_date', '<=', $endDate)
            ->orderBy('issue_date')
            ->get();

        $receipts = SaaSReceipt::with('invoice')
            ->where('school_id', $schoolId)
            ->whereBetween('issued_at', [$start, $end])
            ->orderBy('issued_at')
            ->get();

        $payments = SaaSTransaction::with('invoice')
            ->where('school_id', $schoolId)
            ->where('status', 'completed')
            ->whereBetween('processed_at', [$start, $end])
            ->orderBy('processed_at')
            ->get();

        return [
            'school' => $school,
            'start' => $start,
            'end' => $end,
            'currency' => $invoices->first()->currency
                ?? $payments->first()->currency
                ?? 'USD',
            'invoices' => $invoices,
            'receipts' => $receipts,
            'payments' => $payments,
            'invoiced_total' => round((float) $invoices->sum('total'), 2),
            'receipts_total' => round((float) $receipts->sum('amount_paid'), 2),
            'payments_total' => round((float) $payments->sum('amount'), 2),
            'outstanding_total' => round((float) SaaSInvoice::query()
                ->where('school_id', $schoolId)
                ->whereIn('status', ['unpaid', 'partially_paid'])
                ->sum('total'), 2),
            'generated_at' => now(),
        ];
    }

    /**
     * All-time payment history for one tenant: every invoice raised, receipt
     * issued and payment received, regardless of period. Used by the platform
     * admin's per-school "payment history" PDF.
     */
    public function paymentHistory(int $schoolId): array
    {
        $school = School::query()->find($schoolId);

        $invoices = SaaSInvoice::with('items')
            ->where('school_id', $schoolId)
            ->orderBy('issue_date')
            ->get();

        $receipts = SaaSReceipt::with('invoice')
            ->where('school_id', $schoolId)
            ->orderBy('issued_at')
            ->get();

        $payments = SaaSTransaction::with('invoice')
            ->where('school_id', $schoolId)
            ->where('status', 'completed')
            ->orderBy('processed_at')
            ->get();

        $entries = collect();

        foreach ($invoices as $invoice) {
            $entries->push([
                'sort' => optional($invoice->issue_date)->timestamp ?? 0,
                'date' => optional($invoice->issue_date)->format('M d, Y') ?? '—',
                'type' => __('Invoice'),
                'reference' => (string) $invoice->invoice_number,
                'detail' => __('Subscription invoice'),
                'amount' => (float) $invoice->total,
            ]);
        }

        foreach ($receipts as $receipt) {
            $entries->push([
                'sort' => optional($receipt->issued_at)->timestamp ?? 0,
                'date' => optional($receipt->issued_at)->format('M d, Y') ?? '—',
                'type' => __('Receipt'),
                'reference' => (string) $receipt->receipt_number,
                'detail' => $receipt->invoice?->invoice_number ?? __('Payment received'),
                'amount' => (float) $receipt->amount_paid,
            ]);
        }

        foreach ($payments as $payment) {
            $entries->push([
                'sort' => optional($payment->processed_at)->timestamp ?? 0,
                'date' => optional($payment->processed_at)->format('M d, Y') ?? '—',
                'type' => __('Payment'),
                'reference' => (string) ($payment->transaction_reference ?: ($payment->uuid ?? '—')),
                'detail' => strtoupper((string) $payment->payment_gateway_key),
                'amount' => (float) $payment->amount,
            ]);
        }

        return [
            'school' => $school,
            'currency' => $invoices->first()->currency
                ?? $payments->first()->currency
                ?? 'USD',
            'entries' => $entries->sortBy('sort')->values()->all(),
            'invoices' => $invoices,
            'receipts' => $receipts,
            'payments' => $payments,
            'invoiced_total' => round((float) $invoices->sum('total'), 2),
            'receipts_total' => round((float) $receipts->sum('amount_paid'), 2),
            'payments_total' => round((float) $payments->sum('amount'), 2),
            'outstanding_total' => round((float) SaaSInvoice::query()
                ->where('school_id', $schoolId)
                ->whereIn('status', ['unpaid', 'partially_paid'])
                ->sum('total'), 2),
            'generated_at' => now(),
        ];
    }
}
