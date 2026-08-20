<?php

namespace Modules\HR\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAsset extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'employee_id',
        'asset_name',
        'serial_number',
        'issued_date',
        'returned_date',
        'status',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'returned_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
