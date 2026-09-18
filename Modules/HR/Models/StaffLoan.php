<?php

namespace Modules\HR\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Finance\Models\SchoolBankAccount;

class StaffLoan extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'employee_id',
        'loan_type',
        'loan_type_other',
        'principal_amount',
        'interest_rate',
        'interest_type',
        'repayment_method',
        'total_repayable',
        'balance_remaining',
        'monthly_deduction',
        'monthly_deduction_type',
        'bank_account_id',
        'last_interest_payroll_period_id',
        'status',
        'funded_at',
        'approved_by_id',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:4',
        'interest_rate' => 'decimal:4',
        'total_repayable' => 'decimal:4',
        'balance_remaining' => 'decimal:4',
        'monthly_deduction' => 'decimal:4',
        'funded_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(SchoolBankAccount::class, 'bank_account_id');
    }

    /**
     * The amount recovered from the salary each payroll period: either the
     * fixed monthly amount or a percentage of the total payable.
     */
    public function monthlyDeductionFor(float $totalRepayable): float
    {
        if ($this->monthly_deduction_type === 'percentage' && (float) $this->monthly_deduction > 0) {
            return round($totalRepayable * ((float) $this->monthly_deduction / 100), 4);
        }

        return (float) $this->monthly_deduction;
    }

    /**
     * Interest charged for one payroll period. Reducing-balance loans apply the
     * rate to the amount still owed; fixed loans keep a constant total
     * repayable so no extra interest accrues.
     */
    public function interestForPeriod(): float
    {
        if ($this->repayment_method !== 'reducing_balance') {
            return 0.00;
        }

        return round((float) $this->balance_remaining * ((float) $this->interest_rate / 100), 4);
    }

    public function isReducingBalance(): bool
    {
        return $this->repayment_method === 'reducing_balance';
    }
}
