<?php

namespace Modules\HR\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRun extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'payroll_period_id',
        'status',
        'calculated_at',
        'approved_at',
        'released_at',
        'gross_total',
        'deductions_total',
        'net_total',
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
        'approved_at' => 'datetime',
        'released_at' => 'datetime',
        'gross_total' => 'decimal:4',
        'deductions_total' => 'decimal:4',
        'net_total' => 'decimal:4',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class, 'payroll_run_id');
    }
}
