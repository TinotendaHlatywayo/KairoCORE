<?php

namespace Modules\Academics\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Students\Models\Enrollment;

class MarkRecord extends Model
{
    use BelongsToTenant;

    protected $table = 'mark_records';

    protected $fillable = [
        'school_id',
        'enrollment_id',
        'subject_id',
        'subject_paper_id',
        'bot_mark',
        'mot_mark',
        'eot_mark',
        'c1_mark',
        'c2_mark',
        'c3_mark',
        'teacher_initials',
    ];

    protected $casts = [
        'bot_mark' => 'decimal:2',
        'mot_mark' => 'decimal:2',
        'eot_mark' => 'decimal:2',
        'c1_mark' => 'decimal:2',
        'c2_mark' => 'decimal:2',
        'c3_mark' => 'decimal:2',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function paper()
    {
        return $this->belongsTo(SubjectPaper::class, 'subject_paper_id');
    }
}
