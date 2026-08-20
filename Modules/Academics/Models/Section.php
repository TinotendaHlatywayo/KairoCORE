<?php

namespace Modules\Academics\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;

class Section extends Model
{
    use BelongsToTenant; // Removed SoftDeletes to resolve the column not found query error

    protected $table = 'sections';

    protected $fillable = [
        'school_id',
        'course_id',
        'name',
        'capacity',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students()
    {
        return $this->hasManyThrough(Student::class, Enrollment::class, 'section_id', 'id', 'id', 'student_id');
    }

    /**
     * Null-safe accessor to prevent crashing if course is missing or orphaned
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        $courseName = $this->course?->name ?? 'Unknown Grade';

        return "{$courseName} {$this->name}";
    }
}
