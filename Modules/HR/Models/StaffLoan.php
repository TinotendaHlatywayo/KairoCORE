<?php

namespace Modules\HR\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffLoan extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'employee_id',
        'loan_type',
        'principal_amount',
        'balance_remaining',
        'monthly_deduction',
        'status',
        'approved_by_id',
    ];

    protected $casts = [
        'principal_amount' => 'decimal:4',
        'balance_remaining' => 'decimal:4',
        'monthly_deduction' => 'decimal:4',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
