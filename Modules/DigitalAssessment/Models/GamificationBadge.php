<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class GamificationBadge extends Model
{
    use BelongsToTenant;

    protected $table = 'gamification_badges';

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'icon',
        'criteria',
        'xp_reward',
        'is_active',
        'is_system',
        'visibility',
    ];

    protected $casts = [
        'criteria' => 'array',
        'xp_reward' => 'integer',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function learnerBadges(): HasMany
    {
        return $this->hasMany(LearnerBadge::class, 'gamification_badge_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function earnedByStudent(int $studentId): bool
    {
        return $this->learnerBadges()->where('student_id', $studentId)->exists();
    }
}
