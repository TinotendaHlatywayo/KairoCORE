<?php

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\RevenueStream;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\HR\Services\PayrollCalculationService;

class FinancialAnalyticsEngine
{
    /**
     * Compute Executive Financial Summary for a given school tenant.
     */
    public function getSummary(int $schoolId, ?int $bankAccountId = null): array
    {
        $totalRevenue = Payment::where('school_id', $schoolId)
            ->where('is_reversed', 0)
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId))
            ->sum('amount')
            + $this->getRevenueStreamTotal($schoolId, $bankAccountId);
        $totalExpenses = Expense::where('school_id', $schoolId)
            ->whereIn('status', ['approved', 'paid'])
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId))
            ->sum('amount');
        $netSurplus = $totalRevenue - $totalExpenses;

        $outstandingFees = Invoice::where('school_id', $schoolId)
            ->where('status', '!=', 'void')
            ->sum('balance_amount');

        // Payables = approved but not yet paid expenses (real obligations).
        $accountsPayable = Expense::where('school_id', $schoolId)
            ->where('status', 'approved')
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId))
            ->sum('amount');

        // Cash position aligned with the revenue/expense figures already shown,
        // rather than an inconsistent proxy of invoice credits.
        $cashPosition = max(0, $netSurplus);

        $totalSalaries = app(PayrollCalculationService::class)
            ->payrollExpenseTotal($schoolId, null, null, $bankAccountId);

        return [
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'total_salaries' => $totalSalaries,
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
     *
     * Payment refunds are excluded here (they are a reduction, not a revenue
     * source, and negative slices would break the donut chart); non-fee income
     * is added as an "Other Income" slice from the revenue streams.
     */
    public function getRevenueBreakdown(int $schoolId, ?int $bankAccountId = null): array
    {
        // Derive each invoice's fee category via a subquery so a payment is never
        // multiplied by the number of invoice items on its invoice.
        $invoiceCategories = DB::table('invoice_items')
            ->select('invoice_items.invoice_id', DB::raw('MAX(fee_categories.name) as category'))
            ->join('fee_structures', 'invoice_items.fee_structure_id', '=', 'fee_structures.id')
            ->leftJoin('fee_categories', 'fee_structures.fee_category_id', '=', 'fee_categories.id')
            ->groupBy('invoice_items.invoice_id');

        $breakdown = DB::table('payments')
            ->join('invoices', 'payments.invoice_id', '=', 'invoices.id')
            ->joinSub($invoiceCategories, 'inv_cat', 'invoices.id', '=', 'inv_cat.invoice_id')
            ->where('payments.school_id', $schoolId)
            ->where('payments.is_reversed', 0)
            ->where('payments.is_refund', false)
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId, 'payments.bank_account_id'))
            ->select('inv_cat.category as category', DB::raw('SUM(payments.amount) as total'))
            ->groupBy('inv_cat.category')
            ->pluck('total', 'category')
            ->toArray();

        $streamTotal = $this->getRevenueStreamTotal($schoolId, $bankAccountId);
        if ($streamTotal > 0) {
            $breakdown['Other Income'] = $streamTotal;
        }

        return $breakdown;
    }

    /**
     * Total expected income from revenue streams (non-fee income: bus hire,
     * uniform sales, rentals, ...). Active streams only, bank-account scoped.
     */
    public function getRevenueStreamTotal(int $schoolId, ?int $bankAccountId = null): float
    {
        return (float) RevenueStream::where('school_id', $schoolId)
            ->where('is_active', true)
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId, 'account_id'))
            ->sum('default_amount');
    }

    /**
     * Individual revenue streams recorded within a date range, for the audited
     * Financial Statement line items (name, amount, category, bank account).
     */
    public function getRevenueStreamsForPeriod(int $schoolId, ?string $startDate, ?string $endDate, ?int $bankAccountId = null): array
    {
        return DB::table('revenue_streams')
            ->leftJoin('revenue_categories', 'revenue_streams.revenue_category_id', '=', 'revenue_categories.id')
            ->leftJoin('school_bank_accounts', 'revenue_streams.account_id', '=', 'school_bank_accounts.id')
            ->where('revenue_streams.school_id', $schoolId)
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId, 'revenue_streams.account_id'))
            ->when($startDate, fn ($q) => $q->where('revenue_streams.created_at', '>=', $startDate.' 00:00:00'))
            ->when($endDate, fn ($q) => $q->where('revenue_streams.created_at', '<=', $endDate.' 23:59:59'))
            ->select(
                'revenue_streams.id',
                'revenue_streams.name',
                'revenue_streams.default_amount',
                'revenue_streams.created_at',
                'revenue_categories.name as category',
                'school_bank_accounts.bank_name as bank'
            )
            ->orderByDesc('revenue_streams.created_at')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'amount' => (float) $row->default_amount,
                'category' => $row->category ?? __('Other Income'),
                'bank' => $row->bank,
                'date' => $row->created_at ? Carbon::parse($row->created_at)->format('Y-m-d') : null,
            ])
            ->toArray();
    }

    /**
     * Individual refunds issued within a date range (name/reference, amount,
     * date) for the audited Financial Statement line items.
     */
    public function getRefundsForPeriod(int $schoolId, ?string $startDate, ?string $endDate, ?int $bankAccountId = null): array
    {
        return Payment::where('school_id', $schoolId)
            ->where('is_refund', true)
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId))
            ->when($startDate, fn ($q) => $q->where('created_at', '>=', $startDate.' 00:00:00'))
            ->when($endDate, fn ($q) => $q->where('created_at', '<=', $endDate.' 23:59:59'))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Payment $payment) => [
                'reference' => $payment->reference_number ?: $payment->receipt_number,
                'amount' => abs((float) $payment->amount),
                'date' => $payment->created_at->format('Y-m-d'),
                'account' => $payment->bankAccount?->bank_name,
            ])
            ->toArray();
    }

    /**
     * Individual expenses (name, category, date, amount) within a date range,
     * for the audited Financial Statement line items.
     */
    public function getExpensesForPeriod(int $schoolId, ?string $startDate, ?string $endDate, ?int $bankAccountId = null): array
    {
        return DB::table('expenses')
            ->leftJoin('expense_types', 'expenses.expense_type_id', '=', 'expense_types.id')
            ->leftJoin('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->leftJoin('expense_categories as type_categories', 'expense_types.expense_category_id', '=', 'type_categories.id')
            ->where('expenses.school_id', $schoolId)
            ->whereIn('expenses.status', ['approved', 'paid'])
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId, 'expenses.bank_account_id'))
            ->when($startDate, fn ($q) => $q->where('expenses.expense_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->where('expenses.expense_date', '<=', $endDate))
            ->select(
                'expenses.reference_number',
                'expenses.expense_name',
                'expenses.amount',
                'expenses.expense_date',
                'expenses.status',
                DB::raw('COALESCE(expense_categories.name, type_categories.name) as category')
            )
            ->orderByDesc('expenses.expense_date')
            ->get()
            ->map(fn ($row) => [
                'reference' => $row->reference_number,
                'name' => $row->expense_name ?: ($row->reference_number ?? __('Expense')),
                'amount' => (float) $row->amount,
                'category' => $row->category ?? __('Uncategorised'),
                'date' => $row->expense_date,
                'status' => $row->status,
            ])
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
    public function getRevenueExpenseTrend(int $schoolId, int $months = 12, ?int $bankAccountId = null): array
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
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();

        $expenseData = DB::table('expenses')
            ->select(
                DB::raw('DATE_FORMAT(expenses.expense_date, "%Y-%m") as period'),
                DB::raw('SUM(expenses.amount) as total')
            )
            ->where('expenses.school_id', $schoolId)
            ->whereIn('expenses.status', ['approved', 'paid'])
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId, 'expenses.bank_account_id'))
            ->whereBetween('expenses.expense_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();

        $streamData = $this->revenueStreamsMonthly($schoolId, $startDate, $endDate, $bankAccountId);

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
            $rev = ($revenueData[$key] ?? 0) + ($streamData[$key] ?? 0);
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
    public function getCashFlowTimeline(int $schoolId, int $months = 12, ?int $bankAccountId = null): array
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
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();

        $cashOut = DB::table('expenses')
            ->select(
                DB::raw('DATE_FORMAT(expenses.expense_date, "%Y-%m") as period'),
                DB::raw('SUM(expenses.amount) as total')
            )
            ->where('expenses.school_id', $schoolId)
            ->whereIn('expenses.status', ['approved', 'paid'])
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId, 'expenses.bank_account_id'))
            ->whereBetween('expenses.expense_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();

        $streamData = $this->revenueStreamsMonthly($schoolId, $startDate, $endDate, $bankAccountId);

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
            $in = (float) ($cashIn[$key] ?? 0) + (float) ($streamData[$key] ?? 0);
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
    public function getCollectionRateTrend(int $schoolId, int $months = 12, ?int $bankAccountId = null): array
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
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId, 'payments.bank_account_id'))
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
     *
     * Categories are read from the expense's direct category first, falling back
     * to the category of its expense type, so both form-recorded expenses
     * (direct category) and legacy type-classified expenses are included.
     */
    public function getExpenseBreakdown(int $schoolId, ?int $bankAccountId = null): array
    {
        return DB::table('expenses')
            ->leftJoin('expense_types', 'expenses.expense_type_id', '=', 'expense_types.id')
            ->leftJoin('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->leftJoin('expense_categories as type_categories', 'expense_types.expense_category_id', '=', 'type_categories.id')
            ->where('expenses.school_id', $schoolId)
            ->whereIn('expenses.status', ['approved', 'paid'])
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId, 'expenses.bank_account_id'))
            ->select(
                DB::raw('COALESCE(expense_categories.name, type_categories.name) as category'),
                DB::raw('SUM(expenses.amount) as total')
            )
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();
    }

    /**
     * Get Payment Method Breakdown.
     */
    public function getPaymentMethodBreakdown(int $schoolId, ?int $bankAccountId = null): array
    {
        return DB::table('payments')
            ->where('school_id', $schoolId)
            ->where('is_reversed', 0)
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId))
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
    public function getExpenseRegister(int $schoolId, ?int $bankAccountId = null): array
    {
        return DB::table('expenses')
            ->leftJoin('expense_types', 'expenses.expense_type_id', '=', 'expense_types.id')
            ->leftJoin('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->leftJoin('expense_categories as type_categories', 'expense_types.expense_category_id', '=', 'type_categories.id')
            ->leftJoin('suppliers', 'expenses.supplier_id', '=', 'suppliers.id')
            ->leftJoin('users', 'expenses.user_id', '=', 'users.id')
            ->where('expenses.school_id', $schoolId)
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId, 'expenses.bank_account_id'))
            ->select(
                'expenses.reference_number',
                DB::raw('COALESCE(expense_categories.name, type_categories.name) as category'),
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
    public function getRevenueForecast(int $schoolId, int $months = 6, ?int $bankAccountId = null): array
    {
        $trend = $this->getRevenueExpenseTrend($schoolId, 12, $bankAccountId);
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
    public function getYoYComparison(int $schoolId, ?int $bankAccountId = null): array
    {
        $currentYear = Carbon::now()->year;
        $lastYear = $currentYear - 1;

        $currentRevenue = Payment::where('school_id', $schoolId)
            ->where('is_reversed', 0)
            ->whereYear('created_at', $currentYear)
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId))
            ->sum('amount')
            + $this->revenueStreamsYearly($schoolId, $currentYear, $bankAccountId);

        $lastYearRevenue = Payment::where('school_id', $schoolId)
            ->where('is_reversed', 0)
            ->whereYear('created_at', $lastYear)
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId))
            ->sum('amount')
            + $this->revenueStreamsYearly($schoolId, $lastYear, $bankAccountId);

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

    /**
     * Monthly revenue stream income (grouped by the month each stream was
     * created) within a range, bank-account scoped when requested.
     */
    private function revenueStreamsMonthly(int $schoolId, Carbon $startDate, Carbon $endDate, ?int $bankAccountId = null): array
    {
        return DB::table('revenue_streams')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as period'),
                DB::raw('SUM(default_amount) as total')
            )
            ->where('school_id', $schoolId)
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId, 'account_id'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();
    }

    /**
     * Total revenue stream income recorded within a calendar year, bank-account
     * scoped when requested.
     */
    private function revenueStreamsYearly(int $schoolId, int $year, ?int $bankAccountId = null): float
    {
        return (float) DB::table('revenue_streams')
            ->where('school_id', $schoolId)
            ->when($bankAccountId, SchoolBankAccount::filterClosure($bankAccountId, $schoolId, 'account_id'))
            ->whereYear('created_at', $year)
            ->sum('default_amount');
    }
}
