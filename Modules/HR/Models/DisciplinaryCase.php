<?php

namespace Modules\HR\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplinaryCase extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'employee_id',
        'offense',
        'incident_date',
        'status',
        'severity',
        'resolution_notes',
        'action_taken_by_id',
    ];

    protected $casts = [
        'incident_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function actionTakenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'action_taken_by_id');
    }
}
