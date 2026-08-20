<?php

namespace Modules\Admissions\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Modules\Academics\Models\Course;

class Application extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'school_id',
        'application_number',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'parent_name',
        'parent_email',
        'parent_phone',
        'parent_relationship',
        'course_id',
        'applying_year',
        'applying_term',
        'applying_level',
        'transfer_letter_path',
        'transfer_letter_verified',
        'status',
        'photo_path',
        'documents_verified',
        'interview_date',
        'interview_notes',
        'interview_status',
        'decision_notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'interview_date' => 'date',
        'documents_verified' => 'boolean',
        'transfer_letter_verified' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function documents()
    {
        return $this->hasMany(ApplicationDocument::class);
    }

    public function getParentEmailAttribute($value)
    {
        return strtolower($value);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Level names that represent a school entry point. Applicants joining
     * outside these levels are expected to provide a verified transfer letter.
     */
    public const ENTRY_LEVELS = [
        'ecd a', 'ecd b', 'grade r', 'reception',
        'grade 1', 'form 1', 'form 5',
        'lower six', 'lower 6',
        'a-level', 'a level',
    ];

    public static function isEntryLevel(string $levelName): bool
    {
        $name = strtolower(trim($levelName));

        if (in_array($name, self::ENTRY_LEVELS, true)) {
            return true;
        }

        return str_starts_with($name, 'grade 1')
            || str_starts_with($name, 'form 1')
            || str_starts_with($name, 'form 5')
            || str_starts_with($name, 'lower six')
            || str_starts_with($name, 'lower 6');
    }
}
