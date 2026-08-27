<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class GamificationAchievement extends Model
{
    use BelongsToTenant;

    protected $table = 'gamification_achievements';

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'icon',
        'criteria',
        'xp_reward',
        'achievement_type',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'criteria' => 'array',
        'xp_reward' => 'integer',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function learnerAchievements(): HasMany
    {
        return $this->hasMany(LearnerAchievement::class, 'gamification_achievement_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function earnedByStudent(int $studentId): bool
    {
        return $this->learnerAchievements()->where('student_id', $studentId)->exists();
    }
}
