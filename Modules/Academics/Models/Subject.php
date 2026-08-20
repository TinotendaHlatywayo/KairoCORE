<?php

namespace Modules\Academics\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'type',
        'credit_weight',
        'is_elective',
        'workflow_status',
    ];

    protected $casts = [
        'credit_weight' => 'decimal:2',
        'is_elective' => 'boolean',
    ];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_subject', 'subject_id', 'course_id');
    }
}
