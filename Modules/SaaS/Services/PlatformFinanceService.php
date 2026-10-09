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
}
