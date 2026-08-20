<?php

namespace Modules\Clinic\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Students\Models\Student;

class ClinicVisit extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id', 'student_id', 'recorded_by_user_id', 'visit_time',
        'departure_time', 'symptoms', 'diagnosis', 'treatment_given',
        'temperature_celsius', 'blood_pressure', 'status', 'referral_destination',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(ClinicPrescription::class, 'clinic_visit_id');
    }
}
