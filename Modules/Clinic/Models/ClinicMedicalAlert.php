<?php

namespace Modules\Clinic\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Students\Models\Student;

class ClinicMedicalAlert extends Model
{
    use BelongsToTenant;

    protected $fillable = ['school_id', 'student_id', 'alert_level', 'message', 'expires_at', 'is_active'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
