<?php

namespace Modules\Hostels\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Students\Models\Student;

class StudentHostelProfile extends Model
{
    use BelongsToTenant;

    protected $fillable = ['school_id', 'student_id', 'medical_conditions', 'dietary_restrictions', 'emergency_contacts', 'laundry_number', 'qr_pass_token'];

    protected static function booted()
    {
        static::creating(function ($profile) {
            $profile->qr_pass_token = 'PASS-'.strtoupper(bin2hex(random_bytes(6)));
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
