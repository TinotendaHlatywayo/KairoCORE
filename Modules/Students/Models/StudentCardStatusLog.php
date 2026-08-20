<?php

namespace Modules\Students\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class StudentCardStatusLog extends Model
{
    use BelongsToTenant;

    protected $table = 'student_card_status_logs';

    protected $fillable = [
        'school_id',
        'student_id',
        'action',
        'reason',
        'processed_by_id',
    ];
}
