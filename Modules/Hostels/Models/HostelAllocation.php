<?php

namespace Modules\Hostels\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Academics\Models\AcademicYear;
use Modules\Students\Models\Student;

class HostelAllocation extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'school_id', 'student_id', 'bed_id', 'academic_year_id',
        'status', 'allocated_at', 'expected_checkout_at', 'checked_out_at', 'notes',
    ];

    protected static function booted()
    {
        static::saved(function ($allocation) {
            if ($allocation->status === 'active') {
                $allocation->bed->update(['status' => 'occupied']);
            } elseif (in_array($allocation->status, ['completed', 'cancelled'])) {
                $allocation->bed->update(['status' => 'vacant']);
            }
        });
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(HostelBed::class, 'bed_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }
}
