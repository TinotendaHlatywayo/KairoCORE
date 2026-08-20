<?php

namespace Modules\Lms\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;

class Homework extends Model
{
    use BelongsToTenant;

    protected $table = 'homeworks';

    protected $fillable = [
        'school_id',
        'section_id',
        'subject_id',
        'title',
        'description',
        'file_path',
        'youtube_url',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function submissions()
    {
        return $this->hasMany(HomeworkSubmission::class);
    }

    /**
     * Submission status from the perspective of the currently-loading student.
     *
     * The resource eager-loads the "submissions" relation already scoped to
     * the logged-in student, so a non-empty relation means "I have submitted".
     */
    public function getSubmissionStatusAttribute(): string
    {
        if ($this->relationLoaded('submissions') && $this->submissions->isNotEmpty()) {
            return 'submitted';
        }

        if ($this->due_date && $this->due_date->lt(now()->startOfDay())) {
            return 'overdue';
        }

        return 'pending';
    }
}
