<?php

namespace Modules\DigitalAssessment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class GamificationSettings extends Model
{
    use BelongsToTenant;

    protected $table = 'gamification_settings';

    protected $fillable = [
        'school_id',
        'xp_enabled',
        'badges_enabled',
        'achievements_enabled',
        'streaks_enabled',
        'challenges_enabled',
        'leaderboards_enabled',
        'xp_per_assessment_complete',
        'xp_per_improvement',
        'xp_per_streak_day',
        'xp_per_topic_mastery',
        'xp_per_challenge_complete',
        'streak_qualifying_activities',
        'level_config',
        'leaderboard_scope',
        'leaderboard_anonymize',
        'settings',
    ];

    protected $casts = [
        'xp_enabled' => 'boolean',
        'badges_enabled' => 'boolean',
        'achievements_enabled' => 'boolean',
        'streaks_enabled' => 'boolean',
        'challenges_enabled' => 'boolean',
        'leaderboards_enabled' => 'boolean',
        'xp_per_assessment_complete' => 'integer',
        'xp_per_improvement' => 'integer',
        'xp_per_streak_day' => 'integer',
        'xp_per_topic_mastery' => 'integer',
        'xp_per_challenge_complete' => 'integer',
        'streak_qualifying_activities' => 'array',
        'level_config' => 'array',
        'leaderboard_anonymize' => 'boolean',
        'settings' => 'array',
    ];

    public function learnerXps(): HasMany
    {
        return $this->hasMany(LearnerXp::class, 'school_id', 'school_id');
    }

    public static function forSchool(int $schoolId): self
    {
        return static::firstOrCreate(
            ['school_id' => $schoolId],
            [
                'xp_enabled' => false,
                'badges_enabled' => false,
                'achievements_enabled' => false,
                'streaks_enabled' => false,
                'challenges_enabled' => false,
                'leaderboards_enabled' => false,
                'xp_per_assessment_complete' => 10,
                'xp_per_improvement' => 15,
                'xp_per_streak_day' => 5,
                'xp_per_topic_mastery' => 25,
                'xp_per_challenge_complete' => 20,
                'level_config' => self::defaultLevels(),
                'leaderboard_scope' => 'class',
            ]
        );
    }

    public function isXpEnabled(): bool
    {
        return $this->xp_enabled;
    }

    public function isAnyGamificationEnabled(): bool
    {
        return $this->xp_enabled
            || $this->badges_enabled
            || $this->achievements_enabled
            || $this->streaks_enabled
            || $this->challenges_enabled
            || $this->leaderboards_enabled;
    }

    public static function defaultLevels(): array
    {
        return [
            ['level' => 1, 'name' => 'Explorer', 'xp_threshold' => 0],
            ['level' => 2, 'name' => 'Learner', 'xp_threshold' => 100],
            ['level' => 3, 'name' => 'Achiever', 'xp_threshold' => 300],
            ['level' => 4, 'name' => 'Scholar', 'xp_threshold' => 600],
            ['level' => 5, 'name' => 'Master', 'xp_threshold' => 1000],
            ['level' => 6, 'name' => 'Expert', 'xp_threshold' => 1500],
            ['level' => 7, 'name' => 'Sage', 'xp_threshold' => 2200],
            ['level' => 8, 'name' => 'Laureate', 'xp_threshold' => 3000],
            ['level' => 9, 'name' => 'Virtuoso', 'xp_threshold' => 4000],
            ['level' => 10, 'name' => 'Prodigy', 'xp_threshold' => 5500],
        ];
    }
}
