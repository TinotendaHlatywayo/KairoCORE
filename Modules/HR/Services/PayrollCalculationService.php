<?php

namespace Modules\HR\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\HR\Models\Employee;
use Modules\HR\Models\LeaveRequest;
use Modules\HR\Models\PayrollPeriod;
use Modules\HR\Models\PayrollRun;
use Modules\HR\Models\Payslip;
use Modules\HR\Models\PayslipItem;
use Modules\HR\Models\StaffLoan;

class PayrollCalculationService
{
    /**
     * STAGE 1: Calculate and generate draft payroll for a given period.
     */
    public function executeRun(PayrollPeriod $period): PayrollRun
    {
        return DB::transaction(function () use ($period) {
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

            $employees = Employee::where('school_id', $period->school_id)
                ->where('status', 'active')
                ->with(['currentGrade'])
                ->get();

            $totalGross = 0.0000;
            $totalDeductions = 0.0000;
            $totalNet = 0.0000;

            foreach ($employees as $employee) {
                if (! $employee->currentGrade) {
                    continue;
                }

                $grade = $employee->currentGrade;
                $baseSalary = $grade->base_salary;

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

                // Resolve Standard Allowances
                $housing = $grade->housing_allowance;
                $transport = $grade->transport_allowance;
                $duty = $grade->duty_allowance;

                $grossPay = $baseSalary + $housing + $transport + $duty;

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

                // Create Earnings Items
                $this->createPayslipItem($payslip, 'BASIC', 'Basic Salary', 'earning', $baseSalary, true);
                $this->createPayslipItem($payslip, 'HOUSE', 'Housing Allowance', 'earning', $housing, true);
                $this->createPayslipItem($payslip, 'TRANS', 'Transport Allowance', 'earning', $transport, false);
                $this->createPayslipItem($payslip, 'DUTY', 'Duty Allowance', 'earning', $duty, true);

                $calculatedDeductions = 0.0000;

                if ($unpaidDeduction > 0) {
                    $this->createPayslipItem($payslip, 'UNPAID_DED', 'Unpaid Leave', 'deduction', $unpaidDeduction, false);
                    $calculatedDeductions += $unpaidDeduction;
                }

                $pension = round($baseSalary * 0.045, 4);
                $this->createPayslipItem($payslip, 'PENSION', 'National Pension', 'deduction', $pension, false);
                $calculatedDeductions += $pension;

                $taxableIncome = $grossPay - $pension;
                $paye = 0.0000;
                if ($taxableIncome > 350.0000) {
                    $paye = round(($taxableIncome - 350.0000) * 0.20, 4);
                }

                if ($paye > 0) {
                    $this->createPayslipItem($payslip, 'PAYE', 'Income Tax (PAYE)', 'deduction', $paye, false);
                    $calculatedDeductions += $paye;
                }

                // DRAFT LOAN AMORTIZATION: Calculate potential deduction without mutating database balances
                $activeLoan = StaffLoan::where('school_id', $period->school_id)
                    ->where('employee_id', $employee->id)
                    ->where('status', 'active')
                    ->where('balance_remaining', '>', 0)
                    ->first();

                if ($activeLoan) {
                    $loanRecovery = min($activeLoan->monthly_deduction, $activeLoan->balance_remaining);

                    if (($grossPay - $calculatedDeductions - $loanRecovery) >= 50.0000) {
                        $this->createPayslipItem($payslip, 'LOAN_REC', 'Loan Repayment Deductions', 'deduction', $loanRecovery, false);
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
     * STAGE 3: Release & Pay. Finalizes and locks balances, and officially deducts outstanding loans.
     */
    public function releaseRun(PayrollPeriod $period): void
    {
        DB::transaction(function () use ($period) {
            $period->update(['status' => 'released']);

            $runs = PayrollRun::where('school_id', $period->school_id)
                ->where('payroll_period_id', $period->id)
                ->get();

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
                        // Find the employee's active loan
                        $activeLoan = StaffLoan::where('school_id', $period->school_id)
                            ->where('employee_id', $payslip->employee_id)
                            ->where('status', 'active')
                            ->first();

                        if ($activeLoan) {
                            // Deduct the calculated loan recovery amount from outstanding balance
                            $activeLoan->decrement('balance_remaining', $loanItem->amount);

                            // If the loan is fully paid off, update its status
                            if ($activeLoan->balance_remaining <= 0) {
                                $activeLoan->update([
                                    'balance_remaining' => 0.0000,
                                    'status' => 'settled',
                                ]);
                            }
                        }
                    }
                }
            }
        });
    }

    private function createPayslipItem(Payslip $payslip, string $code, string $name, string $type, float $amount, bool $isTaxable): void
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
            'amount' => $amount,
            'is_taxable' => $isTaxable,
            'is_recurring' => true,
        ]);
    }
}
