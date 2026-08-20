<?php

namespace Modules\Clinic\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Students\Models\Student;

class StudentMedicalRecord extends Model
{
    use BelongsToTenant;

    protected $fillable = ['school_id', 'student_id', 'blood_group', 'allergies', 'chronic_conditions', 'immunization_history', 'regular_medications'];

    protected $casts = [
        'immunization_history' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
