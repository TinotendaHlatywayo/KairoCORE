<?php

namespace Modules\HR\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryGrade extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'name',
        'base_salary',
        'hourly_rate',
        'housing_allowance',
        'transport_allowance',
        'duty_allowance',
        'overtime_eligible',
    ];

    protected $casts = [
        'base_salary' => 'decimal:4',
        'hourly_rate' => 'decimal:4',
        'housing_allowance' => 'decimal:4',
        'transport_allowance' => 'decimal:4',
        'duty_allowance' => 'decimal:4',
        'overtime_eligible' => 'boolean',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'current_grade_id');
    }
}
