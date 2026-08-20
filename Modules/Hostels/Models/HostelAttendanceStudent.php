<?php

namespace Modules\Hostels\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Students\Models\Student;

class HostelAttendanceStudent extends Model
{
    use BelongsToTenant;

    protected $fillable = ['school_id', 'hostel_attendance_id', 'student_id', 'status', 'remarks', 'notified_parents_at'];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(HostelAttendance::class, 'hostel_attendance_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
