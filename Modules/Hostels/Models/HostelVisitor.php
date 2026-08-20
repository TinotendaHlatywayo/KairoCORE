<?php

namespace Modules\Hostels\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Students\Models\Student;

class HostelVisitor extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id', 'student_id', 'visitor_name', 'national_id', 'phone_number',
        'relationship', 'purpose', 'arrival_time', 'departure_time',
        'vehicle_registration', 'items_brought', 'items_taken', 'badge_number', 'is_blacklisted',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
