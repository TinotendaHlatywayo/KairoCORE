<?php

namespace Modules\Academics\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Students\Models\Enrollment;

class StudentCompetency extends Model
{
    use BelongsToTenant;

    protected $table = 'student_competencies';

    protected $fillable = ['school_id', 'enrollment_id', 'skill_area', 'score', 'remark'];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }
}
