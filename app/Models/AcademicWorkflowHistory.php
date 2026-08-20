<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicWorkflowHistory extends Model
{
    protected $table = 'academic_workflow_history';

    protected $fillable = [
        'school_id',
        'entity_type',
        'entity_id',
        'action',
        'workflow_step',
        'old_values',
        'new_values',
        'reason',
        'browser',
        'ip_address',
        'user_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(
        int $schoolId,
        string $entityType,
        int $entityId,
        string $action,
        array $oldValues = [],
        array $newValues = [],
        ?string $reason = null,
    ): self {
        return static::create([
            'school_id' => $schoolId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'reason' => $reason,
            'browser' => request()->userAgent(),
            'ip_address' => request()->ip(),
            'user_id' => auth()->id(),
        ]);
    }
}
