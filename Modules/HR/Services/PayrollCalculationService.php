<?php

namespace Modules\HR\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\ExpenseCategory;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\PayrollPeriod;
use Modules\HR\Models\PayrollRun;
use Modules\HR\Models\Payslip;
use Modules\HR\Models\PayslipItem;
use Modules\HR\Models\SalaryGrade;
use Modules\HR\Models\StaffLoan;

class PayrollCalculationService
{
    /**
     * Resolve a fixed or percentage-based allowance/deduction component.
     * Percentage components apply to the base salary or to the running gross
     * (total after allowances), depending on percentage_of.
     */
    protected function resolveComponentAmount(array $component, float $base, float $gross): float
    {
        $amount = (float) ($component['amount'] ?? 0);

        if (($component['type'] ?? 'fixed') === 'percentage') {
            $basis = ($component['percentage_of'] ?? null) === 'gross' ? $gross : $base;

            return round($basis * ($amount / 100), 4);
        }

        return $amount;
    }

    protected function deductionTypeOf(array $component): string
    {
        return ($component['deduction_type'] ?? 'tax') === 'loan' ? 'loan' : 'tax';
    }

    /**
     * Itemised grade ledger: base salary + all allowances (fixed and
     * percentage) + all grade deductions, mirroring exactly what a payslip
     * will contain and what the Salary Grade table displays.
     */
    public function gradeLedger(SalaryGrade $grade): array
    {
        $base = (float) $grade->base_salary;
        $allowances = [];
        $deductions = [];

        $standard = [
            ['code' => 'HOUSE', 'name' => 'Housing Allowance', 'amount' => (float) $grade->housing_allowance, 'is_taxable' => true],
            ['code' => 'TRANS', 'name' => 'Transport Allowance', 'amount' => (float) $grade->transport_allowance, 'is_taxable' => false],
            ['code' => 'DUTY', 'name' => 'Duty Allowance', 'amount' => (float) $grade->duty_allowance, 'is_taxable' => true],
        ];

        foreach ($standard as $allowance) {
            if ($allowance['amount'] > 0) {
                $allowances[] = $allowance;
            }
        }

        $gross = $base + array_sum(array_column($allowances, 'amount'));

        if (is_array($grade->custom_allowances)) {
            foreach ($grade->custom_allowances as $index => $component) {
                $amount = $this->resolveComponentAmount($component, $base, $gross);
                if ($amount > 0) {
                    $gross += $amount;
                    $allowances[] = [
                        'code' => strtoupper(Str::slug($component['name'] ?? 'CUSTOM_A'.($index + 1), '_')),
                        'name' => $component['name'] ?? 'Custom Allowance',
                        'amount' => $amount,
                        'is_taxable' => true,
                    ];
                }
            }
        }

        if (is_array($grade->custom_deductions)) {
            foreach ($grade->custom_deductions as $index => $component) {
                $amount = $this->resolveComponentAmount($component, $base, $gross);
                if ($amount > 0) {
                    $deductions[] = [
                        'code' => strtoupper(Str::slug($component['name'] ?? 'CUSTOM_D'.($index + 1), '_')),
                        'name' => $component['name'] ?? 'Custom Deduction',
                        'amount' => $amount,
                        'deduction_type' => $this->deductionTypeOf($component),
                    ];
                }
            }
        }

        $deductionsTotal = array_sum(array_column($deductions, 'amount'));

        return [
            'base_salary' => $base,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'gross' => round($gross, 4),
            'deductions_total' => round($deductionsTotal, 4),
            'net' => round($gross - $deductionsTotal, 4),
        ];
    }

    /**
     * STAGE 1: Calculate and generate draft payroll for a given period.
     */
    public function executeRun(PayrollPeriod $period, array $filters = []): PayrollRun
    {
        return DB::transaction(function () use ($period, $filters) {
            // Delete existing run and items for this period if recalculating
            $existingRun = PayrollRun::where('school_id', $period->school_id)
                ->where('payroll_period_id', $period->id)
                ->first();

            if ($existingRun) {
                $existingRun->delete();
            }

            // Create new operational run container
            $run = PayrollRun::create([
                'school_id' => $period->school_id,
                'payroll_period_id' => $period->id,
                'status' => 'calculated',
                'calculated_at' => Carbon::now(),
                'gross_total' => 0.0000,
                'deductions_total' => 0.0000,
                'net_total' => 0.0000,
            ]);

            $query = Employee::where('school_id', $period->school_id)
                ->where('status', 'active')
                ->with(['currentGrade']);

            if (! empty($filters['current_grade_id'])) {
                $query->where('current_grade_id', $filters['current_grade_id']);
            }
            if (! empty($filters['department'])) {
                $query->where('department', $filters['department']);
            }
            if (! empty($filters['employment_type'])) {
                $query->where('employment_type', $filters['employment_type']);
            }
            if (! empty($filters['designation'])) {
                $query->where('designation', $filters['designation']);
            }
            if (! empty($filters['gender'])) {
                $query->where('gender', $filters['gender']);
            }

            $employees = $query->get();

            $totalGross = 0.0000;
            $totalDeductions = 0.0000;
            $totalNet = 0.0000;

            foreach ($employees as $employee) {
                if (! $employee->currentGrade) {
                    continue;
                }

                $ledger = $this->gradeLedger($employee->currentGrade);
                $baseSalary = $ledger['base_salary'];

                // Calculate Unpaid Absences
                $unpaidLeaveDays = LeaveRequest::where('school_id', $period->school_id)
                    ->where('employee_id', $employee->id)
                    ->where('status', 'approved')
                    ->whereHas('leaveType', function ($query) {
                        $query->where('code', 'UNPAID');
                    })
                    ->where(function ($query) use ($period) {
                        $query->whereBetween('start_date', [$period->start_date, $period->end_date])
                            ->orWhereBetween('end_date', [$period->start_date, $period->end_date]);
                    })
                    ->get()
                    ->sum('total_days');

                $dailyRate = $baseSalary / 30.00;
                $unpaidDeduction = round($unpaidLeaveDays * $dailyRate, 4);

                $grossPay = $ledger['gross'];

                // Create Draft Payslip Header Record
                $payslip = Payslip::create([
                    'school_id' => $period->school_id,
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'base_salary' => $baseSalary,
                    'gross_pay' => $grossPay,
                    'total_deductions' => 0.0000,
                    'net_pay' => 0.0000,
                    'status' => 'calculated',
                    'payment_method' => 'Bank Transfer',
                ]);

                // Earnings Items
                $this->createPayslipItem($payslip, 'BASIC', 'Basic Salary', 'earning', $baseSalary, true);

                foreach ($ledger['allowances'] as $allowance) {
                    $this->createPayslipItem($payslip, $allowance['code'], $allowance['name'], 'earning', $allowance['amount'], $allowance['is_taxable']);
                }

                // Employee Individual Allowances (fixed amounts)
                if (is_array($employee->individual_allowances)) {
                    foreach ($employee->individual_allowances as $ia) {
                        $amt = (float) ($ia['amount'] ?? 0);
                        if ($amt > 0) {
                            $grossPay += $amt;
                            $this->createPayslipItem($payslip, strtoupper(Str::slug($ia['name'] ?? 'IND_ALL', '_')), $ia['name'], 'earning', $amt, true);
                        }
                    }
                }

                $payslip->update(['gross_pay' => $grossPay]);

                $calculatedDeductions = 0.0000;

                // Grade Deductions (tax or loan)
                foreach ($ledger['deductions'] as $deduction) {
                    $calculatedDeductions += $deduction['amount'];
                    $this->createPayslipItem($payslip, $deduction['code'], $deduction['name'], 'deduction', $deduction['amount'], false, $deduction['deduction_type']);
                }

                if ($unpaidDeduction > 0) {
                    $this->createPayslipItem($payslip, 'UNPAID_DED', 'Unpaid Leave', 'deduction', $unpaidDeduction, false, 'tax');
                    $calculatedDeductions += $unpaidDeduction;
                }

                // DRAFT LOAN AMORTIZATION: Calculate potential deduction without mutating database balances
                $activeLoan = StaffLoan::where('school_id', $period->school_id)
                    ->where('employee_id', $employee->id)
                    ->where('status', 'active')
                    ->where('balance_remaining', '>', 0)
                    ->first();

                if ($activeLoan) {
                    $monthly = $activeLoan->monthlyDeductionFor((float) $activeLoan->total_repayable);
                    $loanRecovery = min($monthly, (float) $activeLoan->balance_remaining);

                    if (($grossPay - $calculatedDeductions - $loanRecovery) >= 50.0000) {
                        $this->createPayslipItem($payslip, 'LOAN_REC', 'Loan Repayment Deductions', 'deduction', $loanRecovery, false, 'loan');
                        $calculatedDeductions += $loanRecovery;
                    }
                }

                $netPay = $grossPay - $calculatedDeductions;
                $payslip->update([
                    'total_deductions' => $calculatedDeductions,
                    'net_pay' => $netPay,
                ]);

                $totalGross += $grossPay;
                $totalDeductions += $calculatedDeductions;
                $totalNet += $netPay;
            }

            $run->update([
                'gross_total' => $totalGross,
                'deductions_total' => $totalDeductions,
                'net_total' => $totalNet,
            ]);

            $period->update(['status' => 'calculated']);

            return $run;
        });
    }

    /**
     * STAGE 2: Approve. Locks the period, debits the chosen bank account for
     * the salaries (net pay + tax-type deductions), and records the payroll
     * Expense so salaries surface in the financial statement and analytics.
     * Returns the payout summary for the UI.
     */
    public function approvePeriod(PayrollPeriod $period, ?int $bankAccountId = null): array
    {
        return DB::transaction(function () use ($period, $bankAccountId) {
            $schoolId = $period->school_id;

            $payslips = Payslip::where('school_id', $schoolId)
                ->whereHas('run', fn ($q) => $q->where('payroll_period_id', $period->id))
                ->with(['items'])
                ->get();

            $netTotal = round((float) $payslips->sum('net_pay'), 2);
            $taxDeductions = round((float) $payslips->flatMap->items->where('deduction_type', 'tax')->sum('amount'), 2);
            $outflow = round($netTotal + $taxDeductions, 2);

            $account = $bankAccountId
                ? SchoolBankAccount::where('school_id', $schoolId)->where('id', $bankAccountId)->first()
                : SchoolBankAccount::where('school_id', $schoolId)->where('is_default', true)->first();

            if (! $account) {
                $account = SchoolBankAccount::where('school_id', $schoolId)->first();

                if (! $account) {
                    return ['outflow' => $outflow, 'skipped' => true, 'reason' => 'no-bank-account'];
                }
            }

            $reference = 'EXP-PAYROLL-'.$period->id;
            $recorded = Expense::where('school_id', $schoolId)->where('reference_number', $reference)->exists();

            if ($outflow > 0 && ! $recorded) {
                $account->decrement('balance', $outflow);

                try {
                    $category = ExpenseCategory::firstOrCreate(
                        ['school_id' => $schoolId, 'name' => 'Payroll & Compensation'],
                        ['description' => __('Staff salaries and payroll disbursements')]
                    );

                    Expense::create([
                        'school_id' => $schoolId,
                        'expense_category_id' => $category->id,
                        'expense_name' => 'Payroll — '.$period->name,
                        'amount' => $outflow,
                        'expense_date' => now()->toDateString(),
                        'reference_number' => $reference,
                        'notes' => 'Disbursement of salaries for payroll period: '.$period->name,
                        'status' => 'paid',
                        'bank_account_id' => $account->id,
                    ]);
                } catch (\Throwable $e) {
                    // Fail gracefully if finance module tables unconfigured
                }
            }

            $period->update([
                'status' => 'approved',
                'bank_account_id' => $account->id,
            ]);
            $period->runs()->update(['status' => 'approved']);

            return [
                'outflow' => $outflow,
                'net_total' => $netTotal,
                'tax_deductions' => $taxDeductions,
                'bank_account' => $account->bank_name,
                'skipped' => false,
            ];
        });
    }

    /**
     * STAGE 3: Release & Pay. Finalizes and locks balances, and officially
     * deducts outstanding loans (crediting the loan's bank account).
     */
    public function releaseRun(PayrollPeriod $period): void
    {
        DB::transaction(function () use ($period) {
            $period->update(['status' => 'released']);

            $runs = PayrollRun::where('school_id', $period->school_id)
                ->where('payroll_period_id', $period->id)
                ->get();

            $totalLoanRecovered = 0.00;

            foreach ($runs as $run) {
                $run->update([
                    'status' => 'released',
                    'released_at' => Carbon::now(),
                ]);

                $payslips = Payslip::where('school_id', $period->school_id)
                    ->where('payroll_run_id', $run->id)
                    ->get();

                foreach ($payslips as $payslip) {
                    $payslip->update([
                        'status' => 'released',
                        'payment_date' => Carbon::now(),
                    ]);

                    // Check if this payslip contains a LOAN_REC item
                    $loanItem = PayslipItem::where('school_id', $period->school_id)
                        ->where('payslip_id', $payslip->id)
                        ->where('code', 'LOAN_REC')
                        ->first();

                    if ($loanItem) {
                        $activeLoan = StaffLoan::where('school_id', $period->school_id)
                            ->where('employee_id', $payslip->employee_id)
                            ->where('status', 'active')
                            ->first();

                        if ($activeLoan) {
                            $this->amortizeLoan($activeLoan, (float) $loanItem->amount, $period);
                            $totalLoanRecovered += (float) $loanItem->amount;
                        }
                    }
                }
            }
        });
    }

    /**
     * Apply one period's repayment to a loan and route the money back into the
     * loan's bank account. Reducing-balance loans accrue interest on the
     * remaining balance once per period.
     */
    protected function amortizeLoan(StaffLoan $loan, float $amount, PayrollPeriod $period): void
    {
        if ($loan->isReducingBalance()) {
            $interest = (float) $loan->interestForPeriod();

            if ((int) $loan->last_interest_payroll_period_id === (int) $period->id) {
                $newBalance = round((float) $loan->balance_remaining - $amount, 4);
            } else {
                $newBalance = round((float) $loan->balance_remaining + $interest - $amount, 4);
                $loan->last_interest_payroll_period_id = $period->id;
            }

            $loan->balance_remaining = max(0, $newBalance);
        } else {
            $loan->decrement('balance_remaining', $amount);
        }

        if ((float) $loan->balance_remaining <= 0) {
            $loan->balance_remaining = 0.0000;
            $loan->status = 'settled';
        }

        $loan->save();

        // Loan repayments return into the loan's bank account.
        $schoolId = $loan->school_id;
        $accountId = $loan->bank_account_id ?? SchoolBankAccount::where('school_id', $schoolId)->where('is_default', true)->value('id');

        if ($accountId && $amount > 0) {
            SchoolBankAccount::where('school_id', $schoolId)->where('id', $accountId)->increment('balance', $amount);
        }
    }

    /**
     * Total salary expense recorded for a school (optionally for an account or
     * date range), used by the financial statement and analytics.
     */
    public function payrollExpenseTotal(int $schoolId, ?string $startDate = null, ?string $endDate = null, ?int $bankAccountId = null): float
    {
        $category = ExpenseCategory::where('school_id', $schoolId)->where('name', 'Payroll & Compensation')->first();

        if (! $category) {
            return 0.00;
        }

        $query = Expense::where('school_id', $schoolId)->where('expense_category_id', $category->id);

        if ($startDate) {
            $query->where('expense_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('expense_date', '<=', $endDate);
        }

        if ($bankAccountId) {
            $query->where('bank_account_id', $bankAccountId);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Total salary expense of a single payroll period: the recorded Expense
     * (net pay + tax-type deductions) when approved, otherwise the computed
     * payout the period will produce.
     */
    public function periodSalaryExpense(PayrollPeriod $period): float
    {
        $recorded = Expense::where('school_id', $period->school_id)
            ->where('reference_number', 'EXP-PAYROLL-'.$period->id)
            ->first();

        if ($recorded) {
            return (float) $recorded->amount;
        }

        $payslips = Payslip::where('school_id', $period->school_id)
            ->whereHas('run', fn ($q) => $q->where('payroll_period_id', $period->id))
            ->with(['items'])
            ->get();

        $tax = (float) $payslips->flatMap->items->where('deduction_type', 'tax')->sum('amount');

        return round((float) $payslips->sum('net_pay') + $tax, 2);
    }

    private function createPayslipItem(Payslip $payslip, string $code, string $name, string $type, float $amount, bool $isTaxable, ?string $deductionType = null): void
    {
        if ($amount <= 0.00) {
            return;
        }

        PayslipItem::create([
            'school_id' => $payslip->school_id,
            'payslip_id' => $payslip->id,
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'deduction_type' => $deductionType,
            'amount' => $amount,
            'is_taxable' => $isTaxable,
            'is_recurring' => true,
        ]);
    }
}
