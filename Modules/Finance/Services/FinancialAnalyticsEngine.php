<?php

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;

class FinancialAnalyticsEngine
{
    /**
     * Compute Executive Financial Summary for a given school tenant.
     */
    public function getSummary(int $schoolId): array
    {
        $totalRevenue = Payment::where('school_id', $schoolId)->where('is_reversed', 0)->sum('amount');
        $totalExpenses = Expense::where('school_id', $schoolId)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');
        $netSurplus = $totalRevenue - $totalExpenses;

        $outstandingFees = Invoice::where('school_id', $schoolId)
            ->where('status', '!=', 'void')
            ->sum('balance_amount');

        // Payables = approved but not yet paid expenses (real obligations).
        $accountsPayable = Expense::where('school_id', $schoolId)
            ->where('status', 'approved')
            ->sum('amount');

        // Cash position aligned with the revenue/expense figures already shown,
        // rather than an inconsistent proxy of invoice credits.
        $cashPosition = max(0, $netSurplus);

        return [
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_surplus' => $netSurplus,
            'outstanding_student_fees' => $outstandingFees,
            'accounts_receivable' => $outstandingFees,
            'accounts_payable' => $accountsPayable,
            'cash_on_hand' => $cashPosition,
            'bank_balances' => $cashPosition,
        ];
    }

    /**
     * Get Revenue Breakdown for interactive charts.
     */
    public function getRevenueBreakdown(int $schoolId): array
    {
        // Derive each invoice's fee category via a subquery so a payment is never
        // multiplied by the number of invoice items on its invoice.
        $invoiceCategories = DB::table('invoice_items')
            ->select('invoice_items.invoice_id', DB::raw('MAX(fee_categories.name) as category'))
            ->join('fee_structures', 'invoice_items.fee_structure_id', '=', 'fee_structures.id')
            ->leftJoin('fee_categories', 'fee_structures.fee_category_id', '=', 'fee_categories.id')
            ->groupBy('invoice_items.invoice_id');

        return DB::table('payments')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->joinSub($invoiceCategories, 'inv_cat', 'invoices.id', '=', 'inv_cat.invoice_id')
            ->where('payments.school_id', $schoolId)
            ->where('payments.is_reversed', 0)
            ->select('inv_cat.category as category', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('inv_cat.category')
            ->pluck('total', 'category')
            ->toArray();
    }

    /**
     * Get Student Fee Ageing Analysis.
     */
    public function getFeeAgeing(int $schoolId): array
    {
        $now = now();
        $invoices = Invoice::where('school_id', $schoolId)
            ->where('balance_amount', '>', 0)
            ->where('status', '!=', 'void')
            ->get();

        $ageing = [
            '0_30_days' => 0,
            '31_60_days' => 0,
            '61_90_days' => 0,
            '90_plus_days' => 0,
        ];

        foreach ($invoices as $inv) {
            // Age by days overdue (due_date), falling back to creation date when no due date is set.
            $age = $inv->due_date ? max(0, $inv->due_date->diffInDays($now, false)) : $inv->created_at->diffInDays($now);

            if ($age <= 30) {
                $ageing['0_30_days'] += $inv->balance_amount;
            } elseif ($age <= 60) {
                $ageing['31_60_days'] += $inv->balance_amount;
            } elseif ($age <= 90) {
                $ageing['61_90_days'] += $inv->balance_amount;
            } else {
                $ageing['90_plus_days'] += $inv->balance_amount;
            }
        }

        return $ageing;
    }

    /**
     * Get Monthly Revenue vs Expense Trend for time-series chart.
     */
    public function getRevenueExpenseTrend(int $schoolId, int $months = 12): array
    {
        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $revenueData = DB::table('payments')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as period'),
                DB::raw('SUM(amount) as total')
            )
            ->where('school_id', $schoolId)
            ->where('is_reversed', 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();

        $expenseData = DB::table('expenses')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as period'),
                DB::raw('SUM(amount) as total')
            )
            ->where('school_id', $schoolId)
            ->whereIn('status', ['approved', 'paid'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();

        $periods = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $periodKey = $current->format('Y-m');
            $periods[$periodKey] = $current->format('M Y');
            $current->addMonth();
        }

        $labels = array_values($periods);
        $revenue = [];
        $expenses = [];
        $net = [];

        foreach ($periods as $key => $label) {
            $rev = $revenueData[$key] ?? 0;
            $exp = $expenseData[$key] ?? 0;
            $revenue[] = (float) $rev;
            $expenses[] = (float) $exp;
            $net[] = (float) ($rev - $exp);
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net' => $net,
            'periods' => array_keys($periods),
        ];
    }

    /**
     * Get Cash Flow Timeline (monthly net cash position).
     */
    public function getCashFlowTimeline(int $schoolId, int $months = 12): array
    {
        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $cashIn = DB::table('payments')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as period'),
                DB::raw('SUM(amount) as total')
            )
            ->where('school_id', $schoolId)
            ->where('is_reversed', 0)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();

        $cashOut = DB::table('expenses')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as period'),
                DB::raw('SUM(amount) as total')
            )
            ->where('school_id', $schoolId)
            ->whereIn('status', ['approved', 'paid'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();

        $periods = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $periodKey = $current->format('Y-m');
            $periods[$periodKey] = $current->format('M Y');
            $current->addMonth();
        }

        $labels = array_values($periods);
        $inflow = [];
        $outflow = [];
        $netFlow = [];
        $cumulative = 0;
        $cumulativeFlow = [];

        foreach ($periods as $key => $label) {
            $in = (float) ($cashIn[$key] ?? 0);
            $out = (float) ($cashOut[$key] ?? 0);
            $net = $in - $out;
            $cumulative += $net;

            $inflow[] = $in;
            $outflow[] = $out;
            $netFlow[] = $net;
            $cumulativeFlow[] = $cumulative;
        }

        return [
            'labels' => $labels,
            'inflow' => $inflow,
            'outflow' => $outflow,
            'net_flow' => $netFlow,
            'cumulative_flow' => $cumulativeFlow,
            'periods' => array_keys($periods),
        ];
    }

    /**
     * Get Monthly Collection Rate (invoiced vs collected).
     */
    public function getCollectionRateTrend(int $schoolId, int $months = 12): array
    {
        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $invoicedData = DB::table('invoices')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as period'),
                DB::raw('SUM(total_amount) as total')
            )
            ->where('school_id', $schoolId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();

        $collectedData = DB::table('payments')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->select(
                DB::raw('DATE_FORMAT(payments.created_at, "%Y-%m") as period'),
                DB::raw('SUM(payments.amount) as total')
            )
            ->where('payments.school_id', $schoolId)
            ->where('payments.is_reversed', 0)
            ->whereBetween('payments.created_at', [$startDate, $endDate])
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();

        $periods = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $periodKey = $current->format('Y-m');
            $periods[$periodKey] = $current->format('M Y');
            $current->addMonth();
        }

        $labels = array_values($periods);
        $invoiced = [];
        $collected = [];
        $rate = [];

        foreach ($periods as $key => $label) {
            $inv = (float) ($invoicedData[$key] ?? 0);
            $col = (float) ($collectedData[$key] ?? 0);
            $invoiced[] = $inv;
            $collected[] = $col;
            $rate[] = $inv > 0 ? round(($col / $inv) * 100, 1) : 0;
        }

        return [
            'labels' => $labels,
            'invoiced' => $invoiced,
            'collected' => $collected,
            'collection_rate' => $rate,
            'periods' => array_keys($periods),
        ];
    }

    /**
     * Get Expense Breakdown by Category.
     */
    public function getExpenseBreakdown(int $schoolId): array
    {
        return DB::table('expenses')
            ->join('expense_types', 'expenses.expense_type_id', '=', 'expense_types.id')
            ->join('expense_categories', 'expense_types.expense_category_id', '=', 'expense_categories.id', 'left')
            ->where('expenses.school_id', $schoolId)
            ->whereIn('expenses.status', ['approved', 'paid'])
            ->select('expense_categories.name as category', DB::raw('SUM(expenses.amount) as total'))
            ->groupBy('expense_categories.name')
            ->pluck('total', 'category')
            ->toArray();
    }

    /**
     * Get Payment Method Breakdown.
     */
    public function getPaymentMethodBreakdown(int $schoolId): array
    {
        return DB::table('payments')
            ->where('school_id', $schoolId)
            ->where('is_reversed', 0)
            ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->payment_method => [
                'total' => (float) $item->total,
                'count' => (int) $item->count,
            ]])
            ->toArray();
    }

    /**
     * Get Expense Register (detailed expense list).
     */
    public function getExpenseRegister(int $schoolId): array
    {
        return DB::table('expenses')
            ->leftJoin('expense_types', 'expenses.expense_type_id', '=', 'expense_types.id')
            ->leftJoin('expense_categories', 'expense_types.expense_category_id', '=', 'expense_categories.id')
            ->leftJoin('suppliers', 'expenses.supplier_id', '=', 'suppliers.id')
            ->leftJoin('users', 'expenses.user_id', '=', 'users.id')
            ->where('expenses.school_id', $schoolId)
            ->select(
                'expenses.reference_number',
                'expense_categories.name as category',
                'expense_types.name as type',
                'suppliers.name as supplier',
                'expenses.amount',
                'expenses.expense_date',
                'expenses.status',
                'users.name as recorded_by',
                'expenses.notes'
            )
            ->orderByDesc('expenses.expense_date')
            ->limit(50)
            ->get()
            ->toArray();
    }

    /**
     * Get Top Debtors (students with highest outstanding balances).
     */
    public function getTopDebtors(int $schoolId, int $limit = 20): array
    {
        return DB::table('invoices')
            ->join('students', 'invoices.student_id', '=', 'students.id')
            ->join('users', 'students.user_id', '=', 'users.id')
            ->where('invoices.school_id', $schoolId)
            ->where('invoices.balance_amount', '>', 0)
            ->where('invoices.status', '!=', 'void')
            ->select(
                'students.id as student_id',
                'users.name as student_name',
                'students.admission_number',
                DB::raw('SUM(invoices.balance_amount) as total_outstanding'),
                DB::raw('COUNT(invoices.id) as invoice_count'),
                DB::raw('MIN(invoices.due_date) as oldest_due_date')
            )
            ->groupBy('students.id', 'users.name', 'students.admission_number')
            ->orderByDesc('total_outstanding')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get Aged Receivables Detail for table view.
     */
    public function getAgedReceivablesDetail(int $schoolId): array
    {
        $now = Carbon::now();
        $invoices = Invoice::where('school_id', $schoolId)
            ->where('balance_amount', '>', 0)
            ->where('status', '!=', 'void')
            ->with(['student.user'])
            ->get();

        $results = [];
        foreach ($invoices as $inv) {
            $age = $inv->due_date ? max(0, $now->diffInDays($inv->due_date, false)) : $inv->created_at->diffInDays($now);
            $daysOverdue = $inv->due_date ? max(0, $now->diffInDays($inv->due_date, false)) : 0;

            $bucket = match (true) {
                $age <= 30 => '0-30',
                $age <= 60 => '31-60',
                $age <= 90 => '61-90',
                default => '90+'
            };

            $results[] = [
                'invoice_number' => $inv->invoice_number,
                'student_name' => $inv->student?->user?->name ?? 'N/A',
                'student_number' => $inv->student?->admission_number ?? 'N/A',
                'invoice_date' => $inv->created_at->format('Y-m-d'),
                'due_date' => $inv->due_date?->format('Y-m-d') ?? 'N/A',
                'total_amount' => (float) $inv->total_amount,
                'paid_amount' => (float) $inv->paid_amount,
                'balance_amount' => (float) $inv->balance_amount,
                'age_days' => $age,
                'days_overdue' => $daysOverdue,
                'ageing_bucket' => $bucket,
                'status' => $inv->status,
            ];
        }

        return $results;
    }

    /**
     * Get Revenue by Fee Category (alias for revenue breakdown).
     */
    public function getRevenueByDepartment(int $schoolId): array
    {
        return $this->getRevenueBreakdown($schoolId);
    }

    /**
     * Get a short revenue forecast by projecting the observed historical trend
     * forwards with ordinary least-squares regression (real data, no invented
     * growth assumptions). Falls back to the average of recent periods when
     * there is insufficient history to fit a line.
     */
    public function getRevenueForecast(int $schoolId, int $months = 6): array
    {
        $trend = $this->getRevenueExpenseTrend($schoolId, 12);
        $revenues = array_values(array_filter($trend['revenue'] ?? [], fn ($v) => $v > 0));

        $forecastLabels = [];
        $forecastValues = [];
        $current = Carbon::now();

        if (count($revenues) >= 2) {
            // Least-squares slope/intercept over the observed periods (0..n-1).
            $n = count($revenues);
            $x = range(0, $n - 1);
            $meanX = array_sum($x) / $n;
            $meanY = array_sum($revenues) / $n;
            $num = 0.0;
            $den = 0.0;
            foreach ($x as $i => $xi) {
                $num += ($xi - $meanX) * ($revenues[$i] - $meanY);
                $den += ($xi - $meanX) ** 2;
            }
            $slope = $den > 0 ? $num / $den : 0;
            $intercept = $meanY - $slope * $meanX;

            for ($i = 1; $i <= $months; $i++) {
                $current->addMonth();
                $forecastLabels[] = $current->format('M Y');
                // Only project forward if the trend is at least neutral; a heavy
                // negative slope is flattened to avoid absurd negative forecasts.
                $projected = $intercept + $slope * ($n - 1 + $i);
                $forecastValues[] = round(max(0, $projected), 2);
            }
        } else {
            // Insufficient history: hold the latest observed level flat.
            $baseline = $revenues[0] ?? 0;
            for ($i = 1; $i <= $months; $i++) {
                $current->addMonth();
                $forecastLabels[] = $current->format('M Y');
                $forecastValues[] = round($baseline, 2);
            }
        }

        return [
            'labels' => $forecastLabels,
            'forecast' => $forecastValues,
        ];
    }

    /**
     * Get Budget Variance analysis.
     *
     * Only produces real variance when formal budget baselines exist. No Budget
     * model is wired up yet, so this returns an empty set rather than presenting
     * a fabricated "estimated baseline" that would mislead an executive.
     */
    public function getBudgetVariance(int $schoolId): array
    {
        // When real budgets are introduced, load them here and compute variance
        // against actual approved/paid expenses per category. Until then there is
        // no reliable baseline, so we return nothing and the UI hides this block.
        return [];
    }

    /**
     * Get Year-over-Year comparison.
     */
    public function getYoYComparison(int $schoolId): array
    {
        $currentYear = Carbon::now()->year;
        $lastYear = $currentYear - 1;

        $currentRevenue = Payment::where('school_id', $schoolId)
            ->where('is_reversed', 0)
            ->whereYear('created_at', $currentYear)
            ->sum('amount');

        $lastYearRevenue = Payment::where('school_id', $schoolId)
            ->where('is_reversed', 0)
            ->whereYear('created_at', $lastYear)
            ->sum('amount');

        $growth = $lastYearRevenue > 0
            ? round((($currentRevenue - $lastYearRevenue) / $lastYearRevenue) * 100, 1)
            : null;

        return [
            'current_year' => $currentYear,
            'current_revenue' => (float) $currentRevenue,
            'last_year' => $lastYear,
            'last_year_revenue' => (float) $lastYearRevenue,
            'yoy_growth_percent' => $growth,
        ];
    }
}
