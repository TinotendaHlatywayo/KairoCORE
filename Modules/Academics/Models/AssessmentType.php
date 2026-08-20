<?php

namespace Modules\Academics\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AssessmentType extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'term_id',
        'name',
        'max_mark',
        'weight_percentage',
        'subject_id',
        'course_id',
        'section_id',
        'created_by_id',
    ];

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}
