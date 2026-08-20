<?php

namespace Modules\HR\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'payslip_id',
        'code',
        'name',
        'type',
        'amount',
        'is_taxable',
        'is_recurring',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'is_taxable' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }
}
