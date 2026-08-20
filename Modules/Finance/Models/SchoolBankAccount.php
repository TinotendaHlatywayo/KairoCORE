<?php

namespace Modules\Finance\Models;

use App\Models\School;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolBankAccount extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'bank_name',
        'account_name',
        'account_number',
        'branch_code',
        'swift_code',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
