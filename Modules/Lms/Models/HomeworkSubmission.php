<?php

namespace Modules\Lms\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Students\Models\Student;

class HomeworkSubmission extends Model
{
    use BelongsToTenant;

    protected $table = 'homework_submissions';

    protected $fillable = [
        'school_id',
        'homework_id',
        'student_id',
        'file_path',
        'grade_obtained',
        'teacher_feedback',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'grade_obtained' => 'decimal:2',
    ];

    public function homework()
    {
        return $this->belongsTo(Homework::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
