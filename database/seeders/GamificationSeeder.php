<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\DigitalAssessment\Models\GamificationAchievement;
use Modules\DigitalAssessment\Models\GamificationBadge;

class GamificationSeeder extends Seeder
{
    public function run(): void
    {
        $schoolIds = DB::table('schools')->pluck('id')->toArray();

        foreach ($schoolIds as $schoolId) {
            $this->seedBadges($schoolId);
            $this->seedAchievements($schoolId);
        }
    }

    protected function seedBadges(int $schoolId): void
    {
        $badges = [
            [
                'name' => 'First Steps',
                'description' => 'Completed your first assessment attempt.',
                'icon' => 'heroicon-o-play',
                'criteria' => ['type' => 'assessments_completed', 'count' => 1],
                'xp_reward' => 50,
                'is_active' => true,
                'is_system' => true,
                'visibility' => 'public',
            ],
            [
                'name' => 'Perfect Score',
                'description' => 'Achieved 100% on any assessment.',
                'icon' => 'heroicon-o-star',
                'criteria' => ['type' => 'perfect_score', 'count' => 1],
                'xp_reward' => 200,
                'is_active' => true,
                'is_system' => true,
                'visibility' => 'public',
            ],
            [
                'name' => 'On Fire',
                'description' => 'Maintained a 7-day activity streak.',
                'icon' => 'heroicon-o-fire',
                'criteria' => ['type' => 'streak_days', 'count' => 7],
                'xp_reward' => 150,
                'is_active' => true,
                'is_system' => true,
                'visibility' => 'public',
            ],
            [
                'name' => 'Quiz Master',
                'description' => 'Completed 10 assessments.',
                'icon' => 'heroicon-o-academic-cap',
                'criteria' => ['type' => 'assessments_completed', 'count' => 10],
                'xp_reward' => 300,
                'is_active' => true,
                'is_system' => true,
                'visibility' => 'public',
            ],
            [
                'name' => 'Question Slayer',
                'description' => 'Answered 50 questions correctly across all assessments.',
                'icon' => 'heroicon-o-bolt',
                'criteria' => ['type' => 'correct_answers', 'count' => 50],
                'xp_reward' => 250,
                'is_active' => true,
                'is_system' => true,
                'visibility' => 'public',
            ],
            [
                'name' => 'Night Owl',
                'description' => 'Submitted an assessment after 8 PM.',
                'icon' => 'heroicon-o-moon',
                'criteria' => ['type' => 'late_night_submission', 'count' => 1],
                'xp_reward' => 75,
                'is_active' => true,
                'is_system' => true,
                'visibility' => 'private',
            ],
            [
                'name' => 'Speed Demon',
                'description' => 'Completed an assessment in under 5 minutes.',
                'icon' => 'heroicon-o-clock',
                'criteria' => ['type' => 'fast_completion', 'count' => 1],
                'xp_reward' => 100,
                'is_active' => true,
                'is_system' => true,
                'visibility' => 'public',
            ],
            [
                'name' => 'Marathon Runner',
                'description' => 'Maintained a 30-day activity streak.',
                'icon' => 'heroicon-o-trophy',
                'criteria' => ['type' => 'streak_days', 'count' => 30],
                'xp_reward' => 500,
                'is_active' => true,
                'is_system' => true,
                'visibility' => 'public',
            ],
        ];

        foreach ($badges as $badge) {
            GamificationBadge::updateOrCreate(
                ['school_id' => $schoolId, 'name' => $badge['name']],
                $badge + ['school_id' => $schoolId]
            );
        }
    }

    protected function seedAchievements(int $schoolId): void
    {
        $achievements = [
            [
                'name' => 'Rookie',
                'description' => 'Complete your first assessment in any subject.',
                'icon' => 'heroicon-o-sparkles',
                'criteria' => ['type' => 'assessments_completed', 'count' => 1],
                'xp_reward' => 25,
                'achievement_type' => 'milestone',
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Scholar',
                'description' => 'Pass 5 assessments with a score of 70% or higher.',
                'icon' => 'heroicon-o-book-open',
                'criteria' => ['type' => 'assessments_passed_70', 'count' => 5],
                'xp_reward' => 100,
                'achievement_type' => 'milestone',
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Top of the Class',
                'description' => 'Rank in the top 3 on any class leaderboard.',
                'icon' => 'heroicon-o-arrow-trending-up',
                'criteria' => ['type' => 'leaderboard_top', 'rank' => 3],
                'xp_reward' => 200,
                'achievement_type' => 'leaderboard',
                'is_active' => true,
                'is_system' => true,
            ],
            [
                'name' => 'Century Club',
                'description' => 'Earn 100 total XP from assessments and activities.',
                'icon' => 'heroicon-o-bolt',
                'criteria' => ['type' => 'total_xp', 'count' => 100],
                'xp_reward' => 50,
                'achievement_type' => 'xp_milestone',
                'is_active' => true,
                'is_system' => true,
            ],
        ];

        foreach ($achievements as $achievement) {
            GamificationAchievement::updateOrCreate(
                ['school_id' => $schoolId, 'name' => $achievement['name']],
                $achievement + ['school_id' => $schoolId]
            );
        }
    }
}
