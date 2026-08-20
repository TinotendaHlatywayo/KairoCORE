<?php

namespace Modules\HR\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'payroll_run_id',
        'employee_id',
        'base_salary',
        'gross_pay',
        'total_deductions',
        'net_pay',
        'status',
        'payment_method',
        'payment_date',
        'transaction_reference',
        'integrity_hash',
        'qr_token',
    ];

    protected $casts = [
        'base_salary' => 'decimal:4',
        'gross_pay' => 'decimal:4',
        'total_deductions' => 'decimal:4',
        'net_pay' => 'decimal:4',
        'payment_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayslipItem::class);
    }

    public static function boot()
    {
        parent::boot();

        static::saving(function ($payslip) {
            // Generate standard integrity SHA-256 validation token
            $payload = "{$payslip->school_id}-{$payslip->employee_id}-{$payslip->base_salary}-{$payslip->gross_pay}-{$payslip->total_deductions}-{$payslip->net_pay}";
            $payslip->integrity_hash = hash_hmac('sha256', $payload, config('app.key'));
            $payslip->qr_token = md5($payslip->integrity_hash);
        });
    }
}
