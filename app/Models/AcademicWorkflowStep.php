<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AcademicWorkflowStep extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'step_key',
        'title',
        'description',
        'status',
        'step_order',
        'depends_on',
        'completed_at',
        'completed_by',
        'skipped_until',
        'config',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'skipped_until' => 'datetime',
        'config' => 'array',
    ];

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
