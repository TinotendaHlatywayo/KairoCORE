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
        'school_id', 'student_id', 'bed_id', 'room_id', 'academic_year_id',
        'status', 'allocated_at', 'expected_checkout_at', 'checked_out_at', 'notes',
    ];

    protected static function booted()
    {
        static::saved(function ($allocation) {
            // Direct lookup instead of $allocation->bed: lazy loading is
            // disabled in local (Model::shouldBeStrict) and the saved hook
            // must never trigger it.
            $status = in_array($allocation->status, ['active', 'completed', 'cancelled'])
                ? ($allocation->status === 'active' ? 'occupied' : 'vacant')
                : null;

            if ($status !== null && filled($allocation->bed_id)) {
                HostelBed::withoutGlobalScopes()->whereKey($allocation->bed_id)->update(['status' => $status]);
            }
        });
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(HostelBed::class, 'bed_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(HostelRoom::class, 'room_id');
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
