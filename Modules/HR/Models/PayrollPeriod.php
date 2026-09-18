<?php

namespace Modules\HR\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Finance\Models\SchoolBankAccount;

class PayrollPeriod extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'bank_account_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function runs(): HasMany
    {
        return $this->hasMany(PayrollRun::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(SchoolBankAccount::class, 'bank_account_id');
    }
}
