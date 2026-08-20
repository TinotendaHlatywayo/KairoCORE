<?php

namespace Modules\HR\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryGradeHistory extends Model
{
    use BelongsToTenant;

    protected $table = 'salary_grade_history';

    protected $fillable = [
        'school_id',
        'employee_id',
        'previous_grade_id',
        'new_grade_id',
        'base_salary',
        'effective_date',
        'reason',
        'approved_by_id',
    ];

    protected $casts = [
        'base_salary' => 'decimal:4',
        'effective_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function previousGrade(): BelongsTo
    {
        return $this->belongsTo(SalaryGrade::class, 'previous_grade_id');
    }

    public function newGrade(): BelongsTo
    {
        return $this->belongsTo(SalaryGrade::class, 'new_grade_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }
}
