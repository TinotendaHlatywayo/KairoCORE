<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Modules\Students\Models\Student;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceAuditLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'user_id',
        'student_id',
        'action',
        'entity_type',
        'entity_id',
        'before',
        'after',
        'ip_address',
        'memo',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'entity_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}